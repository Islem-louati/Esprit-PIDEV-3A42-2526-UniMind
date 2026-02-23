<?php

namespace App\Form;

use App\Entity\SeanceMeditation;
use App\Entity\CategorieMeditation;
use App\Entity\Enum\TypeFichier;
use App\Entity\Enum\TypeNiveau;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\Positive;
use Symfony\Component\Validator\Constraints\LessThanOrEqual;

class SeanceMeditationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('titre', TextType::class, [
                'label' => 'Titre de la séance',
                'constraints' => [
                    new NotBlank(['message' => 'Le titre est obligatoire']),
                    new Length([
                        'min' => 5,
                        'max' => 150,
                        'minMessage' => 'Le titre doit faire au moins {{ limit }} caractères',
                        'maxMessage' => 'Le titre ne peut pas dépasser {{ limit }} caractères'
                    ])
                ]
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'constraints' => [
                    new NotBlank(['message' => 'La description est obligatoire']),
                    new Length([
                        'min' => 30,
                        'minMessage' => 'La description doit faire au moins {{ limit }} caractères'
                    ])
                ],
                'attr' => ['rows' => 5]
            ])
            ->add('typeFichier', ChoiceType::class, [
                'label' => 'Type de fichier',
                'choices' => [
                    'Audio' => 'audio',
                    'Vidéo' => 'video'
                ],
                'placeholder' => 'Sélectionnez le type',
                'constraints' => [
                    new NotBlank(['message' => 'Le type de fichier est obligatoire'])
                ]
            ])
            ->add('duree', IntegerType::class, [
                'label' => 'Durée (en secondes)',
                'help' => 'Durée en secondes (ex: 300 pour 5 minutes)',
                'constraints' => [
                    new NotBlank(['message' => 'La durée est obligatoire']),
                    new Positive(['message' => 'La durée doit être positive']),
                    new LessThanOrEqual([
                        'value' => 3600,
                        'message' => 'La durée ne peut pas dépasser 3600 secondes (1 heure)'
                    ])
                ]
            ])
            ->add('niveau', ChoiceType::class, [
                'label' => 'Niveau',
                'choices' => [
                    'Débutant' => 'débutant',
                    'Intermédiaire' => 'intermédiaire',
                    'Avancé' => 'avancé'
                ],
                'placeholder' => 'Sélectionnez le niveau',
                'constraints' => [
                    new NotBlank(['message' => 'Le niveau est obligatoire'])
                ]
            ])
            ->add('categorie', EntityType::class, [
                'label' => 'Catégorie',
                'class' => CategorieMeditation::class,
                'choice_label' => 'nom',
                'placeholder' => 'Sélectionnez une catégorie',
                'constraints' => [
                    new NotBlank(['message' => 'La catégorie est obligatoire'])
                ]
            ])
            ->add('isActif', CheckboxType::class, [
                'label' => 'Séance active',
                'required' => false,
                'data' => true
            ]);

        // Ajouter le champ fichier conditionnellement
        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) {
            $seance = $event->getData();
            $form = $event->getForm();

            // Si nouvelle séance (pas d'ID)
            if (!$seance || null === $seance->getSeanceId()) {
                $form->add('fichier', FileType::class, [
                    'label' => 'Fichier audio/vidéo *',
                    'mapped' => false,
                    'required' => true,
                    'help' => 'Téléchargez un fichier audio (MP3) ou vidéo (MP4) - obligatoire',
                    'constraints' => [
                        new NotBlank(['message' => 'Le fichier est obligatoire pour une nouvelle séance']),
                        new \Symfony\Component\Validator\Constraints\File([
                            'maxSize' => '50M',
                            'mimeTypes' => [
                                'audio/mpeg',
                                'audio/mp3',
                                'video/mp4',
                                'video/webm'
                            ],
                            'mimeTypesMessage' => 'Veuillez télécharger un fichier audio ou vidéo valide',
                        ])
                    ]
                ]);
            } else {
                // Pour l'édition
                $form->add('fichier', FileType::class, [
                    'label' => 'Fichier audio/vidéo',
                    'mapped' => false,
                    'required' => false,
                    'help' => 'Téléchargez un fichier audio (MP3) ou vidéo (MP4) - optionnel',
                    'constraints' => [
                        new \Symfony\Component\Validator\Constraints\File([
                            'maxSize' => '50M',
                            'mimeTypes' => [
                                'audio/mpeg',
                                'audio/mp3',
                                'video/mp4',
                                'video/webm'
                            ],
                            'mimeTypesMessage' => 'Veuillez télécharger un fichier audio ou vidéo valide',
                        ])
                    ]
                ]);
            }
        });
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => SeanceMeditation::class,
        ]);
    }
}