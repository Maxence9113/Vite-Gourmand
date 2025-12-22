<?php

namespace App\Form;

use App\Entity\Theme;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;

class ThemeType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Nom du thème',
                'attr' => [
                    'placeholder' => 'Ex: Noël, Mariage, Anniversaire...',
                    'class' => 'form-control'
                ]
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'attr' => [
                    'placeholder' => 'Décrivez le thème...',
                    'class' => 'form-control',
                    'rows' => 4
                ]
            ])
            ->add('imageFile', FileType::class, [
                'label' => 'Illustration du thème',
                'mapped' => false, // Ce champ n'est pas mappé directement à l'entité
                'required' => !$options['is_edit'], // Obligatoire uniquement en création
                'attr' => [
                    'class' => 'form-control',
                    'accept' => 'image/jpeg,image/png,image/webp'
                ],
                'constraints' => [
                    new File([
                        'maxSize' => '2M',
                        'mimeTypes' => [
                            'image/jpeg',
                            'image/png',
                            'image/webp',
                        ],
                        'mimeTypesMessage' => 'Veuillez uploader une image valide (JPEG, PNG ou WebP)',
                    ])
                ],
                'help' => 'Formats acceptés : JPEG, PNG, WebP. Taille maximale : 2 Mo.'
            ])
            ->add('textAlt', TextType::class, [
                'label' => 'Texte alternatif (accessibilité)',
                'required' => false,
                'attr' => [
                    'placeholder' => 'Description de l\'image pour les lecteurs d\'écran',
                    'class' => 'form-control'
                ],
                'help' => 'Optionnel mais recommandé pour l\'accessibilité. Si vide, le nom du thème sera utilisé.'
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Theme::class,
            'is_edit' => false, // Option personnalisée pour savoir si on est en mode édition
        ]);
    }
}