<?php

namespace App\Form;

use App\Entity\Questionnaire;
use App\Enum\TypeQuestionnaire;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\PositiveOrZero;
use Symfony\Component\Validator\Constraints\Positive;

class QuestionnaireType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('code', TextType::class, [
                'label' => 'Code',
                'required' => true,
                'attr' => ['readonly' => true],
                'constraints' => [
                    new NotBlank(['message' => 'Le code est obligatoire.'])
                ]
            ])
            ->add('nom', TextType::class, [
                'label' => 'Nom',
                'required' => true,
                'constraints' => [
                    new NotBlank(['message' => 'Le nom est obligatoire.'])
                ]
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'required' => false
            ])
            ->add('type', ChoiceType::class, [
                'label' => 'Type',
                'choices' => $this->getTypeChoices(),
                'required' => true,
                'placeholder' => 'Sélectionnez un type',
                'constraints' => [
                    new NotBlank(['message' => 'Le type est obligatoire.'])
                ]
            ])
            ->add('interpretat_legere', TextareaType::class, [
                'label' => 'Interprétation légère',
                'required' => false
            ])
            ->add('interpretat_modere', TextareaType::class, [
                'label' => 'Interprétation modérée',
                'required' => false
            ])
            ->add('interpretat_severe', TextareaType::class, [
                'label' => 'Interprétation sévère',
                'required' => false
            ])
            ->add('seuil_leger', IntegerType::class, [
                'label' => 'Seuil léger',
                'required' => true,
                'constraints' => [
                    new NotBlank(['message' => 'Le seuil léger est obligatoire.']),
                    new PositiveOrZero(['message' => 'Le seuil léger doit être positif ou zéro.'])
                ]
            ])
            ->add('seuil_modere', IntegerType::class, [
                'label' => 'Seuil modéré',
                'required' => true,
                'constraints' => [
                    new NotBlank(['message' => 'Le seuil modéré est obligatoire.']),
                    new PositiveOrZero(['message' => 'Le seuil modéré doit être positif ou zéro.'])
                ]
            ])
            ->add('seuil_severe', IntegerType::class, [
                'label' => 'Seuil sévère',
                'required' => true,
                'constraints' => [
                    new NotBlank(['message' => 'Le seuil sévère est obligatoire.']),
                    new PositiveOrZero(['message' => 'Le seuil sévère doit être positif ou zéro.'])
                ]
            ])
            ->add('nbre_questions', IntegerType::class, [
                'label' => 'Nombre de questions',
                'required' => true,
                'constraints' => [
                    new NotBlank(['message' => 'Le nombre de questions est obligatoire.']),
                    new Positive(['message' => 'Le nombre de questions doit être positif.'])
                ]
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Questionnaire::class,
            'csrf_protection' => true,
        ]);
    }

    private function getTypeChoices(): array
    {
        $choices = [];
        foreach (TypeQuestionnaire::cases() as $type) {
            $choices[$type->getLabel()] = $type->value;
        }
        return $choices;
    }
}