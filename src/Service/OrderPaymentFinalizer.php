<?php

namespace App\Service;

use App\Entity\Order;
use App\Entity\Ticket;
use Doctrine\DBAL\LockMode;
use Doctrine\ORM\EntityManagerInterface;

final class OrderPaymentFinalizer
{
    public function __construct(private readonly EntityManagerInterface $em)
    {
    }

    public function markPaid(Order $order): void
    {
        $this->em->wrapInTransaction(function () use ($order): void {
            $lockedOrder = $this->lockOrder($order);
            if ($lockedOrder === null || $lockedOrder->isPaid() || $lockedOrder->getStatus() !== Order::STATUS_PENDING_PAYMENT) {
                return;
            }

            $event = $lockedOrder->getEvent();
            if ($event === null) {
                throw new \RuntimeException('La commande ne possède pas d’événement.');
            }
            $this->em->lock($event, LockMode::PESSIMISTIC_WRITE);

            $types = [];
            foreach ($event->getTicketTypes() as $type) {
                $this->em->lock($type, LockMode::PESSIMISTIC_WRITE);
                $types[(string) $type->getId()] = $type;
            }

            foreach ($lockedOrder->getTicketSelection() as $typeId => $quantity) {
                $type = $types[(string) $typeId] ?? null;
                if ($type === null) {
                    throw new \RuntimeException('Le type de billet de la commande est introuvable.');
                }

                for ($i = 0; $i < $quantity; $i++) {
                    $lockedOrder->addTicket((new Ticket())
                        ->setTicketType($type)
                        ->setCode(strtoupper(bin2hex(random_bytes(6)))));
                }
            }

            $lockedOrder
                ->setStatus(Order::STATUS_PAID)
                ->setReservationExpiresAt(null);
            $this->em->flush();
        });
    }

    public function markFailed(Order $order): void
    {
        $this->releaseReservation($order, Order::STATUS_FAILED);
    }

    public function markExpired(Order $order): void
    {
        $this->releaseReservation($order, Order::STATUS_EXPIRED);
    }

    private function releaseReservation(Order $order, string $status): void
    {
        $this->em->wrapInTransaction(function () use ($order, $status): void {
            $lockedOrder = $this->lockOrder($order);
            if ($lockedOrder === null || $lockedOrder->getStatus() !== Order::STATUS_PENDING_PAYMENT) {
                return;
            }

            $event = $lockedOrder->getEvent();
            if ($event === null) {
                throw new \RuntimeException('La commande ne possède pas d’événement.');
            }
            $this->em->lock($event, LockMode::PESSIMISTIC_WRITE);

            $types = [];
            foreach ($event->getTicketTypes() as $type) {
                $this->em->lock($type, LockMode::PESSIMISTIC_WRITE);
                $types[(string) $type->getId()] = $type;
            }

            $count = 0;
            foreach ($lockedOrder->getTicketSelection() as $typeId => $quantity) {
                $type = $types[(string) $typeId] ?? null;
                if ($type === null) {
                    throw new \RuntimeException('Le type de billet de la commande est introuvable.');
                }
                $type->addSold(-$quantity);
                $count += $quantity;
            }

            $event->addSoldTickets(-$count);
            $lockedOrder
                ->setStatus($status)
                ->setReservationExpiresAt(null);
            $this->em->flush();
        });
    }

    private function lockOrder(Order $order): ?Order
    {
        if ($order->getId() === null) {
            return null;
        }

        return $this->em->find(Order::class, $order->getId(), LockMode::PESSIMISTIC_WRITE);
    }
}
