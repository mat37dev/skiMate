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
    private ?float $average_speed = null;

    #[ORM\Column(nullable: true)]
    private ?float $maximum_speed = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $track_type = null;

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
        return $this->average_speed;
    }

    public function setAverageSpeed(?float $average_speed): static
    {
        $this->average_speed = $average_speed;

        return $this;
    }

    public function getMaximumSpeed(): ?float
    {
        return $this->maximum_speed;
    }

    public function setMaximumSpeed(?float $maximum_speed): static
    {
        $this->maximum_speed = $maximum_speed;

        return $this;
    }

    public function getTrackType(): ?string
    {
        return $this->track_type;
    }

    public function setTrackType(?string $track_type): static
    {
        $this->track_type = $track_type;

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
