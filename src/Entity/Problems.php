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
    #[Groups(['problem_read', 'solution', 'problem_read_admin'])]
    private ?Uuid $id = null;

    #[ORM\ManyToOne(inversedBy: 'problems')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['problem_read', 'problem_read_admin'])]
    private ?Users $user = null;

    #[ORM\Column(length: 255)]
    #[Groups(['problem_read', 'solution', 'problem_read_admin'])]
    private ?string $title = null;

    #[ORM\Column(type: Types::TEXT)]
    #[Groups(['problem_read', 'solution', 'problem_read_admin'])]
    private ?string $description = null;

    #[ORM\Column]
    #[Groups(['problem_read', 'problem_read_admin'])]
    private ?float $latitude = null;

    #[ORM\Column]
    #[Groups(['problem_read', 'problem_read_admin'])]
    private ?float $longitude = null;

    #[ORM\Column(length: 31)]
    #[Groups(['problem_read', 'problem_read_admin'])]
    private ?string $category = null;

    #[ORM\Column(length: 31)]
    #[Groups(['problem_read', 'problem_read_admin'])]
    private ?string $status = null;

    #[ORM\Column]
    #[Groups(['problem_read', 'problem_read_admin'])]
    private ?\DateTimeImmutable $created_at = null;

    /**
     * @var Collection<int, Solutions>
     */
    #[ORM\OneToMany(targetEntity: Solutions::class, mappedBy: 'problem')]
    private Collection $solutions;

    /**
     * @var Collection<int, Upvotes>
     */
    #[ORM\OneToMany(targetEntity: Upvotes::class, mappedBy: 'problem')]
    private Collection $upvotes;

    #[ORM\Column(nullable: true)]
    #[Groups(['problem_read', 'problem_read_admin'])]
    private ?int $upvotes_int = null;

    public function __construct()
    {
        $this->id = Uuid::v4();
        $this->solutions = new ArrayCollection();
        $this->upvotes = new ArrayCollection();
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

    /**
     * @return Collection<int, Upvotes>
     */
    public function getUpvotes(): Collection
    {
        return $this->upvotes;
    }

    public function addUpvote(Upvotes $upvote): static
    {
        if (!$this->upvotes->contains($upvote)) {
            $this->upvotes->add($upvote);
            $upvote->setProblem($this);
        }

        return $this;
    }

    public function removeUpvote(Upvotes $upvote): static
    {
        if ($this->upvotes->removeElement($upvote)) {
            // set the owning side to null (unless already changed)
            if ($upvote->getProblem() === $this) {
                $upvote->setProblem(null);
            }
        }

        return $this;
    }

    public function getUpvotesInt(): ?int
    {
        return $this->upvotes_int;
    }

    public function setUpvotesInt(?int $upvotes_int): static
    {
        $this->upvotes_int = $upvotes_int;

        return $this;
    }
}
