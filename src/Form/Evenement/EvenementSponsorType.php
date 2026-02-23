<?php

namespace App\Form\Evenement;

use App\Entity\Evenement;
use App\Entity\EvenementSponsor;
use App\Entity\Sponsor;
use App\Enum\StatutSponsor;
use App\Enum\TypeContribution;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\EnumType;

/**
 * Formulaire de création/édition d'un lien Événement–Sponsor
 */
class EvenementSponsorType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $evenementFieldOptions = [
            'label' => 'Événement',
            'class' => Evenement::class,
            'choice_label' => 'titre',
            'placeholder' => 'Choisir un événement',
        ];

        if ($options['evenement_choices'] !== null) {
            $evenementFieldOptions['choices'] = $options['evenement_choices'];
        }

        $builder
            ->add('evenement', EntityType::class, $evenementFieldOptions)
            ->add('sponsor', EntityType::class, [
                'label' => 'Sponsor',
                'class' => Sponsor::class,
                'choice_label' => 'nomSponsor',
                'placeholder' => 'Choisir un sponsor',
            ])
            ->add('montantContribution', TextType::class, [
                'label' => 'Montant (TND)',
                'attr' => ['placeholder' => '0.00'],
            ])
            ->add('typeContribution', EnumType::class, [
                'label' => 'Type de contribution',
                'class' => TypeContribution::class,
                'choice_label' => fn (TypeContribution $t) => $t->getLabel(),
            ])
            ->add('descriptionContribution', TextareaType::class, [
                'label' => 'Description de la contribution',
                'required' => false,
            ])
            ->add('dateContribution', DateTimeType::class, [
                'label' => 'Date de la contribution',
                'widget' => 'single_text',
            ])
            ->add('statut', EnumType::class, [
                'label' => 'Statut',
                'class' => StatutSponsor::class,
                'choice_label' => fn (StatutSponsor $s) => $s->getLabel(),
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => EvenementSponsor::class,
            'evenement_choices' => null,
        ]);
    }
}
