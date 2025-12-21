<?php

namespace App\Form;

use App\Entity\Allergen;
use App\Entity\Category;
use App\Entity\Recipe;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class RecipeType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class, [
                'label' => 'Titre de la recette',
                'attr' => [
                    'placeholder' => 'Ex: Bœuf bourguignon',
                    'class' => 'form-control'
                ]
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'attr' => [
                    'placeholder' => 'Décrivez la recette...',
                    'class' => 'form-control',
                    'rows' => 4
                ]
            ])
            ->add('category', EntityType::class, [
                'class' => Category::class,
                'choice_label' => 'name',
                'label' => 'Catégorie',
                'placeholder' => 'Choisir une catégorie',
                'attr' => ['class' => 'form-control']
            ])
            ->add('allergen', EntityType::class, [
                'class' => Allergen::class,
                'choice_label' => 'name',
                'label' => 'Allergènes',
                'multiple' => true,
                'expanded' => true,
                'required' => false,
            ])
            // Ajout du champ pour gérer les illustrations
            // CollectionType permet de gérer plusieurs illustrations (une collection)
            ->add('recipeIllustrations', CollectionType::class, [
                'entry_type' => RecipeIllustrationType::class, // Chaque entrée utilise le formulaire RecipeIllustrationType
                'label' => 'Illustrations du plat',
                'entry_options' => ['label' => false], // On n'affiche pas de label pour chaque illustration
                'allow_add' => true,    // Permet d'ajouter de nouvelles illustrations dynamiquement
                'allow_delete' => true, // Permet de supprimer des illustrations
                'by_reference' => false, // IMPORTANT : Force Symfony à appeler les méthodes add/remove de l'entité Recipe
                'prototype' => true,    // Génère un template HTML pour ajouter dynamiquement des illustrations avec JavaScript
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Recipe::class,
        ]);
    }
}
