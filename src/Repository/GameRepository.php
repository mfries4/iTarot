<?php

namespace App\Repository;

use App\Entity\Game;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Game>
 */
class GameRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Game::class);
    }

    /**
     * Récupère toutes les parties d'un utilisateur
     */
    public function findByOwner(User $owner): array
    {
        return $this->createQueryBuilder('g')
            ->andWhere('g.owner = :owner')
            ->setParameter('owner', $owner)
            ->orderBy('g.playedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Récupère les dernières parties d'un utilisateur
     */
    public function findRecentByOwner(User $owner, int $limit = 10): array
    {
        return $this->createQueryBuilder('g')
            ->andWhere('g.owner = :owner')
            ->setParameter('owner', $owner)
            ->orderBy('g.playedAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}
