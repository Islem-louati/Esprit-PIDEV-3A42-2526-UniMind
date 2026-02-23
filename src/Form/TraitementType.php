<?php

namespace App\Form;

use App\Entity\Traitement;
use App\Entity\User;
use App\Enum\CategorieTraitement;
use App\Enum\PrioriteTraitement;
use App\Enum\StatutTraitement;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TraitementType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('titre', TextType::class, [
                'label' => 'Titre du traitement',
                'required' => true,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Entrez le titre du traitement'
                ]
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'required' => true,
                'attr' => [
                    'class' => 'form-control',
                    'rows' => 4,
                    'placeholder' => 'Décrivez le traitement en détail'
                ]
            ])
            ->add('type', TextType::class, [
                'label' => 'Type de traitement',
                'required' => true,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Ex: Thérapie cognitive, Médication, etc.'
                ]
            ])
            ->add('categorie', ChoiceType::class, [
                'label' => 'Catégorie',
                'required' => true,
                'choices' => array_combine(
                    array_map(fn($case) => $case->getLabel(), CategorieTraitement::cases()),
                    array_map(fn($case) => $case->value, CategorieTraitement::cases())
                ),
                'attr' => [
                    'class' => 'form-control'
                ]
            ])
            ->add('duree_jours', IntegerType::class, [
                'label' => 'Durée (en jours)',
                'required' => true,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Nombre de jours'
                ]
            ])
            ->add('dosage', TextType::class, [
                'label' => 'Dosage (optionnel)',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Ex: 2 fois par jour'
                ]
            ])
            ->add('date_debut', DateType::class, [
                'label' => 'Date de début',
                'widget' => 'single_text',
                'required' => true,
                'attr' => [
                    'class' => 'form-control'
                ]
            ])
            ->add('date_fin', DateType::class, [
                'label' => 'Date de fin (optionnel)',
                'widget' => 'single_text',
                'required' => false,
                'attr' => [
                    'class' => 'form-control'
                ]
            ])
            ->add('statut', ChoiceType::class, [
                'label' => 'Statut',
                'required' => true,
                'choices' => array_combine(
                    array_map(fn($case) => $case->getLabel(), StatutTraitement::cases()),
                    array_map(fn($case) => $case->value, StatutTraitement::cases())
                ),
                'attr' => [
                    'class' => 'form-control'
                ]
            ])
            ->add('priorite', ChoiceType::class, [
                'label' => 'Priorité',
                'required' => true,
                'choices' => array_combine(
                    array_map(fn($case) => $case->getLabel(), PrioriteTraitement::cases()),
                    array_map(fn($case) => $case->value, PrioriteTraitement::cases())
                ),
                'attr' => [
                    'class' => 'form-control'
                ]
            ])
            ->add('objectif_therapeutique', TextareaType::class, [
                'label' => 'Objectif thérapeutique',
                'required' => true,
                'attr' => [
                    'class' => 'form-control',
                    'rows' => 3,
                    'placeholder' => 'Quel est l\'objectif visé par ce traitement ?'
                ]
            ]);

        // Ajouter le champ étudiant seulement pour les psychologues et admins
        if ($options['show_etudiant_field'] ?? false) {
            $builder->add('etudiant', EntityType::class, [
                'label' => 'Étudiant',
                'class' => User::class,
                'choice_label' => function(User $user) {
                    return $user->getFullName() . ' (' . $user->getEmail() . ')';
                },
                'query_builder' => function ($repository) {
                    return $repository->createQueryBuilder('u')
                        ->where('u.role = :role')
                        ->setParameter('role', \App\Enum\RoleType::ETUDIANT)
                        ->orderBy('u.nom', 'ASC')
                        ->addOrderBy('u.prenom', 'ASC');
                },
                'placeholder' => 'Sélectionnez un étudiant',
                'required' => true,
                'attr' => [
                    'class' => 'form-control'
                ]
            ]);
        }

        $builder->add('submit', SubmitType::class, [
            'label' => $options['submit_label'] ?? 'Enregistrer',
            'attr' => [
                'class' => 'btn btn-primary'
            ]
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Traitement::class,
            'submit_label' => 'Enregistrer',
            'show_etudiant_field' => false
        ]);
    }
}