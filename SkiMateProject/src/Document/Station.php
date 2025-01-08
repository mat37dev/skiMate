<?php

namespace App\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

#[ODM\Document(collection: "stations")]
class Station
{
    #[ODM\Id]
    private $id;

    // Domaine skiable auquel appartient la station (ex: "Paradiski")
    #[ODM\Field(type: "string")]
    private string $domain;

    // Nom de la station (ex: "La Plagne")
    #[ODM\Field(type: "string")]
    private string $name;

    // Géométrie de la station, sous forme GeoJSON (Polygon)
    // ex: { "type": "Polygon", "coordinates": [ [ [lon, lat], ... ] ] }
    #[ODM\Field(type: "hash")]
    private array $geometry = [];

    // Tags OSM bruts de la station
    #[ODM\Field(type: "hash")]
    private array $tags = [];

    // Booléen indiquant si la station a été validée par un admin
    #[ODM\Field(type: "bool")]
    private bool $validated = false;

    // Identifiant OSM du way représentant la station
    #[ODM\Field(type: "string")]
    private string $osmId;

    #[ODM\Field(type: "float")]
    private ?float $latitude = null;

    #[ODM\Field(type: "float")]
    private ?float $longitude = null;

    // Tableau des items (pistes, remontées, POI...) liés à la station
    // Chaque item sera un tableau associatif similaire à un Feature GeoJSON :
    // {
    //   "type": "Item",
    //   "geometry": { "type": "Point|LineString|Polygon", "coordinates": [...] },
    //   "properties": {
    //       "source": "osm",
    //       "category": "piste|lift|restaurant|... ",
    //       "tags": { ... }
    //   },
    //   "adminEdits": {},
    //   "validated": false
    // }
    #[ODM\Field(type: "collection")]
    private $features = [];

    public function getId(): ?string
    {
        return $this->id;
    }

    public function getDomain(): string
    {
        return $this->domain;
    }

    public function setDomain(string $domain): self
    {
        $this->domain = $domain;
        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;
        return $this;
    }

    public function getGeometry(): array
    {
        return $this->geometry;
    }

    public function setGeometry(array $geometry): self
    {
        $this->geometry = $geometry;
        return $this;
    }

    public function getTags(): array
    {
        return $this->tags;
    }

    public function setTags(array $tags): self
    {
        $this->tags = $tags;
        return $this;
    }

    public function isValidated(): bool
    {
        return $this->validated;
    }

    public function setValidated(bool $validated): self
    {
        $this->validated = $validated;
        return $this;
    }

    public function getOsmId(): string
    {
        return $this->osmId;
    }

    public function setOsmId(string $osmId): self
    {
        $this->osmId = $osmId;
        return $this;
    }

    public function getFeatures(): array
    {
        return $this->features;
    }

    public function setFeatures(array $features): self
    {
        $this->features = $features;
        return $this;
    }

    public function getLatitude(): ?float
    {
        return $this->latitude;
    }

    public function setLatitude(?float $latitude): self
    {
        $this->latitude = $latitude;
        return $this;
    }

    public function getLongitude(): ?float
    {
        return $this->longitude;
    }

    public function setLongitude(?float $longitude): self
    {
        $this->longitude = $longitude;
        return $this;
    }
}
