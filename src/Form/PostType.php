<?php

namespace App\Form;

use App\Entity\Post;
use App\Entity\CategorieMeditation;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Length;

class PostType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('titre', TextType::class, [
                'label' => 'Titre de la discussion',
                'attr' => [
                    'placeholder' => 'Donnez un titre à votre discussion',
                    'class' => 'form-control'
                ],
                'constraints' => [
                    new NotBlank(['message' => 'Le titre est obligatoire']),
                    new Length([
                        'min' => 5,
                        'max' => 200,
                        'minMessage' => 'Le titre doit faire au moins {{ limit }} caractères',
                        'maxMessage' => 'Le titre ne peut pas dépasser {{ limit }} caractères'
                    ])
                ]
            ])
            ->add('contenu', TextareaType::class, [
                'label' => 'Contenu',
                'attr' => [
                    'placeholder' => 'Partagez vos pensées, questions ou expériences...',
                    'rows' => 5,
                    'class' => 'form-control'
                ],
                'constraints' => [
                    new NotBlank(['message' => 'Le contenu est obligatoire']),
                    new Length([
                        'min' => 10,
                        'minMessage' => 'Le contenu doit faire au moins {{ limit }} caractères'
                    ])
                ]
            ])
            ->add('is_anonyme', CheckboxType::class, [
                'label' => 'Publier anonymement',
                'required' => false,
                'attr' => [
                    'class' => 'form-check-input'
                ],
                'label_attr' => [
                    'class' => 'form-check-label'
                ]])

                 ->add('categorieMeditation', EntityType::class, [
                'label' => 'Catégorie',
                'class' => CategorieMeditation::class,
                'choice_label' => 'nom',
                'required' => true,
                'placeholder' => 'Choisissez une catégorie',
                'attr' => [
                    'class' => 'form-control'
                ],
                'constraints' => [
                    new NotBlank(['message' => 'Vous devez sélectionner une catégorie'])
                ]
            ]);
            ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Post::class,
        ]);
    }
}