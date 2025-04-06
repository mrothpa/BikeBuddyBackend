<?php

namespace App\Entity;

use App\Repository\UsersRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Uid\UuidV4;

#[ORM\Entity(repositoryClass: UsersRepository::class)]
class Users implements PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    #[Groups(['user_read', 'problem_read', 'solution'])]
    private ?Uuid $id = null;

    #[ORM\Column(length: 255, unique: true)]
    #[Groups(['user_read', 'problem_read', 'solution'])]
    private ?string $email = null;

    #[ORM\Column(length: 255)]
    private ?string $password = null;

    #[ORM\Column(length: 31)]
    #[Groups(['user_read', 'problem_read', 'solution'])]
    private ?string $role = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $created_at = null;

    /**
     * @var Collection<int, Problems>
     */
    #[ORM\OneToMany(targetEntity: Problems::class, mappedBy: 'user_id')]
    private Collection $problems;

    /**
     * @var Collection<int, Solutions>
     */
    #[ORM\OneToMany(targetEntity: Solutions::class, mappedBy: 'user')]
    private Collection $solutions;

    /**
     * @var Collection<int, Upvotes>
     */
    #[ORM\OneToMany(targetEntity: Upvotes::class, mappedBy: 'user')]
    private Collection $upvotes;

    public function __construct()
    {
        $this->id = Uuid::v4();
        $this->problems = new ArrayCollection();
        $this->solutions = new ArrayCollection();
        $this->upvotes = new ArrayCollection();
    }

    public function getId(): ?Uuid
    {
        return $this->id;
    }

    public function setId(Uuid $id): self
    {
        $this->id = $id;
        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;

        return $this;
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    public function getUserIdentifier(): string
    {
        return $this->email;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;

        return $this;
    }

    public function getRole(): ?string
    {
        return $this->role;
    }

    public function setRole(string $role): static
    {
        $this->role = $role;

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
     * @return Collection<int, Problems>
     */
    public function getProblems(): Collection
    {
        return $this->problems;
    }

    public function addProblem(Problems $problem): static
    {
        if (!$this->problems->contains($problem)) {
            $this->problems->add($problem);
            $problem->setUser($this);
        }

        return $this;
    }

    public function removeProblem(Problems $problem): static
    {
        if ($this->problems->removeElement($problem)) {
            // set the owning side to null (unless already changed)
            if ($problem->getUser() === $this) {
                $problem->setUser(null);
            }
        }

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
            $solution->setUser($this);
        }

        return $this;
    }

    public function removeSolution(Solutions $solution): static
    {
        if ($this->solutions->removeElement($solution)) {
            // set the owning side to null (unless already changed)
            if ($solution->getUser() === $this) {
                $solution->setUser(null);
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
            $upvote->setUser($this);
        }

        return $this;
    }

    public function removeUpvote(Upvotes $upvote): static
    {
        if ($this->upvotes->removeElement($upvote)) {
            // set the owning side to null (unless already changed)
            if ($upvote->getUser() === $this) {
                $upvote->setUser(null);
            }
        }

        return $this;
    }
}
