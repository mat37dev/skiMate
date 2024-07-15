<?php

namespace App\Entity;

use App\Repository\StatisticRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: StatisticRepository::class)]
class Statistic
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(nullable: true)]
    private ?float $TotalDistance = null;

    #[ORM\Column(type: Types::TIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $TotalHours = null;

    #[ORM\Column(nullable: true)]
    private ?float $TotalElevation = null;

    /**
     * @var Collection<int, user>
     */
    #[ORM\OneToMany(targetEntity: user::class, mappedBy: 'statistic')]
    private Collection $UserStatistic;

    public function __construct()
    {
        $this->UserStatistic = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTotalDistance(): ?float
    {
        return $this->TotalDistance;
    }

    public function setTotalDistance(?float $TotalDistance): static
    {
        $this->TotalDistance = $TotalDistance;

        return $this;
    }

    public function getTotalHours(): ?\DateTimeInterface
    {
        return $this->TotalHours;
    }

    public function setTotalHours(?\DateTimeInterface $TotalHours): static
    {
        $this->TotalHours = $TotalHours;

        return $this;
    }

    public function getTotalElevation(): ?float
    {
        return $this->TotalElevation;
    }

    public function setTotalElevation(?float $TotalElevation): static
    {
        $this->TotalElevation = $TotalElevation;

        return $this;
    }

    /**
     * @return Collection<int, user>
     */
    public function getUserStatistic(): Collection
    {
        return $this->UserStatistic;
    }

    public function addUserStatistic(user $userStatistic): static
    {
        if (!$this->UserStatistic->contains($userStatistic)) {
            $this->UserStatistic->add($userStatistic);
            $userStatistic->setStatistic($this);
        }

        return $this;
    }

    public function removeUserStatistic(user $userStatistic): static
    {
        if ($this->UserStatistic->removeElement($userStatistic)) {
            // set the owning side to null (unless already changed)
            if ($userStatistic->getStatistic() === $this) {
                $userStatistic->setStatistic(null);
            }
        }

        return $this;
    }
}
