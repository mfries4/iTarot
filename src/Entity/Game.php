<?php

namespace App\Entity;

use App\Repository\GameRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: GameRepository::class)]
class Game
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: GameList::class, inversedBy: 'games')]
    #[ORM\JoinColumn(nullable: false)]
    private ?GameList $gameList = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $playedAt = null;

    #[ORM\Column(length: 50)]
    private ?string $contractType = null; // 'petite', 'garde', 'garde_sans', 'garde_contre'

    #[ORM\Column]
    private ?int $oudlers = null; // Nombre de bouts (0-3)

    #[ORM\Column]
    private ?int $points = null; // Points marqués par le preneur

    #[ORM\Column]
    private ?bool $petitAuBout = false;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $poigneeType = null; // null, 'simple', 'double', 'triple'

    #[ORM\OneToMany(targetEntity: GamePlayer::class, mappedBy: 'game', cascade: ['persist', 'remove'])]
    private Collection $gamePlayers;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $createdAt = null;

    public function __construct()
    {
        $this->gamePlayers = new ArrayCollection();
        $this->createdAt = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPlayedAt(): ?\DateTimeInterface
    {
        return $this->playedAt;
    }

    public function setPlayedAt(\DateTimeInterface $playedAt): static
    {
        $this->playedAt = $playedAt;

        return $this;
    }

    public function getGameList(): ?GameList
    {
        return $this->gameList;
    }

    public function setGameList(?GameList $gameList): static
    {
        $this->gameList = $gameList;
        return $this;
    }

    public function getContractType(): ?string
    {
        return $this->contractType;
    }

    public function setContractType(string $contractType): static
    {
        $this->contractType = $contractType;

        return $this;
    }

    public function getOudlers(): ?int
    {
        return $this->oudlers;
    }

    public function setOudlers(int $oudlers): static
    {
        $this->oudlers = $oudlers;

        return $this;
    }

    public function getPoints(): ?int
    {
        return $this->points;
    }

    public function setPoints(int $points): static
    {
        $this->points = $points;

        return $this;
    }

    public function isPetitAuBout(): ?bool
    {
        return $this->petitAuBout;
    }

    public function setPetitAuBout(bool $petitAuBout): static
    {
        $this->petitAuBout = $petitAuBout;

        return $this;
    }

    public function getPoigneeType(): ?string
    {
        return $this->poigneeType;
    }

    public function setPoigneeType(?string $poigneeType): static
    {
        $this->poigneeType = $poigneeType;

        return $this;
    }

    /**
     * @return Collection<int, GamePlayer>
     */
    public function getGamePlayers(): Collection
    {
        return $this->gamePlayers;
    }

    public function addGamePlayer(GamePlayer $gamePlayer): static
    {
        if (!$this->gamePlayers->contains($gamePlayer)) {
            $this->gamePlayers->add($gamePlayer);
            $gamePlayer->setGame($this);
        }

        return $this;
    }

    public function removeGamePlayer(GamePlayer $gamePlayer): static
    {
        if ($this->gamePlayers->removeElement($gamePlayer)) {
            if ($gamePlayer->getGame() === $this) {
                $gamePlayer->setGame(null);
            }
        }

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeInterface $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getTaker(): ?GamePlayer
    {
        foreach ($this->gamePlayers as $gamePlayer) {
            if ($gamePlayer->isTaker()) {
                return $gamePlayer;
            }
        }
        return null;
    }

    public function getAlly(): ?GamePlayer
    {
        foreach ($this->gamePlayers as $gamePlayer) {
            if ($gamePlayer->isAlly()) {
                return $gamePlayer;
            }
        }
        return null;
    }
}
