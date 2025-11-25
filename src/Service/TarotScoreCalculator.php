<?php

namespace App\Service;

use App\Entity\Game;
use App\Entity\GamePlayer;

class TarotScoreCalculator
{
    /**
     * Calcule les scores de tous les joueurs d'une partie
     */
    public function calculateScores(Game $game): void
    {
        $taker = $game->getTaker();
        if (!$taker) {
            throw new \Exception('Aucun preneur trouvé pour cette partie');
        }

        $ally = $game->getAlly();
        $numberOfPlayers = $game->getGameList() ? $game->getGameList()->getPlayers()->count() : 0;

        // Calcul du score de base (ce que chaque perdant perd)
        $baseScore = $this->calculateTakerScore($game);

        // Calculer le nombre d'adversaires
        if ($numberOfPlayers == 5 && $ally) {
            // À 5 avec allié : 2 attaquants (preneur + allié) contre 3 défenseurs
            $numberOfOpponents = 3; // adversaires du preneur et de l'allié
        } else {
            // À 3 ou 4 joueurs : tous contre le preneur
            $numberOfOpponents = $numberOfPlayers - 1;
        }

        // Attribution des scores
        foreach ($game->getGamePlayers() as $gamePlayer) {
            if ($gamePlayer->isTaker()) {
                // Le preneur gagne : baseScore × nombre d'adversaires
                $gamePlayer->setScore($baseScore * $numberOfOpponents);
            } elseif ($gamePlayer->isAlly()) {
                // L'allié gagne : baseScore × nombre d'adversaires
                $gamePlayer->setScore($baseScore * $numberOfOpponents);
            } else {
                // Chaque défenseur perd le baseScore
                $gamePlayer->setScore(-$baseScore);
            }
        }
    }

    /**
     * Calcule le score du preneur selon les règles du tarot
     */
    private function calculateTakerScore(Game $game): int
    {
        // 1. Déterminer le nombre de points requis selon les bouts
        $requiredPoints = $this->getRequiredPoints($game->getOudlers());

        // 2. Calculer la différence de points
        $pointsDifference = $game->getPoints() - $requiredPoints;

        // 3. Score de base (25 + différence)
        $baseScore = 25 + abs($pointsDifference);

        // 4. Appliquer le multiplicateur selon le contrat
        $multiplier = $this->getContractMultiplier($game->getContractType());
        $score = $baseScore * $multiplier;

        // 5. Si le preneur a perdu, le score est négatif
        if ($pointsDifference < 0) {
            $score = -$score;
        }

        // 6. Ajouter les bonus
        $bonusPoints = 0;

        // Petit au bout
        if ($game->isPetitAuBout()) {
            $bonusPoints += 10 * $multiplier;
        }

        // Poignée
        if ($game->getPoigneeType()) {
            $poigneeBonus = $this->getPoigneeBonus($game->getPoigneeType());
            $bonusPoints += $poigneeBonus;
        }

        return $score + $bonusPoints;
    }

    /**
     * Retourne le nombre de points requis selon le nombre de bouts
     */
    private function getRequiredPoints(int $oudlers): int
    {
        return match ($oudlers) {
            3 => 36,
            2 => 41,
            1 => 51,
            0 => 56,
            default => throw new \InvalidArgumentException('Le nombre de bouts doit être entre 0 et 3')
        };
    }

    /**
     * Retourne le multiplicateur selon le type de contrat
     */
    private function getContractMultiplier(string $contractType): int
    {
        return match ($contractType) {
            'petite' => 2,
            'garde' => 4,
            'garde_sans' => 6,
            'garde_contre' => 8,
            default => throw new \InvalidArgumentException('Type de contrat invalide')
        };
    }

    /**
     * Retourne le bonus de poignée
     */
    private function getPoigneeBonus(string $poigneeType): int
    {
        return match ($poigneeType) {
            'simple' => 20,
            'double' => 30,
            'triple' => 40,
            default => 0
        };
    }

    /**
     * Formate le nom d'un contrat pour l'affichage
     */
    public function getContractDisplayName(string $contractType): string
    {
        return match ($contractType) {
            'petite' => 'Petite',
            'garde' => 'Garde',
            'garde_sans' => 'Garde sans le chien',
            'garde_contre' => 'Garde contre le chien',
            default => $contractType
        };
    }

    /**
     * Formate le nom d'une poignée pour l'affichage
     */
    public function getPoigneeDisplayName(?string $poigneeType): string
    {
        if (!$poigneeType) {
            return 'Aucune';
        }

        return match ($poigneeType) {
            'simple' => 'Simple (10 atouts)',
            'double' => 'Double (13 atouts)',
            'triple' => 'Triple (15 atouts)',
            default => $poigneeType
        };
    }
}
