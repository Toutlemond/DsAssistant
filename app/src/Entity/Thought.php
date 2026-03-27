<?php

namespace App\Entity;

use App\Repository\ThoughtRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ThoughtRepository::class)]
#[ORM\Table(name: 'thoughts')]
#[ORM\HasLifecycleCallbacks]
class Thought
{
    public const TYPE_INSIGHT = 'insight';
    public const TYPE_HYPOTHESIS = 'hypothesis';
    public const TYPE_PLAN = 'plan';
    public const TYPE_MEMORY = 'memory';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    // К какому пользователю относится мысль (может быть NULL)
    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?User $user = null;

    // Связь с фокусом, который породил эту мысль
    #[ORM\ManyToOne(targetEntity: Focus::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?Focus $focus = null;

    // Текст мысли (результат размышления)
    #[ORM\Column(type: 'text')]
    private ?string $content = null;

    // Тип мысли (insight, hypothesis, plan, memory)
    #[ORM\Column(length: 50, options: ['default' => self::TYPE_INSIGHT])]
    private ?string $type = self::TYPE_INSIGHT;

    // Когда мысль была использована в ответе/инициативе
    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $usedAt = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $prompt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    // Геттеры и сеттеры...

    public function getId(): ?int { return $this->id; }
    public function getUser(): ?User { return $this->user; }
    public function setUser(?User $user): static { $this->user = $user; return $this; }
    public function getFocus(): ?Focus { return $this->focus; }
    public function setFocus(?Focus $focus): static { $this->focus = $focus; return $this; }
    public function getContent(): ?string { return $this->content; }
    public function setContent(string $content): static { $this->content = $content; return $this; }
    public function getType(): ?string { return $this->type; }
    public function setType(string $type): static { $this->type = $type; return $this; }
    public function getUsedAt(): ?\DateTimeImmutable { return $this->usedAt; }
    public function setUsedAt(?\DateTimeImmutable $usedAt): static { $this->usedAt = $usedAt; return $this; }
    public function getCreatedAt(): ?\DateTimeImmutable { return $this->createdAt; }

    public function getPrompt(): ?string
    {
        return $this->prompt;
    }

    public function setPrompt(?string $prompt): static
    {
        $this->prompt = $prompt;

        return $this;
    }
}
