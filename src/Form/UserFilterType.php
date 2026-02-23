<?php

namespace App\Form;

use App\Enum\RoleType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;

class UserFilterType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('role', ChoiceType::class, [
                'label' => 'Rôle',
                'required' => false,
                'choices' => [
                    'Tous les rôles' => null,
                    'Étudiant' => RoleType::ETUDIANT->value,
                    'Psychologue' => RoleType::PSYCHOLOGUE->value,
                    'Responsable Étudiant' => RoleType::RESPONSABLE_ETUDIANT->value,
                ],
                'placeholder' => false,
                'attr' => ['class' => 'form-control']
            ])
            ->add('statut', ChoiceType::class, [
                'label' => 'Statut',
                'required' => false,
                'choices' => [
                    'Tous les statuts' => null,
                    'Actif' => 'actif',
                    'Inactif' => 'inactif',
                    'En attente' => 'en_attente',
                    'Rejeté' => 'rejeté',
                ],
                'placeholder' => false,
                'attr' => ['class' => 'form-control']
            ])
            ->add('search', TextType::class, [
                'label' => 'Recherche',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Nom, prénom, email...'
                ]
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'method' => 'GET',
            'csrf_protection' => false,
        ]);
    }
}