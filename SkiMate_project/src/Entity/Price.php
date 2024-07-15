<?php

namespace App\Entity;

use App\Repository\PriceRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PriceRepository::class)]
class Price
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $PassType = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPassType(): ?string
    {
        return $this->PassType;
    }

    public function setPassType(string $PassType): static
    {
        $this->PassType = $PassType;

        return $this;
    }
}
