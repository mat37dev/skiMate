<?php

namespace App\Entity;

use App\Repository\RecordedTrackRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: RecordedTrackRepository::class)]
class RecordedTrack
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(nullable: true)]
    private ?float $distance = null;

    #[ORM\Column(nullable: true)]
    private ?float $elevation = null;

    #[ORM\Column(nullable: true)]
    private ?float $averageSpeed = null;

    #[ORM\Column(nullable: true)]
    private ?float $maximumSpeed = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $trackType = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $weather = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDistance(): ?float
    {
        return $this->distance;
    }

    public function setDistance(?float $distance): static
    {
        $this->distance = $distance;

        return $this;
    }

    public function getElevation(): ?float
    {
        return $this->elevation;
    }

    public function setElevation(?float $elevation): static
    {
        $this->elevation = $elevation;

        return $this;
    }

    public function getAverageSpeed(): ?float
    {
        return $this->averageSpeed;
    }

    public function setAverageSpeed(?float $averageSpeed): static
    {
        $this->averageSpeed = $averageSpeed;

        return $this;
    }

    public function getMaximumSpeed(): ?float
    {
        return $this->maximumSpeed;
    }

    public function setMaximumSpeed(?float $maximumSpeed): static
    {
        $this->maximumSpeed = $maximumSpeed;

        return $this;
    }

    public function getTrackType(): ?string
    {
        return $this->trackType;
    }

    public function setTrackType(?string $trackType): static
    {
        $this->trackType = $trackType;

        return $this;
    }

    public function getWeather(): ?string
    {
        return $this->weather;
    }

    public function setWeather(?string $weather): static
    {
        $this->weather = $weather;

        return $this;
    }
}
