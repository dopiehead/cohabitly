<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use App\Repository\FeaturesRepository;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: FeaturesRepository::class)]
#[ORM\Table(name: "feature_lists")]
class Features
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: "integer")]
    private ?int $id = null;

    #[ORM\Column(type: "json")]
    #[Assert\NotBlank]
    private array $more_features = [];

    #[ORM\Column(type: "json", nullable: true)]
    private ?array $bills = [];

    #[ORM\Column(type: "json")]
    #[Assert\NotBlank]
    private array $house_rules = [];

    #[ORM\Column(type: "datetime")]
    private \DateTimeInterface $createdAt;

    #[ORM\ManyToOne(targetEntity: "App\Entity\PropertyList")]
    #[ORM\JoinColumn(nullable: false)]
    private PropertyList $property;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
    }

    // -----------------------
    // Getters and Setters
    // -----------------------

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getMoreFeatures(): array
    {
        return $this->more_features;
    }

    public function setMoreFeatures(array $more_features): self
    {
        $this->more_features = $more_features;
        return $this;
    }

    public function getBills(): ?array
    {
        return $this->bills;
    }

    public function setBills(?array $bills): self
    {
        $this->bills = $bills;
        return $this;
    }

    public function getHouseRules(): array
    {
        return $this->house_rules;
    }

    public function setHouseRules(array $house_rules): self
    {
        $this->house_rules = $house_rules;
        return $this;
    }

    public function getCreatedAt(): \DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeInterface $createdAt): self
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    public function getProperty(): PropertyList
    {
        return $this->property;
    }

    public function setProperty(PropertyList $property): self
    {
        $this->property = $property;
        return $this;
    }
}