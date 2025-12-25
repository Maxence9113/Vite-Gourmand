<?php

namespace App\Form;

use App\Entity\Address;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Regex;

class AddressType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('label', TextType::class, [
                'label' => 'Libellé (optionnel)',
                'required' => false,
                'attr' => [
                    'placeholder' => 'Ex: Domicile, Travail, Événement...',
                    'class' => 'form-control'
                ],
                'help' => 'Donnez un nom à cette adresse pour la retrouver facilement',
                'constraints' => [
                    new Length([
                        'max' => 255,
                        'maxMessage' => 'Le libellé ne peut pas dépasser {{ limit }} caractères',
                    ]),
                ],
            ])
            ->add('street', TextType::class, [
                'label' => 'Adresse',
                'attr' => [
                    'placeholder' => 'Numéro et nom de rue',
                    'class' => 'form-control'
                ],
                'constraints' => [
                    new NotBlank([
                        'message' => 'L\'adresse ne peut pas être vide',
                    ]),
                    new Length([
                        'min' => 5,
                        'max' => 255,
                        'minMessage' => 'L\'adresse doit contenir au moins {{ limit }} caractères',
                        'maxMessage' => 'L\'adresse ne peut pas dépasser {{ limit }} caractères',
                    ]),
                ],
            ])
            ->add('postalCode', TextType::class, [
                'label' => 'Code postal',
                'attr' => [
                    'placeholder' => '33000',
                    'class' => 'form-control',
                    'maxlength' => 5
                ],
                'constraints' => [
                    new NotBlank([
                        'message' => 'Le code postal ne peut pas être vide',
                    ]),
                    new Regex([
                        'pattern' => '/^[0-9]{5}$/',
                        'message' => 'Le code postal doit contenir exactement 5 chiffres',
                    ]),
                ],
            ])
            ->add('city', TextType::class, [
                'label' => 'Ville',
                'attr' => [
                    'placeholder' => 'Bordeaux',
                    'class' => 'form-control'
                ],
                'constraints' => [
                    new NotBlank([
                        'message' => 'La ville ne peut pas être vide',
                    ]),
                    new Length([
                        'min' => 2,
                        'max' => 255,
                        'minMessage' => 'La ville doit contenir au moins {{ limit }} caractères',
                        'maxMessage' => 'La ville ne peut pas dépasser {{ limit }} caractères',
                    ]),
                ],
            ])
            ->add('phone', TelType::class, [
                'label' => 'Téléphone',
                'attr' => [
                    'placeholder' => '06 12 34 56 78',
                    'class' => 'form-control'
                ],
                'help' => 'Numéro de téléphone pour la livraison',
                'constraints' => [
                    new NotBlank([
                        'message' => 'Le numéro de téléphone ne peut pas être vide',
                    ]),
                    new Regex([
                        'pattern' => '/^(?:(?:\+|00)33|0)\s*[1-9](?:[\s.-]*\d{2}){4}$/',
                        'message' => 'Veuillez entrer un numéro de téléphone français valide',
                    ]),
                ],
            ])
            ->add('isDefault', CheckboxType::class, [
                'label' => 'Définir comme adresse par défaut',
                'required' => false,
                'attr' => [
                    'class' => 'form-check-input'
                ],
                'help' => 'Cette adresse sera automatiquement sélectionnée lors de vos commandes',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Address::class,
        ]);
    }
}
