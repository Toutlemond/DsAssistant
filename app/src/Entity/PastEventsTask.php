<?php

namespace App\Entity;

use App\Repository\PastEventsTaskRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PastEventsTaskRepository::class)]
#[ORM\Table(name: 'past_events_tasks')]
#[ORM\HasLifecycleCallbacks]
class PastEventsTask
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    #[ORM\Column(length: 255)]
    private ?string $event = null;

    #[ORM\Column(length: 50)]
    private ?string $category = null; // entertainment, sports, family, hobby

    #[ORM\Column(length: 20)]
    private ?string $interestLevel = null; // high, medium, low

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $originalContext = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $suggestedRemindAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $actualRemindAt = null;

    #[ORM\Column(options: ['default' => false])]
    private ?bool $isProcessed = false;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->isProcessed = false;
    }

    #[ORM\PrePersist]
    public function setSuggestedRemindDate(): void
    {
        if (!$this->suggestedRemindAt) {
            // Автоматически вычисляем дату напоминания на основе категории
            $this->suggestedRemindAt = $this->calculateSuggestedRemindDate();
        }
    }

    private function calculateSuggestedRemindDate(): \DateTimeImmutable
    {
        $interval = match($this->category) {
            'entertainment' => '2 weeks',
            'sports' => '3 weeks',
            'family' => '1 month',
            'hobby' => '2 months',
            default => '1 month'
        };

        return (new \DateTimeImmutable())->modify("+{$interval}");
    }

    // Геттеры и сеттеры
    public function getId(): ?int { return $this->id; }
    public function getUser(): ?User { return $this->user; }
    public function setUser(?User $user): static { $this->user = $user; return $this; }
    public function getEvent(): ?string { return $this->event; }
    public function setEvent(string $event): static { $this->event = $event; return $this; }
    public function getCategory(): ?string { return $this->category; }
    public function setCategory(string $category): static { $this->category = $category; return $this; }
    public function getInterestLevel(): ?string { return $this->interestLevel; }
    public function setInterestLevel(string $interestLevel): static { $this->interestLevel = $interestLevel; return $this; }
    public function getOriginalContext(): ?string { return $this->originalContext; }
    public function setOriginalContext(?string $originalContext): static { $this->originalContext = $originalContext; return $this; }
    public function getCreatedAt(): ?\DateTimeImmutable { return $this->createdAt; }
    public function getSuggestedRemindAt(): ?\DateTimeImmutable { return $this->suggestedRemindAt; }
    public function setSuggestedRemindAt(\DateTimeImmutable $suggestedRemindAt): static { $this->suggestedRemindAt = $suggestedRemindAt; return $this; }
    public function getActualRemindAt(): ?\DateTimeImmutable { return $this->actualRemindAt; }
    public function setActualRemindAt(?\DateTimeImmutable $actualRemindAt): static { $this->actualRemindAt = $actualRemindAt; return $this; }
    public function isProcessed(): ?bool { return $this->isProcessed; }
    public function setIsProcessed(bool $isProcessed): static { $this->isProcessed = $isProcessed; return $this; }

    public function markAsProcessed(): void
    {
        $this->isProcessed = true;
        $this->actualRemindAt = new \DateTimeImmutable();
    }
}
