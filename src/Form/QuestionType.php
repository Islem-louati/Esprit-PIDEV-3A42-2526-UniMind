<?php

namespace App\Form;

use App\Entity\Question;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

class QuestionType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('texte', TextareaType::class, [
                'label'    => 'Texte de la question *',
                'required' => true,
                'attr'     => [
                    'rows'        => 3,
                    'class'       => 'form-control',
                    'placeholder' => 'Entrez le texte de la question...'
                ],
                'constraints' => [
                    new NotBlank(['message' => 'Le champ « Texte de la question » ne peut pas être vide.']),
                ]
            ])
            ->add('type_question', ChoiceType::class, [
                'label'   => 'Type de question',
                'choices' => [
                    'Likert (échelle)'    => 'likert',
                    'Choix multiple'      => 'choix_multiple',
                    'Oui/Non'             => 'oui_non',
                    'Texte libre'         => 'texte_libre',
                    'Échelle numérique'   => 'echelle'
                ],
                'required' => true,
                'attr'     => [
                    'class' => 'form-select question-type-selector'
                ]
            ])
            ->add('options_quest', CollectionType::class, [
                'label'        => 'Options de réponse',
                'entry_type'   => TextType::class,
                'entry_options' => [
                    'label' => false,
                    'attr'  => [
                        'class'       => 'form-control option-input',
                        'placeholder' => 'Entrez une option...'
                    ]
                ],
                'allow_add'    => true,
                'allow_delete' => true,
                'delete_empty' => true,
                'prototype'    => true,
                'by_reference' => false,
                'required'     => false,
                'attr'         => [
                    'class' => 'options-collection'
                ]
            ])
            ->add('score_options', CollectionType::class, [
                'label'        => 'Scores des options',
                'entry_type'   => IntegerType::class,
                'entry_options' => [
                    'label' => false,
                    'attr'  => [
                        'class'       => 'form-control score-input',
                        'placeholder' => 'Score...'
                    ]
                ],
                'allow_add'    => true,
                'allow_delete' => true,
                'delete_empty' => true,
                'prototype'    => true,
                'by_reference' => false,
                'required'     => false,
                'attr'         => [
                    'class' => 'scores-collection'
                ]
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Question::class,
        ]);
    }
}