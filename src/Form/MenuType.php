<?php

namespace App\Form;

use App\Entity\Dietetary;
use App\Entity\Menu;
use App\Entity\Recipe;
use App\Entity\Theme;
use App\Repository\RecipeRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Constraints\GreaterThan;
use Symfony\Component\Validator\Constraints\GreaterThanOrEqual;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Positive;

class MenuType extends AbstractType
{
    public function __construct(
        private RecipeRepository $recipeRepository
    ) {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        // Charger toutes les recettes en une seule requête
        $recipesGrouped = $this->recipeRepository->findAllGroupedByCategory();
        $builder
            ->add('name', TextType::class, [
                'label' => 'Nom du menu',
                'attr' => [
                    'placeholder' => 'Ex: Menu de Noël Traditionnel',
                    'class' => 'form-control'
                ],
                'constraints' => [
                    new NotBlank([
                        'message' => 'Le nom du menu ne peut pas être vide',
                    ]),
                    new Length([
                        'min' => 3,
                        'max' => 255,
                        'minMessage' => 'Le nom doit contenir au moins {{ limit }} caractères',
                        'maxMessage' => 'Le nom ne peut pas dépasser {{ limit }} caractères',
                    ]),
                ],
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'attr' => [
                    'placeholder' => 'Décrivez le menu...',
                    'class' => 'form-control',
                    'rows' => 5
                ],
                'constraints' => [
                    new NotBlank([
                        'message' => 'La description ne peut pas être vide',
                    ]),
                ],
            ])
            ->add('nb_person_min', IntegerType::class, [
                'label' => 'Nombre de personnes minimum',
                'attr' => [
                    'placeholder' => 'Ex: 10',
                    'class' => 'form-control',
                    'min' => 1
                ],
                'constraints' => [
                    new NotBlank([
                        'message' => 'Le nombre de personnes minimum ne peut pas être vide',
                    ]),
                    new Positive([
                        'message' => 'Le nombre de personnes doit être positif',
                    ]),
                ],
            ])
            ->add('price_per_person', MoneyType::class, [
                'label' => 'Prix par personne (€)',
                'currency' => 'EUR',
                'attr' => [
                    'placeholder' => 'Ex: 45.00',
                    'class' => 'form-control',
                    'step' => '0.01'
                ],
                'constraints' => [
                    new NotBlank([
                        'message' => 'Le prix par personne ne peut pas être vide',
                    ]),
                    new GreaterThan([
                        'value' => 0,
                        'message' => 'Le prix doit être supérieur à 0',
                    ]),
                ],
            ])
            ->add('stock', IntegerType::class, [
                'label' => 'Stock disponible (nombre de personnes)',
                'required' => false,
                'attr' => [
                    'placeholder' => 'Laisser vide pour stock illimité',
                    'class' => 'form-control',
                    'min' => 0
                ],
                'constraints' => [
                    new GreaterThanOrEqual([
                        'value' => 0,
                        'message' => 'Le stock ne peut pas être négatif',
                    ]),
                ],
                'help' => 'Laissez vide pour un stock illimité. Sinon, indiquez le nombre total de personnes pouvant être servies.',
            ])
            ->add('theme', EntityType::class, [
                'class' => Theme::class,
                'choice_label' => 'name',
                'label' => 'Thème',
                'placeholder' => 'Sélectionnez un thème',
                'attr' => [
                    'class' => 'form-control'
                ],
                'constraints' => [
                    new NotBlank([
                        'message' => 'Veuillez sélectionner un thème',
                    ]),
                ],
            ])
            ->add('imageFile', FileType::class, [
                'label' => 'Illustration du menu',
                'mapped' => false,
                'required' => !$options['is_edit'],
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
                'help' => 'Optionnel mais recommandé pour l\'accessibilité. Si vide, le nom du menu sera utilisé.'
            ])
            ->add('dietetary', EntityType::class, [
                'class' => Dietetary::class,
                'choice_label' => 'name',
                'label' => 'Régimes alimentaires',
                'multiple' => true,
                'expanded' => true,
                'required' => false,
                'help' => 'Sélectionnez les régimes alimentaires compatibles avec ce menu',
            ])
            ->add('entrees', EntityType::class, [
                'class' => Recipe::class,
                'choice_label' => 'title',
                'label' => 'Entrées',
                'multiple' => true,
                'expanded' => true,
                'mapped' => false,
                'choices' => $recipesGrouped['Entrée'] ?? [],
                'required' => false,
            ])
            ->add('plats', EntityType::class, [
                'class' => Recipe::class,
                'choice_label' => 'title',
                'label' => 'Plats',
                'multiple' => true,
                'expanded' => true,
                'mapped' => false,
                'choices' => $recipesGrouped['Plat'] ?? [],
                'required' => false,
            ])
            ->add('fromages', EntityType::class, [
                'class' => Recipe::class,
                'choice_label' => 'title',
                'label' => 'Fromages',
                'multiple' => true,
                'expanded' => true,
                'mapped' => false,
                'choices' => $recipesGrouped['Fromage'] ?? [],
                'required' => false,
            ])
            ->add('desserts', EntityType::class, [
                'class' => Recipe::class,
                'choice_label' => 'title',
                'label' => 'Desserts',
                'multiple' => true,
                'expanded' => true,
                'mapped' => false,
                'choices' => $recipesGrouped['Dessert'] ?? [],
                'required' => false,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Menu::class,
            'is_edit' => false,
        ]);
    }
}