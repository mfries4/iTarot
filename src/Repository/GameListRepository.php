<?php

namespace App\Repository;

use App\Entity\GameList;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<GameList>
 */
class GameListRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, GameList::class);
    }

    /**
     * @return GameList[]
     */
    public function findByOwner(User $owner): array
    {
        return $this->createQueryBuilder('gl')
            ->andWhere('gl.owner = :owner')
            ->setParameter('owner', $owner)
            ->orderBy('gl.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
