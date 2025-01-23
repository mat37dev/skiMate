<?php

namespace App\Entity;

use App\Repository\UsersRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: UsersRepository::class)]
#[UniqueEntity(fields: ['email'], message: 'Cette adresse mail est déjà utilisée.')]
class Users implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\Column(type: 'string',length: 36, unique: true)]
    #[ORM\CustomIdGenerator(class: 'doctrine.uuid_generator')]
    private ?string $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'Le champ "Nom" ne peut pas être vide.')]
    private ?string $lastname = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'Le champ "Prénom" ne peut pas être vide.')]
    private ?string $firstname = null;

    #[ORM\Column( length: 255, unique: true)]
    #[Assert\NotBlank(message: 'Le champ "Email" ne peut pas être vide.')]
    #[Assert\Email(message: "L'adresse email n'est pas valide.")]
    private ?string $email = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'Le champ "Mot de Passe" ne peut pas être vide.')]
    private ?string $password = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'Le champ "Numéro de "Téléphone" ne peut pas être vide.')]
    private ?string $phoneNumber = null;

    #[ORM\ManyToMany(targetEntity: Roles::class)]
    #[ORM\JoinTable(name: 'user_roles')]
    #[Assert\Count(
        min: 1,
        minMessage: "Vous devez fournir au moins un rôle."
    )]
    private Collection  $roles;

    #[ORM\ManyToOne]
    private ?SkiLevel $skiLevel = null;

    #[ORM\ManyToOne]
    private ?SkiPreference $skiPreference = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $skiResortSelected = null;

    #[ORM\Column(type: 'json')]
    private array $favoriteSkiResorts = [];

    public function __construct()
    {
        $this->roles = new ArrayCollection();
    }


    public function getId(): ?string
    {
        return $this->id;
    }


    public function getLastname(): ?string
    {
        return $this->lastname;
    }

    public function setLastname(string $lastname): static
    {
        $this->lastname = $lastname;

        return $this;
    }

    public function getFirstname(): ?string
    {
        return $this->firstname;
    }

    public function setFirstname(string $firstname): static
    {
        $this->firstname = $firstname;

        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;

        return $this;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;

        return $this;
    }

    public function getPhoneNumber(): ?string
    {
        return $this->phoneNumber;
    }

    public function setPhoneNumber(string $phoneNumber): static
    {
        $this->phoneNumber = $phoneNumber;

        return $this;
    }

    /**
     * @return array
     */
    public function getRoles(): array
    {
        $result = [];
        foreach ($this->roles as $role) {
            $result[] = $role->getName();
        }
        return $result;
    }

    public function addRole(Roles $role): static
    {
        $this->roles->add($role);

        return $this;
    }

    public function removeRole(Roles $role): static
    {
        $this->roles->removeElement($role);
        return $this;
    }

    public function getSkiLevel(): ?SkiLevel
    {
        return $this->skiLevel;
    }

    public function setSkiLevel(?SkiLevel $skiLevel): static
    {
        $this->skiLevel = $skiLevel;

        return $this;
    }

    public function eraseCredentials(): void
    {
        // TODO: Implement eraseCredentials() method.
    }

    public function getUserIdentifier(): string
    {
        return $this->id;
    }

    public function getSkiPreference(): ?SkiPreference
    {
        return $this->skiPreference;
    }

    public function setSkiPreference(?SkiPreference $skiPreference): static
    {
        $this->skiPreference = $skiPreference;

        return $this;
    }

    public function getSkiResortSelected(): ?string
    {
        return $this->skiResortSelected;
    }

    public function setSkiResortSelected(?string $skiResortSelected): static
    {
        $this->skiResortSelected = $skiResortSelected;

        return $this;
    }

    public function getFavoriteSkiResorts(): array
    {
        return $this->favoriteSkiResorts;
    }

    public function addFavoriteSkiResort(string $skiResort): self
    {
        if (!in_array($skiResort, $this->favoriteSkiResorts, true)) {
            $this->favoriteSkiResorts[] = $skiResort;
        }

        return $this;
    }

    public function removeFavoriteSkiResort(string $skiResort): self
    {
        $this->favoriteSkiResorts = array_filter(
            $this->favoriteSkiResorts,
            fn($resort) => $resort !== $skiResort
        );

        return $this;
    }
}
