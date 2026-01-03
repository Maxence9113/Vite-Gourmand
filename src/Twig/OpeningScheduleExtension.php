<?php

namespace App\Twig;

use App\Service\OpeningScheduleManager;
use Twig\Extension\AbstractExtension;
use Twig\Extension\GlobalsInterface;

/**
 * Extension Twig pour rendre les horaires d'ouverture disponibles globalement dans tous les templates
 *
 * Cette extension injecte automatiquement la variable `opening_schedules` dans tous les templates Twig.
 * Cela permet d'afficher les horaires dans le footer sans avoir à les passer depuis chaque controller.
 *
 * Usage dans les templates :
 * {% for schedule in opening_schedules %}
 *     {{ schedule.label }} : {{ schedule.openingTime }} - {{ schedule.closingTime }}
 * {% endfor %}
 */
class OpeningScheduleExtension extends AbstractExtension implements GlobalsInterface
{
    public function __construct(
        private OpeningScheduleManager $openingScheduleManager
    ) {
    }

    /**
     * Retourne les variables globales Twig
     *
     * @return array La variable 'opening_schedules' contient les 7 jours de la semaine avec leurs horaires
     */
    public function getGlobals(): array
    {
        return [
            'opening_schedules' => $this->openingScheduleManager->getFormattedSchedules(),
        ];
    }
}