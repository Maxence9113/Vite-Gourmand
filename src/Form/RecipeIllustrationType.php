<?php

namespace App\Form;

use App\Entity\RecipeIllustration;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Formulaire pour uploader une illustration de recette
 */
class RecipeIllustrationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            // Champ pour uploader le fichier image
            // Ce champ sera lié à la propriété $imageFile de l'entité RecipeIllustration
            ->add('imageFile', FileType::class, [
                'label' => 'Image du plat',
                'help' => 'Formats acceptés : JPEG, PNG, WebP. Taille max : 2 Mo',
                'attr' => [
                    'class' => 'form-control',
                    'accept' => 'image/jpeg,image/png,image/webp' // Limite les types de fichiers dans le sélecteur
                ],
                // required: false permet de ne pas exiger une nouvelle image lors de l'édition
                'required' => false,
            ])
            // Champ pour le texte alternatif (accessibilité)
            ->add('alt_text', TextType::class, [
                'label' => 'Description de l\'image',
                'help' => 'Décrivez brièvement le contenu de l\'image (pour l\'accessibilité)',
                'attr' => [
                    'placeholder' => 'Ex: Assiette de bœuf bourguignon garnie',
                    'class' => 'form-control'
                ],
                'required' => false,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            // Indique que ce formulaire est lié à l'entité RecipeIllustration
            'data_class' => RecipeIllustration::class,
        ]);
    }
}