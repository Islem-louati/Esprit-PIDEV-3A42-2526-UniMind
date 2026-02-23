<?php

namespace App\Form\Evenement;

use App\Entity\Sponsor;
use App\Enum\StatutSponsor;
use App\Enum\TypeSponsor;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Formulaire de création/édition d'un sponsor.
 */
class SponsorType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nomSponsor', TextType::class, [
                'label' => 'Nom du sponsor',
                'attr' => ['maxlength' => 150],
            ])
            ->add('typeSponsor', EnumType::class, [
                'label' => 'Type de sponsor',
                'class' => TypeSponsor::class,
                'choice_label' => fn (TypeSponsor $type) => $type->label(),
            ])
            ->add('emailContact', EmailType::class, [
                'label' => 'Email de contact',
            ])
            ->add('siteWeb', UrlType::class, [
                'label' => 'Site web',
                'required' => false,
            ])
            ->add('telephone', TextType::class, [
                'label' => 'Téléphone',
                'required' => false,
            ])
            ->add('adresse', TextareaType::class, [
                'label' => 'Adresse',
                'required' => false,
            ])
            ->add('domaineActivite', TextType::class, [
                'label' => 'Domaine d\'activité',
                'required' => false,
                'attr' => ['maxlength' => 100],
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
            ->add('statut', EnumType::class, [
                'label' => 'Statut',
                'class' => StatutSponsor::class,
                'choice_label' => fn (StatutSponsor $statut) => $statut->getLabel(),
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Sponsor::class,
        ]);
    }
}
