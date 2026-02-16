<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class CheckoutType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            // Informations client
            ->add('prenom', TextType::class, [
                'label' => 'Prénom',
                'attr' => ['class' => 'form-input', 'placeholder' => 'Votre prénom'],
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Le prénom est requis']),
                    new Assert\Length(['min' => 2, 'max' => 50])
                ]
            ])
            ->add('nom', TextType::class, [
                'label' => 'Nom',
                'attr' => ['class' => 'form-input', 'placeholder' => 'Votre nom'],
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Le nom est requis']),
                    new Assert\Length(['min' => 2, 'max' => 50])
                ]
            ])
            ->add('email', EmailType::class, [
                'label' => 'Email',
                'attr' => ['class' => 'form-input', 'placeholder' => 'votre@email.com'],
                'constraints' => [
                    new Assert\NotBlank(['message' => 'L\'email est requis']),
                    new Assert\Email(['message' => 'Email invalide'])
                ]
            ])
            ->add('telephone', TelType::class, [
                'label' => 'Téléphone',
                'attr' => ['class' => 'form-input', 'placeholder' => '06 12 34 56 78'],
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Le téléphone est requis']),
                    new Assert\Regex([
                        'pattern' => '/^0[1-9][0-9]{8}$/',
                        'message' => 'Numéro de téléphone invalide'
                    ])
                ]
            ])

            // Adresse de livraison
            ->add('adresse', TextType::class, [
                'label' => 'Adresse',
                'attr' => ['class' => 'form-input', 'placeholder' => 'Numéro et nom de rue'],
                'constraints' => [
                    new Assert\NotBlank(['message' => 'L\'adresse est requise']),
                    new Assert\Length(['min' => 5, 'max' => 200])
                ]
            ])
            ->add('complementAdresse', TextType::class, [
                'label' => 'Complément d\'adresse',
                'required' => false,
                'attr' => ['class' => 'form-input', 'placeholder' => 'Appartement, étage, etc. (optionnel)']
            ])
            ->add('codePostal', TextType::class, [
                'label' => 'Code postal',
                'attr' => ['class' => 'form-input', 'placeholder' => '75001'],
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Le code postal est requis']),
                    new Assert\Regex([
                        'pattern' => '/^[0-9]{5}$/',
                        'message' => 'Code postal invalide (5 chiffres)'
                    ])
                ]
            ])
            ->add('ville', TextType::class, [
                'label' => 'Ville',
                'attr' => ['class' => 'form-input', 'placeholder' => 'Paris'],
                'constraints' => [
                    new Assert\NotBlank(['message' => 'La ville est requise']),
                    new Assert\Length(['min' => 2, 'max' => 100])
                ]
            ])
            ->add('pays', ChoiceType::class, [
                'label' => 'Pays',
                'choices' => [
                    'France' => 'FR',
                    'Belgique' => 'BE',
                    'Suisse' => 'CH',
                    'Luxembourg' => 'LU',
                    'Canada' => 'CA'
                ],
                'data' => 'FR',
                'attr' => ['class' => 'form-select'],
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Le pays est requis'])
                ]
            ])

            // Notes de commande
            ->add('notes', TextareaType::class, [
                'label' => 'Notes ou instructions particulières',
                'required' => false,
                'attr' => [
                    'class' => 'form-textarea',
                    'rows' => 4,
                    'placeholder' => 'Des instructions de livraison ou des demandes spéciales ? (optionnel)'
                ]
            ])

            // Mode de paiement
            ->add('modePaiement', ChoiceType::class, [
                'label' => 'Mode de paiement',
                'choices' => [
                    'Carte bancaire' => 'carte',
                    'Virement bancaire' => 'virement',
                    'PayPal' => 'paypal'
                ],
                'data' => 'carte',
                'expanded' => true,
                'attr' => ['class' => 'form-radio-group'],
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Veuillez choisir un mode de paiement'])
                ]
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'csrf_protection' => true,
        ]);
    }
}
