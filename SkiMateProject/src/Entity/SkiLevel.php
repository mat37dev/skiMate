<?php

namespace App\Entity;

use App\Repository\SkiLevelRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: SkiLevelRepository::class)]
class SkiLevel
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    /**
     * @var Collection<int, user>
     */
    #[ORM\OneToMany(targetEntity: user::class, mappedBy: 'skiLevel')]
    private Collection $skilevel;

    public function __construct()
    {
        $this->skilevel = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @return Collection<int, user>
     */
    public function getSkilevel(): Collection
    {
        return $this->skilevel;
    }

    public function addSkilevel(user $skilevel): static
    {
        if (!$this->skilevel->contains($skilevel)) {
            $this->skilevel->add($skilevel);
            $skilevel->setSkiLevel($this);
        }

        return $this;
    }

    public function removeSkilevel(user $skilevel): static
    {
        if ($this->skilevel->removeElement($skilevel)) {
            // set the owning side to null (unless already changed)
            if ($skilevel->getSkiLevel() === $this) {
                $skilevel->setSkiLevel(null);
            }
        }

        return $this;
    }
}
