<?php

namespace App\Enum;

enum DayOfWeek: int
{
    case MONDAY = 1;
    case TUESDAY = 2;
    case WEDNESDAY = 3;
    case THURSDAY = 4;
    case FRIDAY = 5;
    case SATURDAY = 6;
    case SUNDAY = 7;

    public function getLabel(): string
    {
        return match ($this) {
            self::MONDAY => 'Lundi',
            self::TUESDAY => 'Mardi',
            self::WEDNESDAY => 'Mercredi',
            self::THURSDAY => 'Jeudi',
            self::FRIDAY => 'Vendredi',
            self::SATURDAY => 'Samedi',
            self::SUNDAY => 'Dimanche',
        };
    }

    public function getShortLabel(): string
    {
        return match ($this) {
            self::MONDAY => 'Lun',
            self::TUESDAY => 'Mar',
            self::WEDNESDAY => 'Mer',
            self::THURSDAY => 'Jeu',
            self::FRIDAY => 'Ven',
            self::SATURDAY => 'Sam',
            self::SUNDAY => 'Dim',
        };
    }

    public static function fromDateTime(\DateTimeInterface $dateTime): self
    {
        $dayNumber = (int) $dateTime->format('N');
        return self::from($dayNumber);
    }

    public static function all(): array
    {
        return [
            self::MONDAY,
            self::TUESDAY,
            self::WEDNESDAY,
            self::THURSDAY,
            self::FRIDAY,
            self::SATURDAY,
            self::SUNDAY,
        ];
    }
}