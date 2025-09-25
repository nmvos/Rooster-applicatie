<?php

namespace App\Entity;

use App\Repository\NotificationsRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: NotificationsRepository::class)]
class Notifications
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'notifications')]
    #[ORM\JoinColumn(nullable: false)]
    private ?user $user = null;

    #[ORM\Column]
    private array $message = [];

    #[ORM\Column(nullable: true)]
    private ?bool $isSeen = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): ?user
    {
        return $this->user;
    }

    public function setUser(?user $user): static
    {
        $this->user = $user;

        return $this;
    }

    public function getMessage(): array
    {
        return $this->message;
    }

    public function setMessage(array $message): static
    {
        $this->message = $message;

        return $this;
    }

    public function isSeen(): ?bool
    {
        return $this->isSeen;
    }

    public function setSeen(?bool $isSeen): static
    {
        $this->isSeen = $isSeen;

        return $this;
    }
}
