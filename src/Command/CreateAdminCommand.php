<?php

namespace App\Command;

use App\Entity\User;
use App\Enum\RoleType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:create-admin',
    description: 'Crée un utilisateur administrateur'
)]
class CreateAdminCommand extends Command
{
    private EntityManagerInterface $entityManager;
    private UserPasswordHasherInterface $passwordHasher;

    public function __construct(EntityManagerInterface $entityManager, UserPasswordHasherInterface $passwordHasher)
    {
        parent::__construct();
        $this->entityManager = $entityManager;
        $this->passwordHasher = $passwordHasher;
    }

    protected function configure(): void
    {
        $this->setHelp('Cette commande vous permet de créer un administrateur pour la plateforme');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        
        $io->title('Création d\'un administrateur');
        
        /** @var \Symfony\Component\Console\Helper\QuestionHelper $helper */
        $helper = $this->getHelper('question');
        
        $question = new Question('Nom : ');
        $nom = $helper->ask($input, $output, $question);
        
        $question = new Question('Prénom : ');
        $prenom = $helper->ask($input, $output, $question);
        
        $question = new Question('Email : ');
        $email = $helper->ask($input, $output, $question);
        
        $question = new Question('CIN : ');
        $cin = $helper->ask($input, $output, $question);
        
        $question = new Question('Mot de passe : ');
        $question->setHidden(true);
        $question->setHiddenFallback(false);
        $password = $helper->ask($input, $output, $question);
        
        // Vérifier si l'utilisateur existe déjà
        $existingUser = $this->entityManager->getRepository(User::class)->findOneBy(['email' => $email]);
        
        if ($existingUser) {
            $io->error('Un utilisateur avec cet email existe déjà.');
            return Command::FAILURE;
        }
        
        // Créer l'utilisateur admin
        $user = new User();
        $user->setNom($nom);
        $user->setPrenom($prenom);
        $user->setEmail($email);
        $user->setCin($cin);
        $user->setRole(RoleType::ADMIN);
        $user->setStatut('actif');
        $user->setIsActive(true);
        $user->setIsVerified(true);
        
        // Hacher le mot de passe
        $hashedPassword = $this->passwordHasher->hashPassword($user, $password);
        $user->setPassword($hashedPassword);
        
        // Sauvegarder
        $this->entityManager->persist($user);
        $this->entityManager->flush();
        
        $io->success('Administrateur créé avec succès !');
        $io->text([
            'Nom : ' . $nom,
            'Prénom : ' . $prenom,
            'Email : ' . $email,
            'Rôle : Administrateur',
        ]);
        
        return Command::SUCCESS;
    }
}