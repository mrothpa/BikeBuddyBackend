<?php

namespace App\Entity;

use App\Repository\ProblemsRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\Serializer\Annotation\Groups;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Uid\UuidV4;

#[ORM\Entity(repositoryClass: ProblemsRepository::class)]
class Problems
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    #[Groups(['problem_read', 'solution'])]
    private ?Uuid $id = null;

    #[ORM\ManyToOne(inversedBy: 'problems')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['problem_read'])]
    private ?Users $user = null;

    #[ORM\Column(length: 255)]
    #[Groups(['problem_read', 'solution'])]
    private ?string $title = null;

    #[ORM\Column(type: Types::TEXT)]
    #[Groups(['problem_read', 'solution'])]
    private ?string $description = null;

    #[ORM\Column]
    #[Groups(['problem_read'])]
    private ?float $latitude = null;

    #[ORM\Column]
    #[Groups(['problem_read'])]
    private ?float $longitude = null;

    #[ORM\Column(length: 31)]
    #[Groups(['problem_read'])]
    private ?string $category = null;

    #[ORM\Column(length: 31)]
    #[Groups(['problem_read'])]
    private ?string $status = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $created_at = null;

    /**
     * @var Collection<int, Solutions>
     */
    #[ORM\OneToMany(targetEntity: Solutions::class, mappedBy: 'problem')]
    private Collection $solutions;

    public function __construct()
    {
        $this->id = Uuid::v4();
        $this->solutions = new ArrayCollection();
    }

    public function getId(): ?Uuid
    {
        return $this->id;
    }

    public function getUser(): ?Users
    {
        return $this->user;
    }

    public function setUser(?Users $user): self
    {
        $this->user = $user;

        return $this;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): static
    {
        $this->title = $title;

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

    public function getLatitude(): ?float
    {
        return $this->latitude;
    }

    public function setLatitude(float $latitude): static
    {
        $this->latitude = $latitude;

        return $this;
    }

    public function getLongitude(): ?float
    {
        return $this->longitude;
    }

    public function setLongitude(float $longitude): static
    {
        $this->longitude = $longitude;

        return $this;
    }

    public function getCategory(): ?string
    {
        return $this->category;
    }

    public function setCategory(string $category): static
    {
        $this->category = $category;

        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;

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

    /**
     * @return Collection<int, Solutions>
     */
    public function getSolutions(): Collection
    {
        return $this->solutions;
    }

    public function addSolution(Solutions $solution): static
    {
        if (!$this->solutions->contains($solution)) {
            $this->solutions->add($solution);
            $solution->setProblem($this);
        }

        return $this;
    }

    public function removeSolution(Solutions $solution): static
    {
        if ($this->solutions->removeElement($solution)) {
            // set the owning side to null (unless already changed)
            if ($solution->getProblem() === $this) {
                $solution->setProblem(null);
            }
        }

        return $this;
    }
}
