<?php

namespace App\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Symfony\Component\Validator\Constraints as Assert;

#[ODM\Document(collection: "stations")]
class Station
{
    #[ODM\Id]
    private $id;

    // Domaine skiable auquel appartient la station (ex: "Paradiski")
    #[ODM\Field(type: "string")]
    #[Assert\NotBlank(message: "Le nom du domaine ne peut être vide.")]
    private string $domain;

    // Nom de la station (ex: "La Plagne")
    #[ODM\Field(type: "string")]
    #[Assert\NotBlank(message: "Le nom de la station ne peut être vide.")]
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
    #[Assert\NotBlank(message: "La latitude de la station ne peut être vide.")]
    private float $latitude = 0;

    #[ODM\Field(type: "float")]
    #[Assert\NotBlank(message: "La longitude de la station ne peut être vide.")]
    private float $longitude = 0;

    #[ODM\Field(type: "string")]
    private ?string $website = '';

    #[ODM\Field(type: "string")]
    private string $emergencyPhone = '';

    #[ODM\Field(type: "string")]
    private string $altitudeMin = '';

    #[ODM\Field(type: "string")]
    private string $altitudeMax = '';

    #[ODM\Field(type: "string")]
    private string $distanceSlope = '';

    #[ODM\Field(type: "string")]
    private string $countEasy = '';

    #[ODM\Field(type: "string")]
    private string $countIntermediate = '';

    #[ODM\Field(type: "string")]
    private string $countAdvanced = '';

    #[ODM\Field(type: "string")]
    private string $countExpert = '';

    #[ODM\Field(type: "string")]
    private string $logo = '';


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

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param mixed $id
     */
    public function setId($id): void
    {
        $this->id = $id;
    }

    public function getDomain(): string
    {
        return $this->domain;
    }

    public function setDomain(string $domain): void
    {
        $this->domain = $domain;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getGeometry(): array
    {
        return $this->geometry;
    }

    public function setGeometry(array $geometry): void
    {
        $this->geometry = $geometry;
    }

    public function getTags(): array
    {
        return $this->tags;
    }

    public function setTags(array $tags): void
    {
        $this->tags = $tags;
    }

    public function isValidated(): bool
    {
        return $this->validated;
    }

    public function setValidated(bool $validated): void
    {
        $this->validated = $validated;
    }

    public function getOsmId(): string
    {
        return $this->osmId;
    }

    public function setOsmId(string $osmId): void
    {
        $this->osmId = $osmId;
    }

    public function getLatitude(): float
    {
        return $this->latitude;
    }

    public function setLatitude(float $latitude): void
    {
        $this->latitude = $latitude;
    }

    public function getLongitude(): float
    {
        return $this->longitude;
    }

    public function setLongitude(float $longitude): void
    {
        $this->longitude = $longitude;
    }

    public function getWebsite(): ?string
    {
        return $this->website;
    }

    public function setWebsite(?string $website): void
    {
        $this->website = $website;
    }

    public function getEmergencyPhone(): string
    {
        return $this->emergencyPhone;
    }

    public function setEmergencyPhone(string $emergencyPhone): void
    {
        $this->emergencyPhone = $emergencyPhone;
    }

    public function getAltitudeMin(): string
    {
        return $this->altitudeMin;
    }

    public function setAltitudeMin(string $altitudeMin): void
    {
        $this->altitudeMin = $altitudeMin;
    }

    public function getAltitudeMax(): string
    {
        return $this->altitudeMax;
    }

    public function setAltitudeMax(string $altitudeMax): void
    {
        $this->altitudeMax = $altitudeMax;
    }

    public function getDistanceSlope(): string
    {
        return $this->distanceSlope;
    }

    public function setDistanceSlope(string $distanceSlope): void
    {
        $this->distanceSlope = $distanceSlope;
    }

    public function getCountEasy(): string
    {
        return $this->countEasy;
    }

    public function setCountEasy(string $countEasy): void
    {
        $this->countEasy = $countEasy;
    }

    public function getCountIntermediate(): string
    {
        return $this->countIntermediate;
    }

    public function setCountIntermediate(string $countIntermediate): void
    {
        $this->countIntermediate = $countIntermediate;
    }

    public function getCountAdvanced(): string
    {
        return $this->countAdvanced;
    }

    public function setCountAdvanced(string $countAdvanced): void
    {
        $this->countAdvanced = $countAdvanced;
    }

    public function getCountExpert(): string
    {
        return $this->countExpert;
    }

    public function setCountExpert(string $countExpert): void
    {
        $this->countExpert = $countExpert;
    }

    public function getFeatures(): array
    {
        return $this->features;
    }

    public function setFeatures(array $features): void
    {
        $this->features = $features;
    }

    public function getLogo(): string
    {
        return $this->logo;
    }

    public function setLogo(string $logo): void
    {
        $this->logo = $logo;
    }

}
