<?php
namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class AvisType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('contenu', TextareaType::class, [
                'label' => 'Votre avis',
                'attr' => [
                    'rows' => 5,
                    'class' => 'form-input',
                    'placeholder' => 'Partagez votre expérience avec TDBR...',
                ],
                'constraints' => [
                    new NotBlank(['message' => 'Veuillez écrire votre avis.']),
                    new Length(['min' => 10, 'max' => 1000, 'minMessage' => 'Votre avis doit faire au moins 10 caractères.']),
                ],
            ])
            ->add('note', ChoiceType::class, [
                'label' => 'Note (optionnelle)',
                'choices' => [
                    '1 étoile' => 1,
                    '2 étoiles' => 2,
                    '3 étoiles' => 3,
                    '4 étoiles' => 4,
                    '5 étoiles' => 5,
                ],
                'required' => false,
                'expanded' => false,
                'placeholder' => '— Choisir une note —',
            ])
            ->add('photo', FileType::class, [
                'label' => 'Photo (optionnelle)',
                'required' => false,
                'constraints' => [
                    new File([
                        'maxSize' => '2M',
                        'mimeTypes' => ['image/jpeg', 'image/png', 'image/webp'],
                        'mimeTypesMessage' => 'Formats acceptés : JPG, PNG, WEBP (max 2 Mo)',
                    ]),
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([]);
    }
}
