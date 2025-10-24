<?php

namespace App\Entity;

use App\Repository\UserLogRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: UserLogRepository::class)]
class UserLog
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private ?int $UserId = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $create_at = null;

    #[ORM\Column]
    private ?bool $from_user = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $log = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUserId(): ?int
    {
        return $this->UserId;
    }

    public function setUserId(int $UserId): static
    {
        $this->UserId = $UserId;

        return $this;
    }

    public function getCreateAt(): ?\DateTimeImmutable
    {
        return $this->create_at;
    }

    public function setCreateAt(\DateTimeImmutable $create_at): static
    {
        $this->create_at = $create_at;

        return $this;
    }

    public function isFromUser(): ?bool
    {
        return $this->from_user;
    }

    public function setFromUser(bool $from_user): static
    {
        $this->from_user = $from_user;

        return $this;
    }

    public function getLog(): ?string
    {
        return $this->log;
    }

    public function setLog(?string $log): static
    {
        $this->log = $log;

        return $this;
    }
}
