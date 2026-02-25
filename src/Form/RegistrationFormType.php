<?php

namespace App\Form;

use App\Entity\User;
use App\Enum\RoleType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\Regex;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

class RegistrationFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nom', TextType::class, [
                'label' => 'Nom *',
                'constraints' => [
                    new NotBlank(['message' => 'Le nom est obligatoire.']),
                    new Length([
                        'min' => 2,
                        'max' => 50,
                        'minMessage' => 'Le nom doit contenir au moins {{ limit }} caractères.',
                        'maxMessage' => 'Le nom ne peut pas dépasser {{ limit }} caractères.',
                    ]),
                ],
                'attr' => [
                    'class' => 'form-control form-control-lg',
                    'placeholder' => 'Votre nom'
                ]
            ])
            ->add('prenom', TextType::class, [
                'label' => 'Prénom *',
                'constraints' => [
                    new NotBlank(['message' => 'Le prénom est obligatoire.']),
                    new Length([
                        'min' => 2,
                        'max' => 50,
                        'minMessage' => 'Le prénom doit contenir au moins {{ limit }} caractères.',
                        'maxMessage' => 'Le prénom ne peut pas dépasser {{ limit }} caractères.',
                    ]),
                ],
                'attr' => [
                    'class' => 'form-control form-control-lg',
                    'placeholder' => 'Votre prénom'
                ]
            ])
            ->add('email', EmailType::class, [
                'label' => 'Email *',
                'constraints' => [
                    new NotBlank(['message' => 'L\'email est obligatoire.']),
                    new Email(['message' => 'Veuillez entrer une adresse email valide.']),
                ],
                'attr' => [
                    'class' => 'form-control form-control-lg',
                    'placeholder' => 'votre@email.com'
                ]
            ])
            ->add('cin', TextType::class, [
                'label' => 'CIN *',
                'constraints' => [
                    new NotBlank(['message' => 'Le CIN est obligatoire.']),
                    new Regex([
                        'pattern' => '/^[A-Z]{1,2}[0-9]{6}$/',
                        'message' => 'Format CIN invalide (ex: AB123456).',
                    ]),
                ],
                'attr' => [
                    'class' => 'form-control form-control-lg',
                    'placeholder' => 'AB123456'
                ]
            ])
            ->add('role', ChoiceType::class, [
                'label' => 'Rôle *',
                'choices' => [
                    'Étudiant' => RoleType::ETUDIANT,
                    'Psychologue' => RoleType::PSYCHOLOGUE,
                    'Responsable Étudiant' => RoleType::RESPONSABLE_ETUDIANT,
                ],
                'multiple' => false,
                'expanded' => false,
                'constraints' => [
                    new NotBlank(['message' => 'Le rôle est obligatoire.']),
                ],
                'attr' => [
                    'class' => 'form-control form-control-lg',
                    'style' => 'display: none;' // Caché mais accessible
                ]
            ])
            ->add('plainPassword', RepeatedType::class, [
                'type' => PasswordType::class,
                'mapped' => false,
                'invalid_message' => 'Les mots de passe doivent correspondre.',
                'first_options' => [
                    'label' => 'Mot de passe *',
                    'constraints' => [
                        new NotBlank(['message' => 'Le mot de passe est obligatoire.']),
                        new Length([
                            'min' => 8,
                            'minMessage' => 'Le mot de passe doit contenir au moins {{ limit }} caractères.',
                            'max' => 4096,
                        ]),
                        new Regex([
                            'pattern' => '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/',
                            'message' => 'Le mot de passe doit contenir au moins une majuscule, une minuscule, un chiffre et un caractère spécial (@$!%*?&).',
                        ]),
                    ],
                    'attr' => [
                        'class' => 'form-control form-control-lg',
                        'placeholder' => 'Mot de passe'
                    ]
                ],
                'second_options' => [
                    'label' => 'Confirmer le mot de passe *',
                    'attr' => [
                        'class' => 'form-control form-control-lg',
                        'placeholder' => 'Confirmer mot de passe'
                    ]
                ],
            ])
            // Champs conditionnels pour Étudiant
            ->add('identifiant', TextType::class, [
                'label' => 'Identifiant étudiant *',
                'required' => false,
                'constraints' => [
                    new Length([
                        'max' => 100,
                        'maxMessage' => 'L\'identifiant ne peut pas dépasser {{ limit }} caractères.',
                    ]),
                ],
                'attr' => [
                    'class' => 'form-control form-control-lg',
                    'placeholder' => 'Votre identifiant étudiant'
                ]
            ])
            ->add('nom_etablissement', TextType::class, [
                'label' => 'Nom de l\'établissement *',
                'required' => false,
                'constraints' => [
                    new Length([
                        'max' => 255,
                        'maxMessage' => 'Le nom de l\'établissement ne peut pas dépasser {{ limit }} caractères.',
                    ]),
                ],
                'attr' => [
                    'class' => 'form-control form-control-lg',
                    'placeholder' => 'Nom de votre établissement'
                ]
            ])
            // Champs conditionnels pour Psychologue
            ->add('specialite', TextType::class, [
                'label' => 'Spécialité *',
                'required' => false,
                'constraints' => [
                    new Length([
                        'max' => 255,
                        'maxMessage' => 'La spécialité ne peut pas dépasser {{ limit }} caractères.',
                    ]),
                ],
                'attr' => [
                    'class' => 'form-control form-control-lg',
                    'placeholder' => 'Votre spécialité'
                ]
            ])
            ->add('adresse', TextType::class, [
                'label' => 'Adresse *',
                'required' => false,
                'constraints' => [
                    new Length([
                        'max' => 255,
                        'maxMessage' => 'L\'adresse ne peut pas dépasser {{ limit }} caractères.',
                    ]),
                ],
                'attr' => [
                    'class' => 'form-control form-control-lg',
                    'placeholder' => 'Votre adresse professionnelle'
                ]
            ])
            ->add('telephone', TextType::class, [
                'label' => 'Téléphone *',
                'required' => false,
                'constraints' => [
                    new Regex([
                        'pattern' => '/^[0-9]{10}$/',
                        'message' => 'Le téléphone doit contenir 10 chiffres.',
                    ]),
                    new Length([
                        'max' => 20,
                        'maxMessage' => 'Le téléphone ne peut pas dépasser {{ limit }} caractères.',
                    ]),
                ],
                'attr' => [
                    'class' => 'form-control form-control-lg',
                    'placeholder' => '0601020304'
                ]
            ])
            // Champs conditionnels pour Responsable
            ->add('poste', TextType::class, [
                'label' => 'Poste *',
                'required' => false,
                'constraints' => [
                    new Length([
                        'max' => 255,
                        'maxMessage' => 'Le poste ne peut pas dépasser {{ limit }} caractères.',
                    ]),
                ],
                'attr' => [
                    'class' => 'form-control form-control-lg',
                    'placeholder' => 'Votre poste'
                ]
            ])
            ->add('etablissement', TextType::class, [
                'label' => 'Établissement *',
                'required' => false,
                'constraints' => [
                    new Length([
                        'max' => 255,
                        'maxMessage' => 'L\'établissement ne peut pas dépasser {{ limit }} caractères.',
                    ]),
                ],
                'attr' => [
                    'class' => 'form-control form-control-lg',
                    'placeholder' => 'Nom de l\'établissement'
                ]
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}