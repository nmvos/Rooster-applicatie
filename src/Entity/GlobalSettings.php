<?php

namespace App\Entity;

use App\Repository\GlobalSettingsRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: GlobalSettingsRepository::class)]
class GlobalSettings
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private array $departments = [];

    #[ORM\Column]
    private array $colors = [];

    #[ORM\Column(nullable: true)]
    private ?array $departmentColor = null;

    #[ORM\Column(nullable: true)]
    private ?array $signOff = null;

    #[ORM\Column(nullable: true)]
    private ?int $AVD = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDepartments(): array
    {
        return $this->departments;
    }

    public function setDepartments(array $departments): static
    {
        $this->departments = $departments;

        return $this;
    }

    public function getColors(): array
    {
        return $this->colors;
    }

    public function setColors(array $colors): static
    {
        $this->colors = $colors;

        return $this;
    }

    public function getDepartmentColor(): ?array
    {
        return $this->departmentColor;
    }

    public function setDepartmentColor(?array $departmentColor): static
    {
        $this->departmentColor = $departmentColor;

        return $this;
    }

    public function getSignOff(): ?array
    {
        return $this->signOff;
    }

    public function setSignOff(?array $signOff): static
    {
        $this->signOff = $signOff;

        return $this;
    }

    public function getAVD(): ?int
    {
        return $this->AVD;
    }

    public function setAVD(?int $AVD): static
    {
        $this->AVD = $AVD;

        return $this;
    }
}
