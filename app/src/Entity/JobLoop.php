<?php

namespace App\Entity;

use App\Repository\JobLoopRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: JobLoopRepository::class)]
#[ORM\Table(name: 'job_loops')]
#[ORM\HasLifecycleCallbacks]
class JobLoop
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $command = null;

    #[ORM\Column(options: ['default' => 1])]
    private ?int $sleep = 1;

    #[ORM\Column(options: ['default' => 1])]
    private ?int $maxProcesses = 1;

    #[ORM\Column(options: ['default' => true])]
    private ?bool $isActive = true;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $lastRunAt = null;

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
    public function getCommand(): ?string { return $this->command; }
    public function setCommand(string $command): static { $this->command = $command; return $this; }
    public function getSleep(): ?int { return $this->sleep; }
    public function setSleep(int $sleep): static { $this->sleep = $sleep; return $this; }
    public function getMaxProcesses(): ?int { return $this->maxProcesses; }
    public function setMaxProcesses(int $maxProcesses): static { $this->maxProcesses = $maxProcesses; return $this; }
    public function isActive(): ?bool { return $this->isActive; }
    public function setIsActive(bool $isActive): static { $this->isActive = $isActive; return $this; }
    public function getCreatedAt(): ?\DateTimeImmutable { return $this->createdAt; }
    public function getUpdatedAt(): ?\DateTimeImmutable { return $this->updatedAt; }
    public function getLastRunAt(): ?\DateTimeImmutable { return $this->lastRunAt; }
    public function setLastRunAt(?\DateTimeImmutable $lastRunAt): static { $this->lastRunAt = $lastRunAt; return $this; }
}
