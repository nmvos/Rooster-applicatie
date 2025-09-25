<?php

namespace App\Entity;

use App\Repository\ProjectsRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ProjectsRepository::class)]
class Projects
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column]
    private ?int $startWeek = null;

    #[ORM\Column(nullable: true)]
    private ?int $endWeek = null;

    #[ORM\Column]
    private ?bool $Active = null;

    #[ORM\Column]
    private ?int $startYear = null;

    #[ORM\Column(nullable: true)]
    private ?int $endYear = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getStartWeek(): ?int
    {
        return $this->startWeek;
    }

    public function setStartWeek(int $startWeek): static
    {
        $this->startWeek = $startWeek;

        return $this;
    }

    public function getEndWeek(): ?int
    {
        return $this->endWeek;
    }

    public function setEndWeek(?int $endWeek): static
    {
        $this->endWeek = $endWeek;

        return $this;
    }


    public function isActive(): ?bool
    {
        return $this->Active;
    }

    public function setActive(bool $Active): static
    {
        $this->Active = $Active;

        return $this;
    }

    public function getStartYear(): ?int
    {
        return $this->startYear;
    }

    public function setStartYear(int $startYear): static
    {
        $this->startYear = $startYear;

        return $this;
    }

    public function getEndYear(): ?int
    {
        return $this->endYear;
    }

    public function setEndYear(?int $endYear): static
    {
        $this->endYear = $endYear;

        return $this;
    }

}
