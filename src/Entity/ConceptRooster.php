<?php

namespace App\Entity;

use App\Repository\ConceptRoosterRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ConceptRoosterRepository::class)]
class ConceptRooster
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\OneToOne(cascade: ['persist', 'remove'])]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?user $user = null;

    #[ORM\Column]
    private array $basic = [];

    #[ORM\Column]
    private array $even = [];

    #[ORM\Column]
    private array $odd = [];

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): ?user
    {
        return $this->user;
    }

    public function setUser(user $user): static
    {
        $this->user = $user;

        return $this;
    }

    public function getBasic(): array
    {
        return $this->basic;
    }

    public function setBasic(array $basic): static
    {
        $this->basic = $basic;

        return $this;
    }

    public function getEven(): array
    {
        return $this->even;
    }

    public function setEven(array $even): static
    {
        $this->even = $even;

        return $this;
    }

    public function getOdd(): array
    {
        return $this->odd;
    }

    public function setOdd(array $odd): static
    {
        $this->odd = $odd;

        return $this;
    }
}
