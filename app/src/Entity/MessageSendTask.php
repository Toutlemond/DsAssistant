<?php

namespace App\Entity;

use App\Repository\MessageSendTaskRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: MessageSendTaskRepository::class)]
#[ORM\Table(name: 'message_send_tasks')]
#[ORM\HasLifecycleCallbacks]
class MessageSendTask
{
    const STATUS_PENDING = 0;
    const STATUS_SENT = 1;
    const STATUS_FAILED = 2;
    const STATUS_CANCELLED = 3;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    #[ORM\Column(type: 'text')]
    private ?string $text = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $sendAt = null;

    #[ORM\Column(options: ['default' => 0])]
    private ?int $status = 0;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $sentAt = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $errorMessage = null;

    #[ORM\Column(nullable: true)]
    private ?int $retryCount = 0;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->status = self::STATUS_PENDING;
    }

    // Геттеры и сеттеры для всех полей...
    public function getId(): ?int { return $this->id; }
    public function getUser(): ?User { return $this->user; }
    public function setUser(?User $user): static { $this->user = $user; return $this; }
    public function getText(): ?string { return $this->text; }
    public function setText(string $text): static { $this->text = $text; return $this; }
    public function getSendAt(): ?\DateTimeImmutable { return $this->sendAt; }
    public function setSendAt(\DateTimeImmutable $sendAt): static { $this->sendAt = $sendAt; return $this; }
    public function getStatus(): ?int { return $this->status; }
    public function setStatus(int $status): static { $this->status = $status; return $this; }
    public function getCreatedAt(): ?\DateTimeImmutable { return $this->createdAt; }
    public function getSentAt(): ?\DateTimeImmutable { return $this->sentAt; }
    public function setSentAt(?\DateTimeImmutable $sentAt): static { $this->sentAt = $sentAt; return $this; }
    public function getErrorMessage(): ?string { return $this->errorMessage; }
    public function setErrorMessage(?string $errorMessage): static { $this->errorMessage = $errorMessage; return $this; }
    public function getRetryCount(): ?int { return $this->retryCount; }
    public function setRetryCount(?int $retryCount): static { $this->retryCount = $retryCount; return $this; }

    public function markAsSent(): void
    {
        $this->status = self::STATUS_SENT;
        $this->sentAt = new \DateTimeImmutable();
    }

    public function markAsFailed(string $error): void
    {
        $this->status = self::STATUS_FAILED;
        $this->errorMessage = $error;
        $this->retryCount++;
    }
}
