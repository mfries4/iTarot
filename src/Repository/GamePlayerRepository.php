<?php

namespace App\Repository;

use App\Entity\GamePlayer;
use App\Entity\Player;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<GamePlayer>
 */
class GamePlayerRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, GamePlayer::class);
    }

    /**
     * Récupère les statistiques globales pour un joueur
     */
    public function getPlayerStats(Player $player): array
    {
        $qb = $this->createQueryBuilder('gp')
            ->select('COUNT(gp.id) as totalGames')
            ->addSelect('SUM(gp.score) as totalScore')
            ->addSelect('AVG(gp.score) as averageScore')
            ->addSelect('SUM(CASE WHEN gp.isTaker = true THEN 1 ELSE 0 END) as timesTaker')
            ->addSelect('SUM(CASE WHEN gp.score > 0 THEN 1 ELSE 0 END) as wins')
            ->andWhere('gp.player = :player')
            ->setParameter('player', $player)
            ->getQuery()
            ->getSingleResult();

        return $qb;
    }

    /**
     * Récupère les statistiques pour tous les joueurs d'un utilisateur
     */
    public function getAllPlayersStats(User $owner): array
    {
        return $this->createQueryBuilder('gp')
            ->select('p.id', 'p.name')
            ->addSelect('COUNT(gp.id) as totalGames')
            ->addSelect('SUM(gp.score) as totalScore')
            ->addSelect('AVG(gp.score) as averageScore')
            ->addSelect('SUM(CASE WHEN gp.isTaker = true THEN 1 ELSE 0 END) as timesTaker')
            ->addSelect('SUM(CASE WHEN gp.score > 0 THEN 1 ELSE 0 END) as wins')
            ->join('gp.player', 'p')
            ->join('gp.game', 'g')
            ->andWhere('g.owner = :owner')
            ->setParameter('owner', $owner)
            ->groupBy('p.id')
            ->orderBy('totalScore', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
