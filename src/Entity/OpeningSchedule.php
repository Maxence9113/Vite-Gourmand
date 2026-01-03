<?php

namespace App\Entity;

use App\Enum\DayOfWeek;
use App\Repository\OpeningScheduleRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: OpeningScheduleRepository::class)]
#[ORM\HasLifecycleCallbacks]
#[ORM\UniqueConstraint(name: 'unique_day', columns: ['day_of_week'])]
class OpeningSchedule
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: 'integer', enumType: DayOfWeek::class)]
    private ?DayOfWeek $dayOfWeek = null;

    #[ORM\Column(type: Types::TIME_IMMUTABLE, nullable: true)]
    #[Assert\LessThan(propertyPath: 'closingTime', message: 'L\'heure d\'ouverture doit être antérieure à l\'heure de fermeture.')]
    private ?\DateTimeImmutable $openingTime = null;

    #[ORM\Column(type: Types::TIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $closingTime = null;

    #[ORM\Column]
    private bool $isOpen = true;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\PrePersist]
    public function setCreatedAtValue(): void
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
    }

    #[ORM\PreUpdate]
    public function setUpdatedAtValue(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDayOfWeek(): ?DayOfWeek
    {
        return $this->dayOfWeek;
    }

    public function setDayOfWeek(DayOfWeek $dayOfWeek): static
    {
        $this->dayOfWeek = $dayOfWeek;

        return $this;
    }

    public function getOpeningTime(): ?\DateTimeImmutable
    {
        return $this->openingTime;
    }

    public function setOpeningTime(?\DateTimeImmutable $openingTime): static
    {
        $this->openingTime = $openingTime;

        return $this;
    }

    public function getClosingTime(): ?\DateTimeImmutable
    {
        return $this->closingTime;
    }

    public function setClosingTime(?\DateTimeImmutable $closingTime): static
    {
        $this->closingTime = $closingTime;

        return $this;
    }

    public function isOpen(): bool
    {
        return $this->isOpen;
    }

    public function setIsOpen(bool $isOpen): static
    {
        $this->isOpen = $isOpen;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function isOpenAt(\DateTimeImmutable $dateTime): bool
    {
        if (!$this->isOpen) {
            return false;
        }

        if ($this->openingTime === null || $this->closingTime === null) {
            return false;
        }

        $time = $dateTime->format('H:i:s');
        $opening = $this->openingTime->format('H:i:s');
        $closing = $this->closingTime->format('H:i:s');

        return $time >= $opening && $time <= $closing;
    }
}