<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\Profil;
use App\Enum\RoleType;
use App\Form\RegistrationFormType;
use App\Form\ResetPasswordRequestFormType;
use App\Form\ChangePasswordFormType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mime\Address;
use Symfony\Component\Uid\Uuid;

class SecurityController extends AbstractController
{
    private EntityManagerInterface $entityManager;
    private MailerInterface $mailer;
    private UrlGeneratorInterface $urlGenerator;

    public function __construct(EntityManagerInterface $entityManager, MailerInterface $mailer, UrlGeneratorInterface $urlGenerator)
    {
        $this->entityManager = $entityManager;
        $this->mailer = $mailer;
        $this->urlGenerator = $urlGenerator;
    }
#[Route('/inscription', name: 'app_register')]
public function register(Request $request, UserPasswordHasherInterface $userPasswordHasher): Response
{
    if ($this->getUser()) {
        return $this->redirectToRoute('app_dashboard');
    }

    $user = new User();
    $form = $this->createForm(RegistrationFormType::class, $user);
    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
        try {
            // Récupérer le rôle depuis le formulaire (c'est déjà un enum grâce au ChoiceType)
            $role = $user->getRole();
            
            if (!$role) {
                throw new \Exception('Le rôle est obligatoire.');
            }

            // Valider les champs selon le rôle
            $validationErrors = [];
            
            if ($role === RoleType::ETUDIANT) {
                if (!$user->getIdentifiant()) {
                    $validationErrors[] = 'L\'identifiant étudiant est obligatoire.';
                }
                if (!$user->getNomEtablissement()) {
                    $validationErrors[] = 'Le nom de l\'établissement est obligatoire.';
                }
            } elseif ($role === RoleType::PSYCHOLOGUE) {
                if (!$user->getSpecialite()) {
                    $validationErrors[] = 'La spécialité est obligatoire.';
                }
                if (!$user->getAdresse()) {
                    $validationErrors[] = 'L\'adresse est obligatoire.';
                }
                if (!$user->getTelephone()) {
                    $validationErrors[] = 'Le téléphone est obligatoire.';
                }
            } elseif ($role === RoleType::RESPONSABLE_ETUDIANT) {
                if (!$user->getPoste()) {
                    $validationErrors[] = 'Le poste est obligatoire.';
                }
                if (!$user->getEtablissement()) {
                    $validationErrors[] = 'L\'établissement est obligatoire.';
                }
            }

            if (!empty($validationErrors)) {
                throw new \Exception(implode(' ', $validationErrors));
            }

            // Créer le profil
            $profil = new Profil();
            
            // Remplir les informations du profil selon le rôle
            if ($role === RoleType::ETUDIANT) {
                $profil->setEtablissement($user->getNomEtablissement());
            } elseif ($role === RoleType::PSYCHOLOGUE) {
                $profil->setSpecialite($user->getSpecialite());
                $profil->setTel($user->getTelephone());
                $profil->setEtablissement($user->getAdresse());
            } elseif ($role === RoleType::RESPONSABLE_ETUDIANT) {
                $profil->setEtablissement($user->getEtablissement());
                $profil->setFonction($user->getPoste());
            }

            // Encoder le mot de passe
            $user->setPassword(
                $userPasswordHasher->hashPassword(
                    $user,
                    $form->get('plainPassword')->getData()
                )
            );

            // Générer le token de vérification - Utiliser DateTime au lieu de DateTimeImmutable
            $user->setVerificationToken(Uuid::v4()->toRfc4122());
            $user->setTokenExpiresAt(new \DateTime('+24 hours')); // Changé
            $user->setIsActive(false);
            $user->setIsVerified(false);

            // Lier le profil à l'utilisateur
            $user->setProfil($profil);
            $profil->setUser($user);

            $this->entityManager->persist($user);
            $this->entityManager->persist($profil);
            $this->entityManager->flush();

            // Envoyer l'email de vérification
            $this->sendVerificationEmail($user);

            $this->addFlash('success', 'Votre compte a été créé avec succès ! Veuillez vérifier votre email pour activer votre compte.');
            return $this->redirectToRoute('app_login');

        } catch (\Exception $e) {
            $this->addFlash('error', 'Une erreur est survenue lors de l\'inscription : ' . $e->getMessage());
        }
    }

    return $this->render('security/register.html.twig', [
        'registrationForm' => $form->createView(),
    ]);
}


   #[Route('/verification/{token}', name: 'app_verify_email')]
public function verifyEmail(string $token): Response
{
    // Correction: Utilisez 'verificationToken' (le nom de la propriété PHP)
    $user = $this->entityManager->getRepository(User::class)->findOneBy([
        'verificationToken' => $token
    ]);

    if (!$user) {
        // Debug: Vérifiez si le token existe
        error_log("Token non trouvé: " . $token);
        
        // Essayez aussi avec verification_token
        $user = $this->entityManager->getRepository(User::class)->findOneBy([
            'verification_token' => $token
        ]);
        
        if (!$user) {
            $this->addFlash('error', 'Token de vérification invalide.');
            return $this->redirectToRoute('app_register');
        }
    }

    $expiresAt = $user->getTokenExpiresAt();
    if ($expiresAt && $expiresAt < new \DateTime()) {
        $this->addFlash('error', 'Le lien de vérification a expiré.');
        return $this->redirectToRoute('app_register');
    }

    $user->setIsVerified(true);
    $user->setVerificationToken(null);
    $user->setTokenExpiresAt(null);
    
    // L'administrateur doit encore activer le compte
    $user->setIsActive(false);

    $this->entityManager->flush();

    // Ajoutez un log pour debug
    error_log("✅ Utilisateur vérifié: " . $user->getEmail());
    
    return $this->render('security/verification_success.html.twig');
}


    #[Route('/connexion', name: 'app_login')]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        if ($this->getUser()) {
            return $this->redirectToRoute('app_dashboard');
        }

        $error = $authenticationUtils->getLastAuthenticationError();
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('security/login.html.twig', [
            'last_username' => $lastUsername,
            'error' => $error,
        ]);
    }

    #[Route('/deconnexion', name: 'app_logout')]
    public function logout(): void
    {
        throw new \LogicException('Cette méthode peut être vide - elle sera interceptée par la clé logout sur votre pare-feu.');
    }

    #[Route('/mot-de-passe-oublie', name: 'app_forgot_password')]
public function forgotPassword(Request $request): Response
{
    if ($this->getUser()) {
        return $this->redirectToRoute('app_dashboard');
    }

    $form = $this->createForm(ResetPasswordRequestFormType::class);
    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
        $email = $form->get('email')->getData();
        $user = $this->entityManager->getRepository(User::class)->findOneBy(['email' => $email]);

        if ($user && $user->isVerified()) {
            // Générer le token de réinitialisation - Utiliser DateTime
            $user->setResetToken(Uuid::v4()->toRfc4122());
            $user->setResetTokenExpiresAt(new \DateTime('+24 hours'));
            $this->entityManager->flush();

            // Envoyer l'email de réinitialisation
            $this->sendResetPasswordEmail($user);

            $this->addFlash('success', 'Si votre email existe dans notre système, vous recevrez un lien de réinitialisation.');
        } else {
            // Pour la sécurité, on affiche le même message même si l'email n'existe pas
            $this->addFlash('success', 'Si votre email existe dans notre système, vous recevrez un lien de réinitialisation.');
        }

        return $this->redirectToRoute('app_login');
    }

    return $this->render('security/forgot_password.html.twig', [
        'requestForm' => $form->createView(),
    ]);
}
    #[Route('/reinitialiser-mot-de-passe/{token}', name: 'app_reset_password')]
public function resetPassword(string $token, Request $request, UserPasswordHasherInterface $passwordHasher): Response
{
    error_log("=== DÉBUT resetPassword ===");
    error_log("Token reçu: " . $token);
    
    $user = $this->entityManager
        ->getRepository(User::class)
        ->findOneBy(['resetToken' => $token]);
    
    error_log("Utilisateur trouvé: " . ($user ? 'OUI - ' . $user->getEmail() : 'NON'));
    
    if (!$user) {
        error_log("❌ Utilisateur non trouvé avec ce token");
        // Essayez avec reset_token
        $user = $this->entityManager
            ->getRepository(User::class)
            ->findOneBy(['reset_token' => $token]);
            
        error_log("Deuxième tentative: " . ($user ? 'OUI' : 'NON'));
    }
    
    if (!$user || !$user->isResetTokenValid()) {
        error_log("❌ Token invalide ou expiré");
        $this->addFlash('error', 'Le lien de réinitialisation est invalide ou expiré.');
        return $this->redirectToRoute('app_forgot_password');
    }
    
    error_log("✅ Token valide pour: " . $user->getEmail());

    $form = $this->createForm(ChangePasswordFormType::class);
    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
        error_log("Formulaire soumis et valide");
        
        $user->setPassword(
            $passwordHasher->hashPassword(
                $user,
                $form->get('plainPassword')->getData()
            )
        );

        $user->setResetToken(null);
        $user->setResetTokenExpiresAt(null);

        $this->entityManager->flush();
        
        error_log("✅ Mot de passe mis à jour pour: " . $user->getEmail());

        $this->addFlash('success', 'Mot de passe modifié avec succès.');
        return $this->redirectToRoute('app_login');
    } else {
        if ($form->isSubmitted()) {
            error_log("Formulaire soumis mais invalide");
            error_log("Erreurs: " . print_r($form->getErrors(true, true), true));
        }
    }

    error_log("Rendu du template reset_password.html.twig");
    
    return $this->render('security/reset_password.html.twig', [
        'resetForm' => $form->createView(),
    ]);
}


    private function sendVerificationEmail(User $user): void
{
    try {
        // LOG pour voir ce qui se passe
        error_log("[EMAIL DEBUG] Tentative d'envoi d'email de vérification à: " . $user->getEmail());
        
        $verificationUrl = $this->urlGenerator->generate('app_verify_email', [
            'token' => $user->getVerificationToken()
        ], UrlGeneratorInterface::ABSOLUTE_URL);

        error_log("[EMAIL DEBUG] URL générée: " . $verificationUrl);

        $email = (new TemplatedEmail())
            ->from(new Address('louatiislem74@gmail.com', 'UniMind'))
            ->to($user->getEmail())
            ->subject('Vérification de votre email - UniMind')
            ->htmlTemplate('security/verification_email.html.twig')
            ->context([
                'user' => $user,
                'verificationUrl' => $verificationUrl,
                'expiresAt' => $user->getTokenExpiresAt(),
            ]);

        error_log("[EMAIL DEBUG] Email préparé, envoi en cours...");
        
        // Envoyer l'email
        $this->mailer->send($email);
        
        error_log("[EMAIL DEBUG] ✅ Email envoyé avec succès à: " . $user->getEmail());
        
    } catch (\Exception $e) {
        // LOG l'erreur complète
        error_log("[EMAIL ERROR] Erreur d'envoi: " . $e->getMessage());
        error_log("[EMAIL ERROR] Trace: " . $e->getTraceAsString());
        
        // Vous pouvez aussi logger dans un fichier
        file_put_contents(
            __DIR__ . '/../../var/log/email_errors.log',
            date('Y-m-d H:i:s') . " - Erreur email vérification: " . $e->getMessage() . "\n" . $e->getTraceAsString() . "\n\n",
            FILE_APPEND
        );
        
        // Pour le développement, lancez l'exception pour voir l'erreur
        throw $e;
    }
}

private function sendResetPasswordEmail(User $user): void
{
    try {
        error_log("[EMAIL DEBUG] Tentative d'envoi d'email de réinitialisation à: " . $user->getEmail());
        
        $resetUrl = $this->urlGenerator->generate('app_reset_password', [
            'token' => $user->getResetToken()
        ], UrlGeneratorInterface::ABSOLUTE_URL);

        $email = (new TemplatedEmail())
            ->from(new Address('louatiislem74@gmail.com', 'UniMind'))
            ->to($user->getEmail())
            ->subject('Réinitialisation de votre mot de passe - UniMind')
            ->htmlTemplate('security/reset_password_email.html.twig')
            ->context([
                'user' => $user,
                'resetUrl' => $resetUrl,
                'expiresAt' => $user->getResetTokenExpiresAt(),
            ]);

        $this->mailer->send($email);
        
        error_log("[EMAIL DEBUG] ✅ Email de réinitialisation envoyé à: " . $user->getEmail());
        
    } catch (\Exception $e) {
        error_log("[EMAIL ERROR] Erreur réinitialisation: " . $e->getMessage());
        error_log("[EMAIL ERROR] Trace: " . $e->getTraceAsString());
        
        file_put_contents(
            __DIR__ . '/../../var/log/email_errors.log',
            date('Y-m-d H:i:s') . " - Erreur email réinitialisation: " . $e->getMessage() . "\n" . $e->getTraceAsString() . "\n\n",
            FILE_APPEND
        );
        
        throw $e;
    }
}
#[Route('/debug-email', name: 'app_debug_email')]
public function debugEmail(): Response
{
    // Créez un email de test simple
    try {
        $email = (new \Symfony\Component\Mime\Email())
            ->from('louatiislem74@gmail.com')
            ->to('louatiislem74@gmail.com')
            ->subject('Test debug - ' . date('H:i:s'))
            ->text('Ceci est un test depuis le debug endpoint')
            ->html('<p>Ceci est un <strong>test</strong> depuis le debug endpoint</p>');

        $this->mailer->send($email);
        
        return new Response('✅ Email de test envoyé avec succès !');
        
    } catch (\Exception $e) {
        return new Response('❌ Erreur: ' . $e->getMessage() . '<pre>' . $e->getTraceAsString() . '</pre>');
    }
}


}