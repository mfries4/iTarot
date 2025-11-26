<?php

namespace App\Service;

use App\Entity\User;
use App\Entity\GameList;
use App\Repository\GamePlayerRepository;
use App\Repository\GameRepository;

class StatisticsService
{
    public function __construct(
        private GamePlayerRepository $gamePlayerRepository,
        private GameRepository $gameRepository
    ) {
    }

    /**
     * Récupère toutes les statistiques pour un utilisateur
     */
    public function getUserStatistics(User $user): array
    {
        $playersStats = $this->gamePlayerRepository->getAllPlayersStats($user);
        $games = $this->gameRepository->findByOwner($user);

        return [
            'totalGames' => count($games),
            'playersStats' => $playersStats,
            'topScorer' => $this->getTopPlayer($playersStats, 'totalScore'),
            'mostTaker' => $this->getTopPlayer($playersStats, 'timesTaker'),
            'bestWinRate' => $this->getBestWinRate($playersStats),
        ];
    }

    /**
     * Récupère le meilleur joueur selon un critère
     */
    private function getTopPlayer(array $playersStats, string $criteria): ?array
    {
        if (empty($playersStats)) {
            return null;
        }

        usort($playersStats, function ($a, $b) use ($criteria) {
            return ($b[$criteria] ?? 0) <=> ($a[$criteria] ?? 0);
        });

        return $playersStats[0] ?? null;
    }

    /**
     * Récupère le joueur avec le meilleur taux de victoire
     */
    private function getBestWinRate(array $playersStats): ?array
    {
        if (empty($playersStats)) {
            return null;
        }

        $statsWithWinRate = array_map(function ($stats) {
            $stats['winRate'] = $stats['totalGames'] > 0 
                ? ($stats['wins'] / $stats['totalGames']) * 100 
                : 0;
            return $stats;
        }, $playersStats);

        usort($statsWithWinRate, function ($a, $b) {
            return $b['winRate'] <=> $a['winRate'];
        });

        return $statsWithWinRate[0] ?? null;
    }

    /**
     * Prépare les données pour les graphiques
     */
    public function getChartData(User $user): array
    {
        $playersStats = $this->gamePlayerRepository->getAllPlayersStats($user);

        return [
            'labels' => array_column($playersStats, 'name'),
            'totalScores' => array_column($playersStats, 'totalScore'),
            'totalGames' => array_column($playersStats, 'totalGames'),
            'timesTaker' => array_column($playersStats, 'timesTaker'),
            'wins' => array_column($playersStats, 'wins'),
        ];
    }

    /**
     * Statistiques pour une liste de parties (cumul par joueur)
     */
    public function getGameListStatistics(GameList $gameList): array
    {
        $cumulative = [];
        foreach ($gameList->getPlayers() as $player) {
            $cumulative[$player->getId()] = [
                'player' => $player,
                'score' => 0,
                'takerCount' => 0,
                'allyCount' => 0,
                'games' => 0,
            ];
        }
        foreach ($gameList->getGames() as $game) {
            foreach ($game->getGamePlayers() as $gp) {
                $pid = $gp->getPlayer()->getId();
                if (!isset($cumulative[$pid])) {
                    continue;
                }
                $cumulative[$pid]['score'] += $gp->getScore();
                $cumulative[$pid]['games'] += 1;
                if ($gp->isTaker()) {
                    $cumulative[$pid]['takerCount'] += 1;
                }
                if ($gp->isAlly()) {
                    $cumulative[$pid]['allyCount'] += 1;
                }
            }
        }
        // Classement score desc
        usort($cumulative, fn($a,$b) => $b['score'] <=> $a['score']);
        return $cumulative;
    }

    /**
     * Données graphiques pour une liste
     */
    public function getGameListChartData(GameList $gameList): array
    {
        $stats = $this->getGameListStatistics($gameList);
        $contractStats = $this->getContractStatistics($gameList);
        
        return [
            'labels' => array_map(fn($s) => $s['player']->getName(), $stats),
            'scores' => array_map(fn($s) => $s['score'], $stats),
            'takers' => array_map(fn($s) => $s['takerCount'], $stats),
            'allies' => array_map(fn($s) => $s['allyCount'], $stats),
            'games' => array_map(fn($s) => $s['games'], $stats),
            'contracts' => $contractStats,
        ];
    }

    /**
     * Statistiques sur les contrats (types les plus joués et taux de réussite)
     */
    public function getContractStatistics(GameList $gameList): array
    {
        $contractData = [
            'petite' => ['total' => 0, 'won' => 0, 'label' => 'Petite'],
            'garde' => ['total' => 0, 'won' => 0, 'label' => 'Garde'],
            'garde_sans' => ['total' => 0, 'won' => 0, 'label' => 'Garde Sans'],
            'garde_contre' => ['total' => 0, 'won' => 0, 'label' => 'Garde Contre'],
        ];

        foreach ($gameList->getGames() as $game) {
            $contractType = $game->getContractType();
            if (isset($contractData[$contractType])) {
                $contractData[$contractType]['total']++;
                $taker = $game->getTaker();
                if ($taker && $taker->getScore() > 0) {
                    $contractData[$contractType]['won']++;
                }
            }
        }

        // Calculer les taux de réussite
        foreach ($contractData as $key => &$data) {
            $data['successRate'] = $data['total'] > 0 
                ? round(($data['won'] / $data['total']) * 100, 1) 
                : 0;
        }

        return $contractData;
    }
}
