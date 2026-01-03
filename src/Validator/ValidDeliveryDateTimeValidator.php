<?php

namespace App\Validator;

use App\Service\OpeningScheduleManager;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

/**
 * Validateur pour la contrainte ValidDeliveryDateTime
 *
 * Utilise le service OpeningScheduleManager pour vérifier que la date de livraison
 * respecte les horaires d'ouverture et le délai de 48h
 */
class ValidDeliveryDateTimeValidator extends ConstraintValidator
{
    public function __construct(
        private OpeningScheduleManager $openingScheduleManager
    ) {
    }

    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof ValidDeliveryDateTime) {
            throw new UnexpectedTypeException($constraint, ValidDeliveryDateTime::class);
        }

        // Valeur vide acceptée (géré par NotBlank si nécessaire)
        if (null === $value || '' === $value) {
            return;
        }

        if (!$value instanceof \DateTimeInterface) {
            throw new UnexpectedValueException($value, \DateTimeInterface::class);
        }

        // Convertir en DateTimeImmutable pour le service
        $deliveryDateTime = $value instanceof \DateTimeImmutable
            ? $value
            : \DateTimeImmutable::createFromInterface($value);

        // Vérifier le délai de 48h
        $now = new \DateTimeImmutable();
        $minDeliveryDate = $now->modify('+48 hours');

        if ($deliveryDateTime < $minDeliveryDate) {
            $this->context->buildViolation($constraint->tooSoonMessage)
                ->addViolation();
            return;
        }

        // Vérifier les horaires d'ouverture
        if (!$this->openingScheduleManager->canDeliverAt($deliveryDateTime)) {
            $this->context->buildViolation($constraint->closedMessage)
                ->addViolation();
        }
    }
}