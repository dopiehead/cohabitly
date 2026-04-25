<?php

namespace App\Entity;

use App\Repository\UserNotificationRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: UserNotificationRepository::class)]
#[ORM\Table(name: 'user_notification')]
class UserNotification
{
    public const TYPE_APPLICATION_RECEIVED = 'application_received';
    public const TYPE_APPLICATION_ACCEPTED = 'application_accepted';
    public const TYPE_APPLICATION_REJECTED = 'application_rejected';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private User $user;

    #[ORM\Column(length: 50)]
    private string $type;

    #[ORM\Column(type: 'json')]
    private array $payload = [];

    #[ORM\Column(type: 'boolean')]
    private bool $read = false;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int { return $this->id; }

    public function getUser(): User { return $this->user; }
    public function setUser(User $user): static { $this->user = $user; return $this; }

    public function getType(): string { return $this->type; }
    public function setType(string $type): static { $this->type = $type; return $this; }

    public function getPayload(): array { return $this->payload; }
    public function setPayload(array $payload): static { $this->payload = $payload; return $this; }

    public function isRead(): bool { return $this->read; }
    public function markAsRead(): static { $this->read = true; return $this; }

    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }
}
