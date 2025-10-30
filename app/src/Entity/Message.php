<?php

namespace App\Entity;

use App\Repository\MessageRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: MessageRepository::class)]
class Message
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'messages')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    #[ORM\Column(type: 'text')]
    private ?string $content = null;

    #[ORM\Column(length: 20)]
    private ?string $role = null; // 'user', 'assistant', 'system'

    #[ORM\Column(length: 20)]
    private ?string $assistantRole = null; // В какой роли был ассистент при этой беседе

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $messageType = null; // 'text', 'initiative', 'command', 'analysis'

    #[ORM\Column(nullable: true)]
    private ?int $telegramMessageId = null;

    #[ORM\Column(nullable: true)]
    private ?int $tokens = null;

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $metadata = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $updatedAt = null;

    // Для инициативных сообщений
    #[ORM\Column(nullable: true)]
    private ?bool $isInitiative = false;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $initiativeTrigger = null; // 'time_based', 'interest_based', 'event_based'

    // Для анализа профиля
    #[ORM\Column(nullable: true)]
    private ?bool $usedForAnalysis = false;

    // Для анализа задач и событий
    #[ORM\Column(nullable: true)]
    private ?bool $usedForTasks = false;

    #[ORM\Column(nullable: true)]
    private ?bool $processed = null;

    #[ORM\Column(nullable: true)]
    private ?int $promptTokens = null;

    #[ORM\Column(nullable: true)]
    private ?int $completionTokens = null;

    #[ORM\Column(nullable: true)]
    private ?int $totalTokens = null;

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $tokenDetails = null;

    public const USER_ROLE =  'user';
    public const ASSISTANT_ROLE =  'assistant';
    public const SYSTEM_ROLE =   'system';


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

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;
        return $this;
    }

    public function getContent(): ?string
    {
        return $this->content;
    }

    public function setContent(string $content): static
    {
        $this->content = $content;
        return $this;
    }

    public function getRole(): ?string
    {
        return $this->role;
    }

    public function setRole(string $role): static
    {
        $this->role = $role;
        return $this;
    }

    public function getAssistantRole(): ?string
    {
        return $this->assistantRole;
    }

    public function setAssistantRole(string $assistantRole): static
    {
        $this->assistantRole = $assistantRole;
        return $this;
    }

    public function getMessageType(): ?string
    {
        return $this->messageType;
    }

    public function setMessageType(?string $messageType): static
    {
        $this->messageType = $messageType;
        return $this;
    }

    public function getTelegramMessageId(): ?int
    {
        return $this->telegramMessageId;
    }

    public function setTelegramMessageId(?int $telegramMessageId): static
    {
        $this->telegramMessageId = $telegramMessageId;
        return $this;
    }

    public function getTokens(): ?int
    {
        return $this->tokens;
    }

    public function setTokens(?int $tokens): static
    {
        $this->tokens = $tokens;
        return $this;
    }


    public function getPromptTokens(): ?int
    {
        return $this->promptTokens;
    }

    public function setPromptTokens(?int $tokens): static
    {
        $this->promptTokens = $tokens;
        return $this;
    }

    public function getCompletionTokens(): ?int
    {
        return $this->completionTokens;
    }

    public function setCompletionTokens(?int $tokens): static
    {
        $this->completionTokens = $tokens;
        return $this;
    }

    public function getTotalTokens(): ?int
    {
        return $this->totalTokens;
    }

    public function setTotalTokens(?int $tokens): static
    {
        $this->totalTokens = $tokens;
        return $this;
    }

    public function getMetadata(): ?array
    {
        return $this->metadata;
    }

    public function setMetadata(?array $metadata): static
    {
        $this->metadata = $metadata;
        return $this;
    }

    public function getTokenDetails(): ?array
    {
        return $this->tokenDetails;
    }

    public function setTokenDetails(?array $tokenDetails): static
    {
        $this->tokenDetails = $tokenDetails;
        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTimeImmutable $updatedAt): static
    {
        $this->updatedAt = $updatedAt;
        return $this;
    }

    public function isIsInitiative(): ?bool
    {
        return $this->isInitiative;
    }

    public function setIsInitiative(?bool $isInitiative): static
    {
        $this->isInitiative = $isInitiative;
        return $this;
    }

    public function getInitiativeTrigger(): ?string
    {
        return $this->initiativeTrigger;
    }

    public function setInitiativeTrigger(?string $initiativeTrigger): static
    {
        $this->initiativeTrigger = $initiativeTrigger;
        return $this;
    }

    public function isUsedForAnalysis(): ?bool
    {
        return $this->usedForAnalysis;
    }

    public function setUsedForAnalysis(?bool $usedForAnalysis): static
    {
        $this->usedForAnalysis = $usedForAnalysis;
        return $this;
    }

    public function isUsedForTasks(): ?bool
    {
        return $this->usedForTasks;
    }

    public function setUsedForTasks(?bool $usedForTasks): static
    {
        $this->usedForTasks = $usedForTasks;
        return $this;
    }




    public function isProcessed(): ?bool
    {
        return $this->processed;
    }

    public function setProcessed(?bool $processed): static
    {
        $this->processed = $processed;

        return $this;
    }
}
