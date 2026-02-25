<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
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

        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'csrf_protection' => true,
        ]);
    }
}
