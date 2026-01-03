<?php

namespace App\DataFixtures;

use App\Entity\OpeningSchedule;
use App\Enum\DayOfWeek;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class OpeningScheduleFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $schedules = [
            [
                'day' => DayOfWeek::MONDAY,
                'opening' => '09:00',
                'closing' => '18:00',
                'isOpen' => true,
            ],
            [
                'day' => DayOfWeek::TUESDAY,
                'opening' => '09:00',
                'closing' => '18:00',
                'isOpen' => true,
            ],
            [
                'day' => DayOfWeek::WEDNESDAY,
                'opening' => '09:00',
                'closing' => '18:00',
                'isOpen' => true,
            ],
            [
                'day' => DayOfWeek::THURSDAY,
                'opening' => '09:00',
                'closing' => '18:00',
                'isOpen' => true,
            ],
            [
                'day' => DayOfWeek::FRIDAY,
                'opening' => '09:00',
                'closing' => '18:00',
                'isOpen' => true,
            ],
            [
                'day' => DayOfWeek::SATURDAY,
                'opening' => '10:00',
                'closing' => '16:00',
                'isOpen' => true,
            ],
            [
                'day' => DayOfWeek::SUNDAY,
                'opening' => null,
                'closing' => null,
                'isOpen' => false,
            ],
        ];

        foreach ($schedules as $data) {
            $schedule = new OpeningSchedule();
            $schedule->setDayOfWeek($data['day']);
            $schedule->setIsOpen($data['isOpen']);

            if ($data['opening'] !== null) {
                $schedule->setOpeningTime(new \DateTimeImmutable($data['opening']));
            }

            if ($data['closing'] !== null) {
                $schedule->setClosingTime(new \DateTimeImmutable($data['closing']));
            }

            $manager->persist($schedule);
        }

        $manager->flush();
    }
}