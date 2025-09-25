<?php

namespace App\Entity;

use App\Repository\DepartmentLimitRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: DepartmentLimitRepository::class)]
class DepartmentLimit
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $department = null;

    #[ORM\Column]
    private ?int $maxEmployees = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDepartment(): ?string
    {
        return $this->department;
    }

    public function setDepartment(string $department): static
    {
        $this->department = $department;

        return $this;
    }

    public function getMaxEmployees(): int
    {
        return $this->maxEmployees;
    }

    public function setMaxEmployees(int $maxEmployees): self
    {
        $this->maxEmployees = $maxEmployees;
        return $this;
    }
}
