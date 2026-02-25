<?php

namespace App\Form;

use App\Entity\User;
use App\Enum\RoleType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\Regex;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;

class UserType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nom', TextType::class, [
                'label' => 'Nom *',
                'constraints' => [
                    new NotBlank([
                        'message' => 'Le nom est obligatoire.',
                    ]),
                    new Length([
                        'min' => 2,
                        'max' => 50,
                        'minMessage' => 'Le nom doit contenir au moins {{ limit }} caractères.',
                        'maxMessage' => 'Le nom ne peut pas dépasser {{ limit }} caractères.',
                    ]),
                    new Regex([
                        'pattern' => '/^[a-zA-ZÀ-ÿ\s\-]+$/',
                        'message' => 'Le nom ne peut contenir que des lettres, espaces et tirets.',
                    ]),
                ],
                'attr' => ['class' => 'form-control']
            ])
            ->add('prenom', TextType::class, [
                'label' => 'Prénom *',
                'constraints' => [
                    new NotBlank([
                        'message' => 'Le prénom est obligatoire.',
                    ]),
                    new Length([
                        'min' => 2,
                        'max' => 50,
                        'minMessage' => 'Le prénom doit contenir au moins {{ limit }} caractères.',
                        'maxMessage' => 'Le prénom ne peut pas dépasser {{ limit }} caractères.',
                    ]),
                    new Regex([
                        'pattern' => '/^[a-zA-ZÀ-ÿ\s\-]+$/',
                        'message' => 'Le prénom ne peut contenir que des lettres, espaces et tirets.',
                    ]),
                ],
                'attr' => ['class' => 'form-control']
            ])
            ->add('email', EmailType::class, [
                'label' => 'Email *',
                'constraints' => [
                    new NotBlank([
                        'message' => 'L\'email est obligatoire.',
                    ]),
                    new Email([
                        'message' => 'Veuillez entrer une adresse email valide.',
                    ]),
                    new Length([
                        'max' => 180,
                        'maxMessage' => 'L\'email ne peut pas dépasser {{ limit }} caractères.',
                    ]),
                ],
                'attr' => ['class' => 'form-control']
            ])
            ->add('cin', TextType::class, [
                'label' => 'CIN',
                'required' => false,
                'attr' => ['class' => 'form-control']
            ])
            ->add('role', ChoiceType::class, [
                'label' => 'Rôle *',
                'choices' => [
                    'Étudiant' => RoleType::ETUDIANT,
                    'Psychologue' => RoleType::PSYCHOLOGUE,
                    'Responsable Étudiant' => RoleType::RESPONSABLE_ETUDIANT,
                    'Administrateur' => RoleType::ADMIN,
                ],
                'constraints' => [
                    new NotBlank([
                        'message' => 'Le rôle est obligatoire.',
                    ]),
                ],
                'attr' => ['class' => 'form-control']
            ])
            ->add('statut', ChoiceType::class, [
                'label' => 'Statut *',
                'choices' => [
                    'Actif' => 'actif',
                    'Inactif' => 'inactif',
                    'En attente' => 'en_attente',
                    'Rejeté' => 'rejeté',
                ],
                'constraints' => [
                    new NotBlank([
                        'message' => 'Le statut est obligatoire.',
                    ]),
                ],
                'attr' => ['class' => 'form-control']
            ])
            ->add('isActive', CheckboxType::class, [
                'label' => 'Compte actif',
                'required' => false,
                'attr' => ['class' => 'form-check-input']
            ])
            ->add('isVerified', CheckboxType::class, [
                'label' => 'Email vérifié',
                'required' => false,
                'attr' => ['class' => 'form-check-input']
            ]);

        // Ajouter le champ mot de passe seulement pour la création
        if ($options['is_creation']) {
            $builder->add('plainPassword', RepeatedType::class, [
                'type' => PasswordType::class,
                'invalid_message' => 'Les mots de passe doivent correspondre.',
                'options' => ['attr' => ['class' => 'password-field']],
                'required' => true,
                'first_options'  => ['label' => 'Mot de passe *'],
                'second_options' => ['label' => 'Confirmer le mot de passe *'],
                'mapped' => false, // Ne pas mapper directement à l'entité
            ]);
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
            'is_creation' => false, // Par défaut, ce n'est pas une création
        ]);
        
        $resolver->setAllowedTypes('is_creation', 'bool');
    }
}