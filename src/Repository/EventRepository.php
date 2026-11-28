<?php

namespace App\Repository;

use App\Entity\Event;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<Event> */
class EventRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Event::class);
    }

    /** @return Event[] */
    public function findUpcoming(?string $category = null, ?string $search = null): array
    {
        $qb = $this->createQueryBuilder('e')
            ->where('e.startsAt >= :since')
            ->setParameter('since', new \DateTimeImmutable('-1 day'))
            ->orderBy('e.startsAt', 'ASC');

        if ($category !== null && trim($category) !== '') {
            $qb->join('e.category', 'c')
                ->andWhere('c.slug = :category')
                ->andWhere('c.active = :categoryActive')
                ->setParameter('category', trim($category))
                ->setParameter('categoryActive', true);
        }

        if ($search !== null && trim($search) !== '') {
            $qb->andWhere('LOWER(e.title) LIKE :q OR LOWER(e.place) LIKE :q')
                ->setParameter('q', '%'.mb_strtolower(trim($search)).'%');
        }

        return $qb->getQuery()->getResult();
    }
}
