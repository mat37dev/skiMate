<?php

namespace App\Entity;

use App\Repository\StatisticsRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: StatisticsRepository::class)]
class Statistics
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private ?float $totalDistance = 0;

    #[ORM\Column]
    private ?float $totalHours = 0;

    #[ORM\Column]
    private ?float $totalElevation = 0;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTotalDistance(): ?float
    {
        return $this->totalDistance;
    }

    public function setTotalDistance(float $totalDistance): static
    {
        $this->totalDistance = $totalDistance;

        return $this;
    }

    public function getTotalHours(): ?float
    {
        return $this->totalHours;
    }

    public function setTotalHours(float $totalHours): static
    {
        $this->totalHours = $totalHours;

        return $this;
    }

    public function getTotalElevation(): ?float
    {
        return $this->totalElevation;
    }

    public function setTotalElevation(float $totalElevation): static
    {
        $this->totalElevation = $totalElevation;

        return $this;
    }
}
