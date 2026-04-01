<?php

namespace App\Entity;

use App\Repository\FocusRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: FocusRepository::class)]
#[ORM\Table(name: 'focus')]
#[ORM\HasLifecycleCallbacks]
class Focus
{
    public const STATUS_NEW = 'new';
    public const STATUS_PENDING = 'pending';
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_DONE = 'done';
    public const STATUS_CANCELLED = 'cancelled';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    // Если фокус связан с конкретным пользователем (может быть NULL для общих мыслей)
    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?User $user = null;

    // Тема размышления (короткое описание)
    #[ORM\Column(length: 255)]
    private ?string $topic = null;

    // Откуда пришёл фокус: 'system', 'user_mention', 'vision', 'internal_thought', и т.д.
    #[ORM\Column(length: 50)]
    private ?string $source = null;

    // Приоритет 1-10 (чем выше, тем важнее)
    #[ORM\Column(type: 'smallint', options: ['default' => 5])]
    private ?int $priority = 5;

    // Статус: pending, processing, done, cancelled
    #[ORM\Column(length: 20, options: ['default' => self::STATUS_NEW])]
    private ?string $status = self::STATUS_NEW;

    // Дополнительные данные в JSON (например, исходный контекст)
    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $context = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $processedAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    // Геттеры и сеттеры...

    public function getId(): ?int { return $this->id; }

    public function getUser(): ?User { return $this->user; }
    public function setUser(?User $user): static { $this->user = $user; return $this; }

    public function getTopic(): ?string { return $this->topic; }
    public function setTopic(string $topic): static { $this->topic = $topic; return $this; }

    public function getSource(): ?string { return $this->source; }
    public function setSource(string $source): static { $this->source = $source; return $this; }

    public function getPriority(): ?int { return $this->priority; }
    public function setPriority(int $priority): static { $this->priority = $priority; return $this; }

    public function getStatus(): ?string { return $this->status; }
    public function setStatus(string $status): static { $this->status = $status; return $this; }

    public function getContext(): ?array { return $this->context; }
    public function setContext(?array $context): static { $this->context = $context; return $this; }

    public function getCreatedAt(): ?\DateTimeImmutable { return $this->createdAt; }
    public function getProcessedAt(): ?\DateTimeImmutable { return $this->processedAt; }
    public function setProcessedAt(?\DateTimeImmutable $processedAt): static { $this->processedAt = $processedAt; return $this; }
}
