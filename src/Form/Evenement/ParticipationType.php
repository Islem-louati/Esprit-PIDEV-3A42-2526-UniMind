<?php

namespace App\Form\Evenement;

use App\Entity\Evenement;
use App\Entity\Participation;
use App\Entity\User;
use App\Enum\RoleType;
use App\Enum\StatutParticipation;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Formulaire de création/édition d'une participation.
 */
class ParticipationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var User|null $organisateur */
        $organisateur = $options['organisateur_evenements'] ?? null;

        $builder
            ->add('evenement', EntityType::class, [
                'label' => 'Événement',
                'class' => Evenement::class,
                'choice_label' => 'titre',
                'query_builder' => function (EntityRepository $er) use ($organisateur) {
                    $qb = $er->createQueryBuilder('e')
                        ->orderBy('e.dateDebut', 'DESC');

                    if ($organisateur !== null) {
                        $qb->andWhere('e.organisateur = :organisateur')
                            ->setParameter('organisateur', $organisateur);
                    }

                    return $qb;
                },
                'placeholder' => 'Choisir un événement',
            ])
            ->add('etudiant', EntityType::class, [
                'label' => 'Étudiant',
                'class' => User::class,
                'choice_label' => 'fullName',
                'query_builder' => function (EntityRepository $er) {
                    return $er->createQueryBuilder('u')
                        ->andWhere('u.role = :role')
                        ->setParameter('role', RoleType::ETUDIANT->value)
                        ->orderBy('u.nom', 'ASC');
                },
                'placeholder' => 'Choisir un étudiant',
            ])
            ->add('dateInscription', DateTimeType::class, [
                'label' => 'Date d\'inscription',
                'widget' => 'single_text',
            ])
            ->add('statut', EnumType::class, [
                'label' => 'Statut',
                'class' => StatutParticipation::class,
                'choice_label' => fn (StatutParticipation $s) => $s->label(),
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Participation::class,
            'organisateur_evenements' => null,
        ]);
    }
}
