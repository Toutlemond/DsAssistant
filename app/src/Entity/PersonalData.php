<?php

namespace App\Entity;

use App\Repository\PersonalDataRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PersonalDataRepository::class)]
#[ORM\Table(name: 'personal_data')]
#[ORM\HasLifecycleCallbacks]
class PersonalData
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'personalData')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    #[ORM\Column(length: 50)]
    private ?string $type = null; // reminder, future_event, person, pet, location, preference, important_date

    #[ORM\Column(length: 255)]
    private ?string $data_key = null;

    #[ORM\Column(type: 'text')]
    private ?string $value = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $eventDate = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $updatedAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
    }

    #[ORM\PreUpdate]
    public function updateTimestamps(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }

    // Геттеры и сеттеры для всех полей...
    public function getId(): ?int { return $this->id; }
    public function getUser(): ?User { return $this->user; }
    public function setUser(?User $user): static { $this->user = $user; return $this; }
    public function getType(): ?string { return $this->type; }
    public function setType(string $type): static { $this->type = $type; return $this; }
    public function getDataKey(): ?string { return $this->data_key; }
    public function setDataKey(string $data_key): static { $this->data_key = $data_key; return $this; }
    public function getValue(): ?string { return $this->value; }
    public function setValue(string $value): static { $this->value = $value; return $this; }
    public function getPriority(): ?string { return $this->priority; }
    public function setPriority(?string $priority): static { $this->priority = $priority; return $this; }
    public function getEventDate(): ?\DateTimeImmutable { return $this->eventDate; }
    public function setEventDate(?\DateTimeImmutable $eventDate): static { $this->eventDate = $eventDate; return $this; }
    public function getCreatedAt(): ?\DateTimeImmutable { return $this->createdAt; }
    public function getUpdatedAt(): ?\DateTimeImmutable { return $this->updatedAt; }
}
