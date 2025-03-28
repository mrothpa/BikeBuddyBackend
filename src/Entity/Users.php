<?php

namespace App\Entity;

use App\Repository\UsersRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: UsersRepository::class)]
class Users
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]

    #[Groups(['problem_read'])]
    private ?int $id = null;

    #[ORM\Column(length: 255, unique: true)]
    #[Groups(['user:read', 'problem_read'])]
    private ?string $email = null;

    #[ORM\Column(length: 255)]
    private ?string $password = null;

    #[ORM\Column(length: 31)]
    #[Groups(['user:read', 'problem_read'])]
    private ?string $role = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $created_at = null;

    /**
     * @var Collection<int, Problems>
     */
    #[ORM\OneToMany(targetEntity: Problems::class, mappedBy: 'user_id')]
    private Collection $problems;

    public function __construct()
    {
        $this->problems = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
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

    public function getPassword(): ?string
    {
        return $this->password;
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
            $problem->setUserId($this);
        }

        return $this;
    }

    public function removeProblem(Problems $problem): static
    {
        if ($this->problems->removeElement($problem)) {
            // set the owning side to null (unless already changed)
            if ($problem->getUserId() === $this) {
                $problem->setUserId(null);
            }
        }

        return $this;
    }
}
