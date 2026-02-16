<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class ContactType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nom', TextType::class, [
                'label' => 'Nom',
                'attr' => ['class' => 'w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary'],
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Veuillez entrer votre nom']),
                    new Assert\Length(['min' => 2, 'max' => 100])
                ]
            ])
            ->add('email', EmailType::class, [
                'label' => 'Email',
                'attr' => ['class' => 'w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary'],
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Veuillez entrer votre email']),
                    new Assert\Email(['message' => 'Email invalide'])
                ]
            ])
            ->add('sujet', TextType::class, [
                'label' => 'Sujet',
                'attr' => ['class' => 'w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary'],
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Veuillez entrer un sujet']),
                    new Assert\Length(['min' => 5, 'max' => 200])
                ]
            ])
            ->add('message', TextareaType::class, [
                'label' => 'Message',
                'attr' => [
                    'class' => 'w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary',
                    'rows' => 6
                ],
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Veuillez entrer un message']),
                    new Assert\Length(['min' => 20, 'max' => 2000])
                ]
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([]);
    }
}
