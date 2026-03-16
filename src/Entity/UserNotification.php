<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'user_notification')]
class UserNotification
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(name: 'sender_id', type: 'integer')]
    private int $senderId;

    #[ORM\Column(name: 'recipient_id', type: 'integer')]
    private int $recipientId;

    #[ORM\Column(type: 'string', length: 255)]
    private string $message;

    #[ORM\Column(type: 'boolean')]
    private bool $pending = true;

    #[ORM\Column(type: 'datetime')]
    private \DateTimeInterface $date;

    public function __construct()
    {
        $this->date = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSenderId(): int
    {
        return $this->senderId;
    }

    public function setSenderId(int $senderId): self
    {
        $this->senderId = $senderId;
        return $this;
    }

    public function getRecipientId(): int
    {
        return $this->recipientId;
    }

    public function setRecipientId(int $recipientId): self
    {
        $this->recipientId = $recipientId;
        return $this;
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    public function setMessage(string $message): self
    {
        $this->message = $message;
        return $this;
    }

    public function isPending(): bool
    {
        return $this->pending;
    }

    public function setPending(bool $pending): self
    {
        $this->pending = $pending;
        return $this;
    }

    public function getDate(): \DateTimeInterface
    {
        return $this->date;
    }
}
