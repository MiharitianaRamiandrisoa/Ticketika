<?php

namespace App\Command;

use App\Entity\Order;
use App\Service\OrderPaymentFinalizer;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:orders:expire',
    description: 'Libère les réservations de paiement arrivées à expiration.',
)]
final class ExpirePendingOrdersCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly OrderPaymentFinalizer $finalizer,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $orders = $this->em->createQueryBuilder()
            ->select('o')
            ->from(Order::class, 'o')
            ->where('o.status = :status')
            ->andWhere('o.reservationExpiresAt IS NOT NULL')
            ->andWhere('o.reservationExpiresAt < :now')
            ->setParameter('status', Order::STATUS_PENDING_PAYMENT)
            ->setParameter('now', new \DateTimeImmutable())
            ->getQuery()
            ->getResult();

        foreach ($orders as $order) {
            $this->finalizer->markExpired($order);
        }

        $output->writeln(sprintf('%d réservation(s) expirée(s).', count($orders)));

        return Command::SUCCESS;
    }
}
