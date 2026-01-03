<?php

namespace App\Service;

use App\Entity\OpeningSchedule;
use App\Enum\DayOfWeek;
use App\Repository\OpeningScheduleRepository;

class OpeningScheduleManager
{
    public function __construct(
        private OpeningScheduleRepository $openingScheduleRepository
    ) {
    }

    public function isRestaurantOpen(\DateTimeImmutable $dateTime): bool
    {
        $dayOfWeek = DayOfWeek::fromDateTime($dateTime);
        $schedule = $this->openingScheduleRepository->findByDayOfWeek($dayOfWeek);

        if ($schedule === null) {
            return false;
        }

        return $schedule->isOpenAt($dateTime);
    }

    public function canDeliverAt(\DateTimeImmutable $deliveryDateTime): bool
    {
        return $this->isRestaurantOpen($deliveryDateTime);
    }

    public function isValidDeliveryDateTime(\DateTimeImmutable $dateTime): bool
    {
        $now = new \DateTimeImmutable();

        if ($dateTime <= $now->modify('+48 hours')) {
            return false;
        }

        return $this->canDeliverAt($dateTime);
    }

    public function getNextOpeningTime(\DateTimeImmutable $fromDateTime = null): ?\DateTimeImmutable
    {
        $fromDateTime = $fromDateTime ?? new \DateTimeImmutable();
        $openDays = $this->openingScheduleRepository->findOpenDays();

        if (empty($openDays)) {
            return null;
        }

        $currentDayOfWeek = DayOfWeek::fromDateTime($fromDateTime);
        $currentDayNumber = $currentDayOfWeek->value;

        for ($i = 0; $i < 7; $i++) {
            $checkDayNumber = (($currentDayNumber - 1 + $i) % 7) + 1;
            $checkDay = DayOfWeek::from($checkDayNumber);

            $schedule = $this->openingScheduleRepository->findByDayOfWeek($checkDay);

            if ($schedule && $schedule->isOpen() && $schedule->getOpeningTime()) {
                $daysToAdd = $i;
                $nextDate = $fromDateTime->modify("+{$daysToAdd} days");

                $nextDateTime = new \DateTimeImmutable(
                    $nextDate->format('Y-m-d') . ' ' . $schedule->getOpeningTime()->format('H:i:s')
                );

                if ($nextDateTime > $fromDateTime) {
                    return $nextDateTime;
                }
            }
        }

        return null;
    }

    public function getScheduleForDay(DayOfWeek $dayOfWeek): ?OpeningSchedule
    {
        return $this->openingScheduleRepository->findByDayOfWeek($dayOfWeek);
    }

    public function getAllSchedules(): array
    {
        return $this->openingScheduleRepository->findAllOrdered();
    }

    public function getFormattedSchedules(): array
    {
        $schedules = $this->getAllSchedules();
        $formatted = [];

        foreach (DayOfWeek::all() as $day) {
            $schedule = null;
            foreach ($schedules as $s) {
                if ($s->getDayOfWeek() === $day) {
                    $schedule = $s;
                    break;
                }
            }

            $formatted[] = [
                'day' => $day,
                'schedule' => $schedule,
                'label' => $day->getLabel(),
                'isOpen' => $schedule?->isOpen() ?? false,
                'openingTime' => $schedule?->getOpeningTime()?->format('H:i'),
                'closingTime' => $schedule?->getClosingTime()?->format('H:i'),
            ];
        }

        return $formatted;
    }
}