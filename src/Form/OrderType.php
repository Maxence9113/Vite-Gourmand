<?php

namespace App\Form;

use App\Entity\Address;
use App\Entity\Menu;
use App\Entity\Order;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\Constraints\GreaterThan;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class OrderType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $user = $options['user'];

        $builder
            ->add('menu', EntityType::class, [
                'class' => Menu::class,
                'choice_label' => function (Menu $menu) {
                    return sprintf(
                        '%s - %s€/pers (min. %d personnes)',
                        $menu->getName(),
                        number_format($menu->getPricePerPerson() / 100, 2, ',', ' '),
                        $menu->getNbPersonMin()
                    );
                },
                'label' => 'Menu',
                'placeholder' => 'Sélectionnez un menu',
                'attr' => [
                    'class' => 'form-select',
                    'data-order-target' => 'menuSelect'
                ],
                'constraints' => [
                    new NotBlank([
                        'message' => 'Veuillez sélectionner un menu',
                    ]),
                ],
                'mapped' => false,
            ])
            ->add('numberOfPersons', IntegerType::class, [
                'label' => 'Nombre de personnes',
                'attr' => [
                    'class' => 'form-control',
                    'min' => 1,
                    'data-order-target' => 'personsInput'
                ],
                'constraints' => [
                    new NotBlank([
                        'message' => 'Le nombre de personnes ne peut pas être vide',
                    ]),
                    new GreaterThan([
                        'value' => 0,
                        'message' => 'Le nombre de personnes doit être supérieur à 0',
                    ]),
                ],
            ])
            ->add('deliveryDateTime', DateTimeType::class, [
                'label' => 'Date et heure de livraison souhaitée',
                'widget' => 'single_text',
                'attr' => [
                    'class' => 'form-control',
                ],
                'help' => 'Minimum 48h à l\'avance',
                'constraints' => [
                    new NotBlank([
                        'message' => 'La date de livraison ne peut pas être vide',
                    ]),
                    new Callback([
                        'callback' => function ($value, ExecutionContextInterface $context) {
                            if ($value instanceof \DateTimeInterface) {
                                $now = new \DateTimeImmutable();
                                $minDeliveryDate = $now->modify('+48 hours');

                                if ($value < $minDeliveryDate) {
                                    $context->buildViolation('La livraison doit être prévue au minimum 48h à l\'avance')
                                        ->addViolation();
                                }
                            }
                        },
                    ]),
                ],
            ])
            ->add('deliveryAddress', EntityType::class, [
                'class' => Address::class,
                'choice_label' => function (Address $address) {
                    $label = $address->getLabel() ? $address->getLabel() . ' - ' : '';
                    return sprintf(
                        '%s%s, %s %s',
                        $label,
                        $address->getStreet(),
                        $address->getPostalCode(),
                        $address->getCity()
                    );
                },
                'label' => 'Adresse de livraison',
                'placeholder' => 'Sélectionnez une adresse',
                'choices' => $user ? $user->getAddresses() : [],
                'attr' => [
                    'class' => 'form-select',
                    'data-order-target' => 'addressSelect'
                ],
                'help' => 'Vous pouvez gérer vos adresses dans votre compte',
                'constraints' => [
                    new NotBlank([
                        'message' => 'Veuillez sélectionner une adresse de livraison',
                    ]),
                ],
                'mapped' => false,
            ])
            ->add('hasMaterialLoan', CheckboxType::class, [
                'label' => 'Je souhaite emprunter du matériel (vaisselle, couverts, etc.)',
                'required' => false,
                'attr' => [
                    'class' => 'form-check-input'
                ],
                'help' => 'Le matériel devra être retourné dans un délai de 10 jours',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Order::class,
            'user' => null,
        ]);

        $resolver->setAllowedTypes('user', ['null', 'App\Entity\User']);
    }
}