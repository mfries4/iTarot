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
        $perPlayer = $this->getPerPlayerStatistics($gameList);
        
        return [
            'labels' => array_map(fn($s) => $s['player']->getName(), $stats),
            'scores' => array_map(fn($s) => $s['score'], $stats),
            'takers' => array_map(fn($s) => $s['takerCount'], $stats),
            'allies' => array_map(fn($s) => $s['allyCount'], $stats),
            'games' => array_map(fn($s) => $s['games'], $stats),
            'contracts' => $contractStats,
            'perPlayer' => $perPlayer,
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

    /**
     * Statistiques détaillées par joueur dans une liste
     */
    public function getPerPlayerStatistics(GameList $gameList): array
    {
        $result = [];
        foreach ($gameList->getPlayers() as $player) {
            $result[$player->getId()] = [
                'player' => $player,
                'name' => $player->getName(),
                'totalGames' => 0,
                'wins' => 0,
                'losses' => 0,
                'totalScore' => 0,
                'avgScore' => 0,
                'timesTaker' => 0,
                'timesAlly' => 0,
                'bestContract' => null,
            ];
        }

        foreach ($gameList->getGames() as $game) {
            // Identifie victoire/défaite du preneur
            $taker = $game->getTaker();
            $takerWon = $taker ? ($taker->getScore() > 0) : false;
            $contractType = $game->getContractType();

            foreach ($game->getGamePlayers() as $gp) {
                $pid = $gp->getPlayer()->getId();
                if (!isset($result[$pid])) { continue; }
                $row = &$result[$pid];

                $row['totalGames'] += 1;
                $row['totalScore'] += $gp->getScore();
                if ($gp->isTaker()) {
                    $row['timesTaker'] += 1;
                    if ($takerWon) { $row['wins'] += 1; } else { $row['losses'] += 1; }
                    // meilleur contrat côté preneur: garder celui avec meilleur score perso
                    if ($row['bestContract'] === null || $gp->getScore() > ($row['bestContract']['score'] ?? -INF)) {
                        $row['bestContract'] = ['type' => $contractType, 'score' => $gp->getScore()];
                    }
                } elseif ($gp->isAlly()) {
                    $row['timesAlly'] += 1;
                    if ($takerWon) { $row['wins'] += 1; } else { $row['losses'] += 1; }
                    if ($row['bestContract'] === null || $gp->getScore() > ($row['bestContract']['score'] ?? -INF)) {
                        $row['bestContract'] = ['type' => $contractType, 'score' => $gp->getScore()];
                    }
                } else {
                    // défenseurs: victoire quand le preneur perd
                    if (!$takerWon) { $row['wins'] += 1; } else { $row['losses'] += 1; }
                }
                // calcul avg plus tard
                unset($row);
            }
        }

        foreach ($result as &$row) {
            $row['avgScore'] = $row['totalGames'] > 0 ? round($row['totalScore'] / $row['totalGames'], 1) : 0;
            if (is_array($row['bestContract'])) {
                $row['bestContractLabel'] = match($row['bestContract']['type'] ?? null) {
                    'petite' => 'Petite',
                    'garde' => 'Garde',
                    'garde_sans' => 'Garde Sans',
                    'garde_contre' => 'Garde Contre',
                    default => null,
                };
            } else {
                $row['bestContractLabel'] = null;
            }
            $row['winRate'] = $row['totalGames'] > 0 ? round(($row['wins'] / $row['totalGames']) * 100, 1) : 0;
        }
        unset($row);

        // trier par score total desc
        usort($result, fn($a,$b) => $b['totalScore'] <=> $a['totalScore']);
        return $result;
    }
}
