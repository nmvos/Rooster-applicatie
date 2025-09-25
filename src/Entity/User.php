<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use App\Repository\UserRepository;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Mapping\ClassMetadata;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: UserRepository::class)]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 255)]
    private ?string $username = null;

    #[ORM\Column(type: 'string', length: 255)]
    private ?string $password = null;

    #[ORM\OneToOne(targetEntity: Profile::class, mappedBy: 'user', cascade: ['persist', 'remove'])]
    private ?Profile $profile = null;

    #[ORM\Column(type: 'json')]
    private array $roles = [];

    private ?string $plainPassword = null;

    #[ORM\Column(nullable: true)]
    private ?bool $passwordReset = null;

    #[ORM\Column(length: 255)]
    private ?string $location = null;

    /**
     * @var Collection<int, Schedule>
     */
    #[ORM\OneToMany(targetEntity: Schedule::class, mappedBy: 'user', orphanRemoval: true, cascade: ["persist", 'remove'])]
    private Collection $schedules;

    /**
     * @var Collection<int, Notifications>
     */
    #[ORM\OneToMany(targetEntity: Notifications::class, mappedBy: 'user', orphanRemoval: true, cascade: ["persist", 'remove'])]
    private Collection $notifications;

    public function __construct()
    {
        $this->schedules = new ArrayCollection();
        $this->notifications = new ArrayCollection();
    }



    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUsername(): ?string
    {
        return $this->username;
    }

    public function setUsername(string $username): self
    {
        $this->username = $username;
        return $this;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): self
    {
        $this->password = $password;
        return $this;
    }

    public function getProfile(): ?Profile
    {
        return $this->profile;
    }

    public function setProfile(Profile $profile): self
    {
        // set the owning side of the relation if necessary
        if ($profile->getUser() !== $this) {
            $profile->setUser($this);
        }

        $this->profile = $profile;

        return $this;
    }

    public function getSalt(): ?string
    {
        return null; // Not needed for bcrypt or argon2i
    }

    public function eraseCredentials(): void
    {
        $this->plainPassword = null; // If you store any sensitive information temporarily, clear it here
    }

    public function getUserIdentifier(): string
    {
        return (string) $this->username;
    }

    // src/Entity/User.php
    public function getRoles(): array
    {
        $roles = $this->roles;

        // Garandeer dat elke gebruiker minimaal de ROLE_USER heeft
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }


    public function setRoles(array $roles): self
    {
        $this->roles = $roles;
        return $this;
    }

    public function getPlainPassword(): ?string
    {
        return $this->plainPassword;
    }

    public function setPlainPassword(?string $plainPassword): self
    {
        $this->plainPassword = $plainPassword;
        return $this;
    }

    public function isPasswordReset(): ?bool
    {
        return $this->passwordReset;
    }

    public function setPasswordReset(?bool $passwordReset): static
    {
        $this->passwordReset = $passwordReset;

        return $this;
    }

    public function getLocation(): ?string
    {
        return $this->location;
    }

    public function setLocation(string $location): static
    {
        $this->location = $location;

        return $this;
    }

    /**
     * @return Collection<int, Schedule>
     */
    public function getSchedules(): Collection
    {
        return $this->schedules;
    }

    public function addSchedule(Schedule $schedule): static
    {
        if (!$this->schedules->contains($schedule)) {
            $this->schedules->add($schedule);
            $schedule->setUser($this);
        }

        return $this;
    }

    public function removeSchedule(Schedule $schedule): static
    {
        if ($this->schedules->removeElement($schedule)) {
            // set the owning side to null (unless already changed)
            if ($schedule->getUser() === $this) {
                $schedule->setUser(null);
            }
        }

        return $this;
    }

    public static function loadValidatorMetadata(ClassMetadata $metadata): void
    {
        $metadata->addConstraint(new UniqueEntity([
            'fields' => 'username',
        ]));
    }

    /**
     * @return Collection<int, Notifications>
     */
    public function getNotifications(): Collection
    {
        return $this->notifications;
    }

    public function addNotification(Notifications $notification): static
    {
        if (!$this->notifications->contains($notification)) {
            $this->notifications->add($notification);
            $notification->setUser($this);
        }

        return $this;
    }

    public function removeNotification(Notifications $notification): static
    {
        if ($this->notifications->removeElement($notification)) {
            // set the owning side to null (unless already changed)
            if ($notification->getUser() === $this) {
                $notification->setUser(null);
            }
        }

        return $this;
    }

}
