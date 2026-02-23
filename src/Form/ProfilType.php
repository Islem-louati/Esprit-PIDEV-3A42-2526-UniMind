<?php

namespace App\Form;

use App\Entity\Profil;
use App\Enum\RoleType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\FileType;

class ProfilType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('pseudo', TextType::class, [
                'label' => 'Pseudo',
                'required' => false,
                'attr' => ['class' => 'form-control']
            ])
            ->add('tel', TextType::class, [
                'label' => 'Téléphone',
                'required' => false,
                'attr' => ['class' => 'form-control']
            ])
            ->add('dateNaissance', DateType::class, [
                'label' => 'Date de naissance',
                'widget' => 'single_text',
                'required' => false,
                'attr' => ['class' => 'form-control']
            ])
            ->add('photo', FileType::class, [
                'label' => 'Photo de profil',
                'mapped' => false,
                'required' => false,
                'attr' => ['class' => 'form-control']
            ])
            ->add('bio', TextareaType::class, [
                'label' => 'Biographie',
                'required' => false,
                'attr' => ['class' => 'form-control', 'rows' => 4]
            ]);

        // Ajouter les champs spécifiques selon le rôle
        $role = $options['role'] ?? null;
        
        if ($role === RoleType::ETUDIANT) {
            $builder
                ->add('niveau', TextType::class, [
                    'label' => 'Niveau d\'études',
                    'required' => false,
                    'attr' => ['class' => 'form-control']
                ])
                ->add('filiere', TextType::class, [
                    'label' => 'Filière',
                    'required' => false,
                    'attr' => ['class' => 'form-control']
                ]);
        } elseif ($role === RoleType::PSYCHOLOGUE) {
            $builder
                ->add('specialite', TextType::class, [
                    'label' => 'Spécialité',
                    'required' => false,
                    'attr' => ['class' => 'form-control']
                ])
                ->add('experience', TextareaType::class, [
                    'label' => 'Expérience professionnelle',
                    'required' => false,
                    'attr' => ['class' => 'form-control', 'rows' => 5]
                ])
                ->add('qualification', TextareaType::class, [
                    'label' => 'Qualifications',
                    'required' => false,
                    'attr' => ['class' => 'form-control', 'rows' => 5]
                ]);
        } elseif ($role === RoleType::RESPONSABLE_ETUDIANT) {
            $builder
                ->add('departement', TextType::class, [
                    'label' => 'Département',
                    'required' => false,
                    'attr' => ['class' => 'form-control']
                ])
                ->add('etablissement', TextType::class, [
                    'label' => 'Établissement',
                    'required' => false,
                    'attr' => ['class' => 'form-control']
                ])
                ->add('fonction', TextType::class, [
                    'label' => 'Fonction',
                    'required' => false,
                    'attr' => ['class' => 'form-control']
                ]);
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Profil::class,
            'role' => null, // Accepter l'option 'role'
        ]);
    }
}