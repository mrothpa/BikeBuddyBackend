<?php

namespace App\Entity;

use App\Repository\SolutionsRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: SolutionsRepository::class)]
class Solutions
{
    #[ORM\Id]
    #[ORM\Column(type: UuidType::NAME, unique: true)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: 'doctrine.uuid_generator')]
    #[Groups(['solution'])]
    private ?Uuid $id = null;

    #[ORM\ManyToOne(inversedBy: 'solution')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['solution'])]
    private ?Users $user = null;

    #[ORM\ManyToOne(inversedBy: 'solution')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['solution'])]
    private ?Problems $problem = null;

    #[ORM\Column(type: Types::TEXT)]
    #[Groups(['solution'])]
    private ?string $description = null;

    #[ORM\Column]
    // #[Groups(['solutions'])]
    private ?\DateTimeImmutable $created_at = null;

    public function getId(): ?Uuid
    {
        return $this->id;
    }

    public function getUser(): ?Users
    {
        return $this->user;
    }

    public function setUser(?Users $user): static
    {
        $this->user = $user;

        return $this;
    }

    public function getProblem(): ?Problems
    {
        return $this->problem;
    }

    public function setProblem(?Problems $problem): static
    {
        $this->problem = $problem;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->created_at;
    }

    public function setCreatedAt(\DateTimeImmutable $created_at): static
    {
        $this->created_at = $created_at;

        return $this;
    }
}
