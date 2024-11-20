<?php

namespace App\Entity;

use App\Repository\SkiResortRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: SkiResortRepository::class)]
class SkiResort
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $website = null;

    #[ORM\ManyToOne]
    private ?SkiArea $skiArea = null;

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

    public function getWebsite(): ?string
    {
        return $this->website;
    }

    public function setWebsite(?string $website): static
    {
        $this->website = $website;

        return $this;
    }

    public function getSkiArea(): ?SkiArea
    {
        return $this->skiArea;
    }

    public function setSkiArea(?SkiArea $skiArea): static
    {
        $this->skiArea = $skiArea;

        return $this;
    }
}
