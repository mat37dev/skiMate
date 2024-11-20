<?php

namespace App\Entity;

use App\Repository\SkiSlopeRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: SkiSlopeRepository::class)]
class SkiSlope
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    /**
     * @var Collection<int, SkiResort>
     */
    #[ORM\ManyToMany(targetEntity: SkiResort::class)]
    private Collection $skiResort;

    #[ORM\Column(type: 'string', enumType: SkiSlopeLevel::class)]
    private SkiSlopeLevel $level;

    public function __construct()
    {
        $this->skiResort = new ArrayCollection();
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
     * @return Collection<int, SkiResort>
     */
    public function getSkiResort(): Collection
    {
        return $this->skiResort;
    }

    public function addSkiResort(SkiResort $skiResort): static
    {
        if (!$this->skiResort->contains($skiResort)) {
            $this->skiResort->add($skiResort);
        }

        return $this;
    }

    public function removeSkiResort(SkiResort $skiResort): static
    {
        $this->skiResort->removeElement($skiResort);

        return $this;
    }

    public function getLevel(): SkiSlopeLevel
    {
        return $this->level;
    }

    public function setLevel(SkiSlopeLevel $level): void
    {
        $this->level = $level;
    }
}
