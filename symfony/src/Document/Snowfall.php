<?php
namespace App\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;

#[MongoDB\Document]
class Snowfall
{
    #[MongoDB\Id]
    private string $id;

    #[MongoDB\Field(type: "string")]
    private string $location;

    #[MongoDB\Field(type: "date")]
    private \DateTime $lastSnowfallDate;

    #[MongoDB\Field(type: "float", nullable: true)]
    private ?float $snowfallAmount = null;

    // Getters et setters
    public function getId(): string
    {
        return $this->id;
    }

    public function getLocation(): string
    {
        return $this->location;
    }

    public function setLocation(string $location): self
    {
        $this->location = strtolower($location); // Enregistrer en lowercase
        return $this;
    }

    public function getLastSnowfallDate(): \DateTime
    {
        return $this->lastSnowfallDate;
    }

    public function setLastSnowfallDate(\DateTime $lastSnowfallDate): self
    {
        $this->lastSnowfallDate = $lastSnowfallDate;
        return $this;
    }

    public function getSnowfallAmount(): ?float
    {
        return $this->snowfallAmount;
    }

    public function setSnowfallAmount(?float $snowfallAmount): self
    {
        $this->snowfallAmount = $snowfallAmount;
        return $this;
    }
}
