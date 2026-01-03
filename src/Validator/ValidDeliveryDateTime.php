<?php

namespace App\Validator;

use Symfony\Component\Validator\Constraint;

/**
 * Contrainte de validation pour vérifier qu'une date de livraison est valide
 *
 * Vérifie que :
 * - La date est au minimum 48h à l'avance
 * - La date tombe pendant les horaires d'ouverture du restaurant
 */
#[\Attribute]
class ValidDeliveryDateTime extends Constraint
{
    public string $message = 'La livraison ne peut pas avoir lieu à cette date/heure. Le restaurant est fermé ou le délai de 48h n\'est pas respecté.';

    public string $tooSoonMessage = 'La livraison doit être prévue au minimum 48h à l\'avance.';

    public string $closedMessage = 'Le restaurant est fermé à cette date/heure. Veuillez choisir un créneau pendant nos horaires d\'ouverture.';
}