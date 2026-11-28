<?php

namespace App\Service;

use App\Entity\Event;
use App\Entity\Order;
use App\Entity\User;
use Doctrine\DBAL\LockMode;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Crée une commande, réserve les places et génère les billets.
 * Le paiement réel doit être branché ici avec un prestataire mobile money.
 */
final class OrderPlacer
{
    public const SERVICE_FEE_AR = 2000;
    public const MAX_TICKETS_PER_ORDER = 8;

    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly int $paymentHoldMinutes,
    )
    {
    }

    /**
     * @param array<int|string, int|string> $quantities ticketTypeId => quantité
     * @param array{firstName: string, lastName: string, email: string, paymentMethod: string} $buyer
     */
    public function place(Event $event, array $quantities, array $buyer, User $user): Order
    {
        if (($buyer['paymentMethod'] ?? null) !== 'mobile') {
            throw new \RuntimeException('Seul le paiement par mobile money est disponible.');
        }

        return $this->em->wrapInTransaction(function () use ($event, $quantities, $buyer, $user): Order {
            $this->em->lock($event, LockMode::PESSIMISTIC_WRITE);
            foreach ($event->getTicketTypes() as $type) {
                $this->em->lock($type, LockMode::PESSIMISTIC_WRITE);
            }

        $order = (new Order())
            ->setEvent($event)
            ->setFirstName($buyer['firstName'])
            ->setLastName($buyer['lastName'])
            ->setEmail($buyer['email'])
            ->setPaymentMethod($buyer['paymentMethod'])
            ->setUser($user);

        $subtotal = 0;
        $count = 0;
        $selection = [];

        foreach ($event->getTicketTypes() as $type) {
            $qty = max(0, (int) ($quantities[$type->getId()] ?? 0));
            if ($qty === 0) {
                continue;
            }
            if ($qty > self::MAX_TICKETS_PER_ORDER || $count + $qty > self::MAX_TICKETS_PER_ORDER) {
                throw new \RuntimeException(sprintf('Une commande ne peut pas dépasser %d billets.', self::MAX_TICKETS_PER_ORDER));
            }
            if ($qty > $type->getRemaining()) {
                throw new \RuntimeException(sprintf('Il ne reste que %d place(s) pour « %s ».', $type->getRemaining(), $type->getName()));
            }

            $type->addSold($qty);
            $selection[(string) $type->getId()] = $qty;

            $subtotal += $qty * $type->getPriceAr();
            $count += $qty;
        }

        if ($count === 0) {
            throw new \RuntimeException('Aucun billet sélectionné.');
        }
        if ($count > $event->getRemaining()) {
            throw new \RuntimeException(sprintf('Il ne reste que %d place(s) pour cet événement.', $event->getRemaining()));
        }

        $fee = $subtotal > 0 ? self::SERVICE_FEE_AR : 0;
        $order
            ->setTicketSelection($selection)
            ->setServiceFeeAr($fee)
            ->setTotalAr($subtotal + $fee);
        $order->setReservationExpiresAt(new \DateTimeImmutable(sprintf('+%d minutes', max(1, $this->paymentHoldMinutes))));
        $event->addSoldTickets($count);

        $this->em->persist($order);
        $this->em->flush();

        return $order;
        });
    }
}
