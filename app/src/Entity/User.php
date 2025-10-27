<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: UserRepository::class)]
class User
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private ?int $chatId = null;

    #[ORM\Column(length: 255)]
    private ?string $username = null;

    #[ORM\Column(length: 255)]
    private ?string $firstName = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $lastName = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $gender = null;

    #[ORM\Column(nullable: true)]
    private ?int $age = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $phone = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $state = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\OneToMany(targetEntity: Message::class, mappedBy: 'user')]
    private Collection $messages;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $aiRole = null;

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $interests = null;

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $personalityTraits = null;

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $conversationStats = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $lastAnalysisAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $lastInitiativeAt = null;


    public function __construct()
    {
        $this->messages = new ArrayCollection();
        // ... остальной код конструктора
    }

    /**
     * @return Collection<int, Message>
     */
    public function getMessages(): Collection
    {
        return $this->messages;
    }

    public function getUserContext()
    {
       return [
            'first_name' => $this->getFirstName(),
            'age' => $this->getAge(),
            'gender' => $this->getGender(),
            'interests' => $this->getInterests()
        ];
    }
    public function addMessage(Message $message): static
    {
        if (!$this->messages->contains($message)) {
            $this->messages->add($message);
            $message->setUser($this);
        }

        return $this;
    }

    public function removeMessage(Message $message): static
    {
        if ($this->messages->removeElement($message)) {
            // set the owning side to null (unless already changed)
            if ($message->getUser() === $this) {
                $message->setUser(null);
            }
        }

        return $this;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getChatId(): ?int
    {
        return $this->chatId;
    }

    public function setChatId(int $chatId): static
    {
        $this->chatId = $chatId;

        return $this;
    }

    public function getUsername(): ?string
    {
        return $this->username;
    }

    public function setUsername(string $username): static
    {
        $this->username = $username;

        return $this;
    }

    public function getFirstName(): ?string
    {
        return $this->firstName;
    }

    public function setFirstName(string $firstName): static
    {
        $this->firstName = $firstName;

        return $this;
    }

    public function getLastName(): ?string
    {
        return $this->lastName;
    }

    public function setLastName(?string $lastName): static
    {
        $this->lastName = $lastName;

        return $this;
    }

    public function getGender(): ?string
    {
        return $this->gender;
    }

    public function setGender(?string $gender): static
    {
        $this->gender = $gender;

        return $this;
    }

    public function getAge(): ?int
    {
        return $this->age;
    }

    public function setAge(?int $age): static
    {
        $this->age = $age;

        return $this;
    }

    public function getPhone(): ?string
    {
        return $this->phone;
    }

    public function setPhone(?string $phone): static
    {
        $this->phone = $phone;

        return $this;
    }

    public function getState(): ?string
    {
        return $this->state;
    }

    public function setState(?string $state): static
    {
        $this->state = $state;

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

    public function getAiRole(): ?string
    {
        return $this->aiRole;
    }

    public function setAiRole(?string $aiRole): static
    {
        $this->aiRole = $aiRole;

        return $this;
    }

    public function getInterests(): ?array
    {
        return $this->interests ?? [];
    }

    public function setInterests(?array $interests): static
    {
        $this->interests = $interests;
        return $this;
    }

    public function addInterest(string $interest): static
    {
        if (!in_array($interest, $this->getInterests(), true)) {
            $this->interests[] = $interest;
        }
        return $this;
    }

    public function getPersonalityTraits(): ?array
    {
        return $this->personalityTraits ?? [];
    }

    public function setPersonalityTraits(?array $personalityTraits): static
    {
        $this->personalityTraits = $personalityTraits;
        return $this;
    }

    public function getConversationStats(): ?array
    {
        return $this->conversationStats ?? [
            'total_messages' => 0,
            'engagement_score' => 0,
            'favorite_topics' => []
        ];
    }

    public function setConversationStats(?array $conversationStats): static
    {
        $this->conversationStats = $conversationStats;
        return $this;
    }

    public function getLastAnalysisAt(): ?\DateTimeImmutable
    {
        return $this->lastAnalysisAt;
    }

    public function setLastAnalysisAt(?\DateTimeImmutable $lastAnalysisAt): static
    {
        $this->lastAnalysisAt = $lastAnalysisAt;
        return $this;
    }

    public function getLastInitiativeAt(): ?\DateTimeImmutable
    {
        return $this->lastInitiativeAt;
    }

    public function setLastInitiativeAt(?\DateTimeImmutable $lastInitiativeAt): static
    {
        $this->lastInitiativeAt = $lastInitiativeAt;
        return $this;
    }

}
