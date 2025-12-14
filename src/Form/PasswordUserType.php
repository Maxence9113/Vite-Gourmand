<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\PasswordStrength;
use Symfony\Component\Validator\Constraints as Assert;


class PasswordUserType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('actualPassword', PasswordType::class, [
                'label' => 'Votre mot de pass actuel',
                'attr' => [
                        'placeholder' => 'Indiquez votre mot de passe',
                    ],
                'mapped' => false,
            ])
            ->add('plainPassword', RepeatedType::class, [
                'type' => PasswordType::class,
                'constraints' => [
                    new Assert\NotBlank(message: 'Le mot de passe est obligatoire.'),
                    new Assert\Length(
                        min: 10,
                        minMessage: 'Le mot de passe doit contenir au moins {{ limit }} caractères.'
                    ),
                    new PasswordStrength(
                        minScore: PasswordStrength::STRENGTH_STRONG,
                        message: 'Le mot de passe doit contenir au moins 1 majuscule, 1 minuscule, 1 chiffre et 1 caractère spécial.'
                    )
                ],
                'first_options' => [
                    'label' => 'Votre nouveau mot de passe', 
                    'attr' => [
                        'placeholder' => 'Indiquez votre nouveau mot de passe',
                    ],
                    'hash_property_path' => 'password'
                ],
                'second_options' => [
                    'label' => 'Confirmation du nouveau mot de passe',
                    'attr' => [
                        'placeholder' => 'Confirmez votre nouveau mot de passe'
                    ]
                ],
                'mapped' => false,
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'Mettre à jour',
                'attr' => [
                    'class'=> 'btn btn-primary'
                ]
            ])
            //écouteur d'évennement dans le formulaire
            // au moment du "SUBMIT"
            ->addEventListener(FormEvents::SUBMIT, function (FormEvent $event){
                // dump('OK l\'event fonctionne');

                // Récupération du formulaire
                // dump($event->getForm());
                $form = $event->getForm();

                // Récupération du User actuel
                // dump($event->getForm()->getConfig()->getOptions()['data']);
                $user = $form->getConfig()->getOptions()['data'];

                // Vérification de l'encodage du mot de passe -> AccountController
                // Déclaration de l'option plus bas dans configureOption()
                // dd($form->getConfig()->getOptions()['userPasswordHasher']);
                $passwordHasher = $form->getConfig()->getOptions()['userPasswordHasher'];

                //1. Récupérer le mot de passe saisi
                // dump($form->get('actualPassword')->getData());

                //2. Comparer avec le mot de passe en BDD
                $isValid = $passwordHasher->isPasswordValid(
                    $user,
                    // mot de passe en clair
                    $form->get('actualPassword')->getData()
                );

                //Si passwd different, envoyer l'erreur. $isValid = false ou true
                // dd($isValid);
                if (!$isValid) {
                    $form->get('actualPassword')->addError(new FormError("Votre mot de passe actuel n'est pas conforme. Veuillez vérifier."));
                }
            })

        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
            'userPasswordHasher' => null
        ]);
    }
}
