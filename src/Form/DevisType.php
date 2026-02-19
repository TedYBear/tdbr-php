<?php
namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class DevisType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nom', TextType::class, [
                'label' => 'Nom complet',
                'attr' => ['class' => 'form-input', 'placeholder' => 'Votre nom'],
                'constraints' => [new Assert\NotBlank(), new Assert\Length(['min' => 2])],
            ])
            ->add('email', EmailType::class, [
                'label' => 'Email',
                'attr' => ['class' => 'form-input', 'placeholder' => 'votre@email.com'],
                'constraints' => [new Assert\NotBlank(), new Assert\Email()],
            ])
            ->add('telephone', TelType::class, [
                'label' => 'Téléphone',
                'required' => false,
                'attr' => ['class' => 'form-input', 'placeholder' => '06 12 34 56 78 (optionnel)'],
            ])
            ->add('concept', TextareaType::class, [
                'label' => 'Décrivez votre projet',
                'attr' => ['class' => 'form-input', 'rows' => 5, 'placeholder' => 'Décrivez votre idée de création personnalisée...'],
                'constraints' => [new Assert\NotBlank(), new Assert\Length(['min' => 20])],
            ])
            ->add('contexte', TextareaType::class, [
                'label' => 'Contexte d\'utilisation',
                'required' => false,
                'attr' => ['class' => 'form-input', 'rows' => 3, 'placeholder' => 'Événement, cadeau entreprise, merchandising...'],
            ])
            ->add('quantite', ChoiceType::class, [
                'label' => 'Quantité souhaitée',
                'choices' => [
                    '1 à 10 pièces' => '1-10',
                    '11 à 50 pièces' => '11-50',
                    '51 à 100 pièces' => '51-100',
                    'Plus de 100 pièces' => '100+',
                ],
                'expanded' => true,
                'multiple' => false,
                'constraints' => [new Assert\NotBlank()],
            ])
            ->add('moyenContact', ChoiceType::class, [
                'label' => 'Préférence de contact',
                'choices' => [
                    'Par email' => 'email',
                    'Par téléphone' => 'telephone',
                    'Pas de préférence' => 'pas-de-preference',
                ],
                'expanded' => true,
                'multiple' => false,
                'constraints' => [new Assert\NotBlank()],
            ])
            ->add('messageAdditionnel', TextareaType::class, [
                'label' => 'Message complémentaire',
                'required' => false,
                'attr' => ['class' => 'form-input', 'rows' => 3, 'placeholder' => 'Toute information supplémentaire...'],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['csrf_protection' => true]);
    }
}
