<?php

namespace App\Entity;

use App\Repository\CommentRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: CommentRepository::class)]
class Comment
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\Column(type: 'string', length: 36, unique: true)]
    #[ORM\CustomIdGenerator(class: 'doctrine.uuid_generator')]
    private ?string $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: "Le titre ne doit pas être vide.")]
    #[Assert\Length(
        max: 255,
        maxMessage: "Le titre ne peut dépasser {{ limit }} caractères."
    )]
    private ?string $title = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: "La description ne doit pas être vide.")]
    #[Assert\Length(
        max: 255,
        maxMessage: "La description ne peut dépasser {{ limit }} caractères."
    )]
    private ?string $description = null;

    #[ORM\Column]
    #[Assert\NotBlank(message: "L'osmId ne doit pas être vide.")]
    private ?string $osmId = null;

    #[ORM\ManyToOne]
    #[Assert\NotNull(message: "L'utilisateur est requis.")]
    private ?Users $user = null;

    #[ORM\Column(type: "float")]
    #[Assert\NotNull(message: "La note est requise.")]
    #[Assert\Range(
        notInRangeMessage: "La note doit être comprise entre {{ min }} et {{ max }}.",
        min: 0,
        max: 5
    )]
    private ?float $note = null;

    #[ORM\Column(nullable: false)]
    private bool $isValide = true;

    #[ORM\Column(nullable: false)]
    private \DateTimeImmutable $createdAt;

    public function getId(): ?string
    {
        return $this->id;
    }

    public function setId(?string $id): void
    {
        $this->id = $id;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(?string $title): void
    {
        $this->title = $title;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): void
    {
        $this->description = $description;
    }

    public function getOsmId(): ?string
    {
        return $this->osmId;
    }

    public function setOsmId(?string $osmId): void
    {
        $this->osmId = $osmId;
    }

    public function getUser(): ?Users
    {
        return $this->user;
    }

    public function setUser(?Users $user): void
    {
        $this->user = $user;
    }

    public function getNote(): ?float
    {
        return $this->note;
    }

    public function setNote(?float $note): void
    {
        $this->note = $note;
    }

    public function isValide(): bool
    {
        return $this->isValide;
    }

    public function setIsValide(bool $isValide): void
    {
        $this->isValide = $isValide;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): void
    {
        $this->createdAt = $createdAt;
    }
}
