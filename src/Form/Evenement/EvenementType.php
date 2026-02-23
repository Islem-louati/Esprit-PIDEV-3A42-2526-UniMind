<?php

namespace App\Form\Evenement;

use App\Entity\Evenement;
use App\Entity\User;
use App\Enum\RoleType;
use App\Enum\StatutEvenement;
use App\Enum\TypeEvenement;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\FileType;

/**
 * Formulaire de création/édition d'un événement.
 */
class EvenementType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('titre', TextType::class, [
                'label' => 'Titre',
                'attr' => ['maxlength' => 200],
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'required' => false,
            ])
            ->add('type', EnumType::class, [
                'label' => 'Type d\'événement',
                'class' => TypeEvenement::class,
                'choice_label' => fn (TypeEvenement $type) => $type->getLabel(),
            ])
            ->add('dateDebut', DateTimeType::class, [
                'label' => 'Date et heure de début',
                'widget' => 'single_text',
            ])
            ->add('dateFin', DateTimeType::class, [
                'label' => 'Date et heure de fin',
                'widget' => 'single_text',
            ])
            ->add('lieu', TextType::class, [
                'label' => 'Lieu',
            ])
            ->add('capaciteMax', IntegerType::class, [
                'label' => 'Capacité maximale',
            ])
            ->add('statut', EnumType::class, [
                'label' => 'Statut',
                'class' => StatutEvenement::class,
                'choice_label' => fn (StatutEvenement $statut) => $statut->getLabel(),
            ])
            ->add('dateLimiteInscription', DateTimeType::class, [
                'label' => 'Date limite d\'inscription',
                'required' => false,
                'widget' => 'single_text',
            ])
            ->add('imageFile', FileType::class, [
                'label' => 'Affiche/Image',
                'mapped' => false,
                'required' => false,
                'attr' => [
                    'accept' => 'image/jpeg,image/png,image/webp',
                ],
            ])
        ;

        if ($options['allow_organisateur']) {
            $builder->add('organisateur', EntityType::class, [
                'label' => 'Organisateur',
                'class' => User::class,
                'choice_label' => 'fullName',
                'query_builder' => static function (EntityRepository $er) {
                    return $er->createQueryBuilder('u')
                        ->andWhere('u.role = :role')
                        ->setParameter('role', RoleType::RESPONSABLE_ETUDIANT)
                        ->orderBy('u.prenom', 'ASC')
                        ->addOrderBy('u.nom', 'ASC');
                },
            ]);
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Evenement::class,
            'validation_groups' => ['Default', 'creation'],
            'allow_organisateur' => true,
        ]);
    }
}
