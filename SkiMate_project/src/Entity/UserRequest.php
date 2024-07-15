<?php

namespace App\Entity;

use App\Repository\UserRequestRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: UserRequestRepository::class)]
class UserRequest
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: Types::DATE_IMMUTABLE)]
    private ?\DateTimeImmutable $RequestDate = null;

    #[ORM\Column(length: 255)]
    private ?string $RequestStatus = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $Description = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getRequestDate(): ?\DateTimeImmutable
    {
        return $this->RequestDate;
    }

    public function setRequestDate(\DateTimeImmutable $RequestDate): static
    {
        $this->RequestDate = $RequestDate;

        return $this;
    }

    public function getRequestStatus(): ?string
    {
        return $this->RequestStatus;
    }

    public function setRequestStatus(string $RequestStatus): static
    {
        $this->RequestStatus = $RequestStatus;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->Description;
    }

    public function setDescription(string $Description): static
    {
        $this->Description = $Description;

        return $this;
    }
}
