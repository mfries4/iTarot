<?php

namespace App\Entity;

use App\Repository\GamePlayerRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: GamePlayerRepository::class)]
class GamePlayer
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Game::class, inversedBy: 'gamePlayers')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Game $game = null;

    #[ORM\ManyToOne(targetEntity: Player::class, inversedBy: 'gamePlayers')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Player $player = null;

    #[ORM\Column]
    private ?bool $isTaker = false;

    #[ORM\Column]
    private ?bool $isAlly = false;

    #[ORM\Column]
    private ?int $score = 0;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getGame(): ?Game
    {
        return $this->game;
    }

    public function setGame(?Game $game): static
    {
        $this->game = $game;

        return $this;
    }

    public function getPlayer(): ?Player
    {
        return $this->player;
    }

    public function setPlayer(?Player $player): static
    {
        $this->player = $player;

        return $this;
    }

    public function isTaker(): ?bool
    {
        return $this->isTaker;
    }

    public function setIsTaker(bool $isTaker): static
    {
        $this->isTaker = $isTaker;

        return $this;
    }

    public function isAlly(): ?bool
    {
        return $this->isAlly;
    }

    public function setIsAlly(bool $isAlly): static
    {
        $this->isAlly = $isAlly;

        return $this;
    }

    public function getScore(): ?int
    {
        return $this->score;
    }

    public function setScore(int $score): static
    {
        $this->score = $score;

        return $this;
    }
}
