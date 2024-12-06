<?php

namespace App\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

#[ODM\Document(collection: 'WeatherForecast')]
class WeatherForecast
{
    #[ODM\Id]
    private ?string $id = null;

    #[ODM\Field(type: 'string')]
    private ?string $location = null;

    #[ODM\Field(type: 'date')]
    private ?\DateTime $date = null;

    #[ODM\Field(type: 'collection')]
    private array $forecasts = [];

    public function getId(): ?string
    {
        return $this->id;
    }

    public function getLocation(): ?string
    {
        return $this->location;
    }

    public function setLocation(string $location): self
    {
        $this->location = strtolower($location);
        return $this;
    }

    public function getDate(): ?\DateTime
    {
        return $this->date;
    }

    public function setDate(\DateTime $date): self
    {
        $this->date = $date;
        return $this;
    }

    public function getForecasts(): array
    {
        return $this->forecasts;
    }

    public function setForecasts(array $forecasts): self
    {
        $this->forecasts = $forecasts;
        return $this;
    }
}
