<?php

namespace App\Entity;

use App\Repository\AgentPersonalityRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AgentPersonalityRepository::class)]
#[ORM\Table(name: 'agent_personality')]
class AgentPersonality
{
    // Типы данных
    public const TYPE_TRAIT = 'trait';       // черта характера (доброта, открытость)
    public const TYPE_BELIEF = 'belief';     // убеждение ("я должен помогать")
    public const TYPE_GOAL = 'goal';         // цель ("узнать больше о пользователе")
    public const TYPE_FACT = 'fact';         // факт о себе ("меня создал Николай")
    public const TYPE_NAME = 'name';         // имя агента
    public const TYPE_EMOTION = 'emotion';   // текущее настроение

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 100)]
    private ?string $trait = null;           // ключ: 'name', 'empathy', 'curiosity'

    #[ORM\Column(type: 'float', nullable: true)]
    private ?float $value = null;             // числовое значение (для черт)

    #[ORM\Column(length: 50)]
    private ?string $type = self::TYPE_FACT;  // тип данных

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description = null;      // текстовое описание (для фактов, убеждений)

    #[ORM\Column]
    private ?\DateTimeImmutable $updatedAt = null;

    public function __construct()
    {
        $this->updatedAt = new \DateTimeImmutable();
    }

    // --- Getters and Setters ---

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTrait(): ?string
    {
        return $this->trait;
    }

    public function setTrait(?string $trait): self
    {
        $this->trait = $trait;
        return $this;
    }

    public function getValue(): ?float
    {
        return $this->value;
    }

    public function setValue(?float $value): self
    {
        $this->value = $value;
        return $this;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(string $type): self
    {
        $this->type = $type;
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;
        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTimeImmutable $updatedAt): self
    {
        $this->updatedAt = $updatedAt;
        return $this;
    }

    // Optionally, add a method to update the timestamp before persist
    public function updateTimestamp(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }
}
