<?php

namespace App\Form;

use App\Enum\DayOfWeek;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TimeType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class BulkOpeningScheduleType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('days', ChoiceType::class, [
                'label' => 'Sélectionnez les jours',
                'choices' => array_combine(
                    array_map(fn(DayOfWeek $day) => $day->getLabel(), DayOfWeek::cases()),
                    DayOfWeek::cases()
                ),
                'choice_label' => fn(DayOfWeek $day) => $day->getLabel(),
                'choice_value' => fn(?DayOfWeek $day) => $day?->value,
                'expanded' => true,
                'multiple' => true,
                'required' => true,
            ])
            ->add('isOpen', CheckboxType::class, [
                'label' => 'Restaurant ouvert',
                'required' => false,
                'attr' => [
                    'class' => 'form-check-input',
                ],
            ])
            ->add('openingTime', TimeType::class, [
                'label' => 'Heure d\'ouverture',
                'widget' => 'single_text',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                ],
            ])
            ->add('closingTime', TimeType::class, [
                'label' => 'Heure de fermeture',
                'widget' => 'single_text',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([]);
    }
}
