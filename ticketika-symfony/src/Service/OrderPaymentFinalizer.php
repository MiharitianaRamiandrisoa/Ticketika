<?php

namespace App\Service;

use App\Entity\Order;
use App\Entity\Ticket;
use Doctrine\ORM\EntityManagerInterface;

final class OrderPaymentFinalizer
{
    public function __construct(private readonly EntityManagerInterface $em)
    {
    }

    public function markPaid(Order $order): void
    {
        if ($order->isPaid() || $order->getStatus() !== Order::STATUS_PENDING_PAYMENT) {
            return;
        }

        $types = [];
        foreach ($order->getEvent()?->getTicketTypes() ?? [] as $type) {
            $types[(string) $type->getId()] = $type;
        }

        foreach ($order->getTicketSelection() as $typeId => $quantity) {
            $type = $types[(string) $typeId] ?? null;
            if ($type === null) {
                continue;
            }

            for ($i = 0; $i < $quantity; $i++) {
                $order->addTicket((new Ticket())
                    ->setTicketType($type)
                    ->setCode(strtoupper(bin2hex(random_bytes(6)))));
            }
        }

        $order->setStatus(Order::STATUS_PAID);
        $this->em->flush();
    }

    public function markFailed(Order $order): void
    {
        if ($order->getStatus() !== Order::STATUS_PENDING_PAYMENT) {
            return;
        }

        $types = [];
        foreach ($order->getEvent()?->getTicketTypes() ?? [] as $type) {
            $types[(string) $type->getId()] = $type;
        }

        $count = 0;
        foreach ($order->getTicketSelection() as $typeId => $quantity) {
            $type = $types[(string) $typeId] ?? null;
            if ($type === null) {
                continue;
            }

            $type->addSold(-$quantity);
            $count += $quantity;
        }

        $order->getEvent()?->addSoldTickets(-$count);
        $order->setStatus(Order::STATUS_FAILED);
        $this->em->flush();
    }
}
