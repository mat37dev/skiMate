<?php

namespace App\Entity;

use App\Repository\RoleRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: RoleRepository::class)]
class Role
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    /**
     * @var Collection<int, user>
     */
    #[ORM\OneToMany(targetEntity: user::class, mappedBy: 'role', orphanRemoval: true)]
    private Collection $role;

    public function __construct()
    {
        $this->role = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return Collection<int, user>
     */
    public function getRole(): Collection
    {
        return $this->role;
    }

    public function addRole(user $role): static
    {
        if (!$this->role->contains($role)) {
            $this->role->add($role);
            $role->setRole($this);
        }

        return $this;
    }

    public function removeRole(user $role): static
    {
        if ($this->role->removeElement($role)) {
            // set the owning side to null (unless already changed)
            if ($role->getRole() === $this) {
                $role->setRole(null);
            }
        }

        return $this;
    }
}
