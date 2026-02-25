<?php

namespace App\Form;

use App\Entity\CategorieMeditation;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Validator\Constraints as Assert;

class CategorieMeditationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nom', TextType::class, [
                'label' => 'Nom de la catégorie *',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Ex: Méditation guidée',
                    'minlength' => 3,
                    'maxlength' => 100,
                ],
                'constraints' => [
                    new Assert\NotBlank([
                        'message' => 'Le nom est obligatoire.'
                    ]),
                    new Assert\Length([
                        'min' => 3,
                        'max' => 100,
                        'minMessage' => 'Le nom doit contenir au moins {{ limit }} caractères.',
                        'maxMessage' => 'Le nom ne peut pas dépasser {{ limit }} caractères.'
                    ]),
                    new Assert\Regex([
                        'pattern' => '/^[a-zA-ZÀ-ÿ0-9\s\-_\',.!?]+$/u',
                        'message' => 'Caractères spéciaux non autorisés (accents, lettres, chiffres, espaces, tirets, apostrophes et ponctuation basique autorisés).'
                    ])
                ]
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'rows' => 4,
                    'placeholder' => 'Description de la catégorie (minimum 30 caractères)...',
                    'minlength' => 30,
                    'maxlength' => 2000,
                ],
                'constraints' => [
                    new Assert\Length([
                        'min' => 30,
                        'max' => 2000,
                        'minMessage' => 'La description doit contenir au moins {{ limit }} caractères.',
                        'maxMessage' => 'La description ne peut pas dépasser {{ limit }} caractères.'
                    ]),
                    
                ]
            ])
            ->add('iconUrl', UrlType::class, [
                'label' => 'URL de l\'icône',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'https://example.com/icon.png (optionnel)',
                    'maxlength' => 255,
                ],
                'constraints' => [
                    new Assert\Url([
                        'message' => 'Veuillez entrer une URL valide.',
                        'protocols' => ['http', 'https'],
                    ]),
                    new Assert\Length([
                        'max' => 255,
                        'maxMessage' => 'L\'URL ne peut pas dépasser {{ limit }} caractères.'
                    ])
                ],
                'help' => 'URL d\'une image pour l\'icône de la catégorie (optionnel)'
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => CategorieMeditation::class,
        ]);
    }
}