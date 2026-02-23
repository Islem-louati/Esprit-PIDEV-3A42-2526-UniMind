<?php

namespace App\Form\Evenement;

use App\Entity\Sponsor;
use App\Entity\Evenement;
use App\Entity\EvenementSponsor;
use App\Enum\TypeSponsor;
use App\Enum\TypeContribution;
use App\Enum\StatutSponsor;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Formulaire combiné pour créer un Sponsor ET une contribution (EvenementSponsor) sur la même page.
 */
class SponsorContributionType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $typeSponsorChoices = [];
        foreach (TypeSponsor::cases() as $case) {
            $typeSponsorChoices[$case->label()] = $case->value;
        }

        $typeContributionChoices = [];
        foreach (TypeContribution::cases() as $case) {
            $typeContributionChoices[$case->getLabel()] = $case->value;
        }

        $statutChoices = [];
        foreach (StatutSponsor::cases() as $case) {
            $statutChoices[$case->getLabel()] = $case->value;
        }

        // === Section Sponsor ===
        $builder
            ->add('nomSponsor', TextType::class, [
                'label' => 'Nom du sponsor',
                'attr' => ['placeholder' => 'Nom du sponsor'],
                'constraints' => [
                    new Assert\NotBlank([
                        'message' => 'Le nom du sponsor est obligatoire',
                    ]),
                ],
            ])
            ->add('typeSponsor', ChoiceType::class, [
                'label' => 'Type de sponsor',
                'choices' => $typeSponsorChoices,
                'placeholder' => 'Sélectionner un type',
                'constraints' => [
                    new Assert\NotBlank([
                        'message' => 'Le type de sponsor est obligatoire',
                    ]),
                ],
            ])
            ->add('siteWeb', UrlType::class, [
                'label' => 'Site web',
                'required' => false,
                'attr' => ['placeholder' => 'https://example.com'],
            ])
            ->add('emailContact', EmailType::class, [
                'label' => 'Email de contact',
                'attr' => ['placeholder' => 'email@example.com'],
                'constraints' => [
                    new Assert\NotBlank([
                        'message' => 'L\'email de contact est obligatoire',
                    ]),
                    new Assert\Email([
                        'message' => 'L\'email n\'est pas valide',
                    ]),
                ],
            ])
            ->add('telephone', TelType::class, [
                'label' => 'Téléphone',
                'required' => false,
                'attr' => ['placeholder' => '+216 00 000 000'],
            ])
            ->add('adresse', TextareaType::class, [
                'label' => 'Adresse',
                'required' => false,
                'attr' => ['rows' => 3],
            ])
            ->add('domaineActivite', TextType::class, [
                'label' => 'Domaine d\'activité',
                'required' => false,
                'attr' => ['placeholder' => 'Technologie, Éducation, etc.'],
            ])
            ->add('logoFile', FileType::class, [
                'label' => 'Logo (PNG/JPG/WebP)',
                'mapped' => false,
                'required' => false,
                'constraints' => [
                    new Assert\File([
                        'maxSize' => '5M',
                        'mimeTypes' => ['image/jpeg', 'image/png', 'image/webp'],
                        'mimeTypesMessage' => 'Veuillez uploader une image valide (JPG, PNG, WebP).',
                    ]),
                ],
            ])

            // === Section Contribution (EvenementSponsor) ===
            ->add('evenement', EntityType::class, [
                'label' => 'Événement',
                'class' => Evenement::class,
                'choice_label' => 'titre',
                'placeholder' => 'Sélectionner un événement',
                'required' => false,
                'help' => 'Laissez vide si vous souhaitez seulement créer le sponsor pour l’instant.',
            ])
            ->add('montantContribution', MoneyType::class, [
                'label' => 'Montant de la contribution (TND)',
                'currency' => 'TND',
                'required' => false,
                'help' => 'Exemple : 1500.50',
            ])
            ->add('typeContribution', ChoiceType::class, [
                'label' => 'Type de contribution',
                'choices' => $typeContributionChoices,
                'placeholder' => 'Sélectionner un type',
                'required' => false,
            ])
            ->add('descriptionContribution', TextareaType::class, [
                'label' => 'Description de la contribution',
                'required' => false,
                'attr' => ['rows' => 3],
            ])
            ->add('dateContribution', DateTimeType::class, [
                'label' => 'Date de contribution',
                'widget' => 'single_text',
                'required' => false,
                'data' => new \DateTime(),
            ])
            ->add('statut', ChoiceType::class, [
                'label' => 'Statut de la contribution',
                'choices' => $statutChoices,
                'required' => false,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => null, // Pas de data class : on gère deux entités
            'csrf_token_id' => 'sponsor_contribution',
        ]);
    }
}
