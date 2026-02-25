<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Validator\Constraints\NotBlank;

class LoginFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('email', EmailType::class, [
                'label' => 'Email',
                'constraints' => [new NotBlank()],
                'attr' => [
                    'placeholder' => 'votre@email.fr',
                    'class' => 'form-control',
                    'autocomplete' => 'email'
                ]
            ])
            ->add('password', PasswordType::class, [
                'label' => 'Mot de passe',
                'constraints' => [new NotBlank()],
                'attr' => [
                    'class' => 'form-control',
                    'autocomplete' => 'current-password'
                ]
            ])
            ->add('_remember_me', CheckboxType::class, [
                'label' => 'Se souvenir de moi',
                'required' => false,
                'mapped' => false,
                'attr' => ['class' => 'form-check-input']
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            // Important pour le CSRF token
            'csrf_field_name' => '_csrf_token',
            'csrf_token_id'   => 'authenticate',
        ]);
    }

    public function getBlockPrefix(): string
    {
        return ''; // Retourne une chaîne vide pour que le formulaire n'ait pas de préfixe
    }
}