<?php
// src/Form/DisponibilitePsyType.php

namespace App\Form;

use App\Entity\DisponibilitePsy;
use App\Enum\TypeConsultation;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TimeType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class DisponibilitePsyType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('date_dispo', DateType::class, [
                'widget' => 'single_text',
                'label' => 'Date',
                'attr' => [
                    'class' => 'form-control',
                ],
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Veuillez sélectionner une date.']),
                    new Assert\Date()
                ]
            ])
            ->add('heure_debut', TimeType::class, [
                'widget' => 'single_text',
                'label' => 'Heure de début',
                'attr' => [
                    'class' => 'form-control',
                ],
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Veuillez sélectionner une heure de début.'])
                ]
            ])
            ->add('heure_fin', TimeType::class, [
                'widget' => 'single_text',
                'label' => 'Heure de fin',
                'attr' => [
                    'class' => 'form-control',
                ],
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Veuillez sélectionner une heure de fin.'])
                ]
            ])
            ->add('type_consult', ChoiceType::class, [
                'label' => 'Type de consultation',
                'choices' => [
                    'Présentiel' => TypeConsultation::PRESENTIEL->value,
                    'En ligne' => TypeConsultation::EN_LIGNE->value,
                ],
                'attr' => [
                    'class' => 'form-control',
                ],
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Veuillez sélectionner un type de consultation.'])
                ]
            ])
            ->add('lieu', TextType::class, [
                'label' => 'Lieu (optionnel)',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Ex: Bureau 203, Bâtiment A'
                ]
            ]);
    }



    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => DisponibilitePsy::class,
            'constraints' => [
                new Assert\Callback(function (?DisponibilitePsy $data, ExecutionContextInterface $context) {
                    if (!$data) {
                        return;
                    }

                    $date = $data->getDateDispo();
                    $heureDebut = $data->getHeureDebut();
                    $heureFin = $data->getHeureFin();

                    if ($date instanceof \DateTimeInterface) {
                        $today = new \DateTime('today');
                        $dateOnly = new \DateTime($date->format('Y-m-d'));
                        if ($dateOnly < $today) {
                            $context->buildViolation('La date doit être aujourd\'hui ou dans le futur.')
                                ->atPath('date_dispo')
                                ->addViolation();
                        } elseif ($dateOnly == $today && $heureDebut instanceof \DateTimeInterface) {
                            $now = new \DateTime();
                            $heureDebutToday = new \DateTime($now->format('Y-m-d') . ' ' . $heureDebut->format('H:i:s'));
                            if ($heureDebutToday <= $now) {
                                $context->buildViolation('L\'heure de début doit être dans le futur.')
                                    ->atPath('heure_debut')
                                    ->addViolation();
                            }
                        }
                    }

                    if ($heureDebut instanceof \DateTimeInterface && $heureFin instanceof \DateTimeInterface) {
                        if ($heureFin <= $heureDebut) {
                            $context->buildViolation('L\'heure de fin doit être postérieure à l\'heure de début.')
                                ->atPath('heure_fin')
                                ->addViolation();
                        }
                    }
                })
            ]
        ]);
    }
}
