<?php

namespace App\Controller;

use App\Entity\Profil;
use App\Entity\User;
use App\Form\ProfilType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
#[IsGranted('ROLE_USER')]
#[Route('/profil')]
class ProfileController extends AbstractController
{
    #[Route('/mon-profil', name: 'app_profile')]
    public function index(): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        $profil = $user->getProfil();

        if (!$profil) {
            $profil = new Profil();
            $profil->setUser($user);
        }

        return $this->render('profile/indexP.html.twig', [
            'user' => $user,
            'profil' => $profil,
        ]);
    }

    #[Route('/modifier', name: 'app_profile_edit', methods: ['GET', 'POST'])] // Ajoutez les méthodes
public function edit(
    Request $request, 
    EntityManagerInterface $entityManager,
    SluggerInterface $slugger
): Response {
    /** @var User $user */
    $user = $this->getUser();
    $profil = $user->getProfil();

    if (!$profil) {
        $profil = new Profil();
        $profil->setUser($user);
    }

    $form = $this->createForm(ProfilType::class, $profil, [
        'role' => $user->getRole()
    ]);

    $form->handleRequest($request); // AJOUTEZ CETTE LIGNE

    if ($form->isSubmitted() && $form->isValid()) { // Simplifiez la condition
        $profil->setUpdatedAt(new \DateTime());

        // Gérer l'upload de photo
        $photoFile = $form->get('photo')->getData();
        
        if ($photoFile) {
            $originalFilename = pathinfo($photoFile->getClientOriginalName(), PATHINFO_FILENAME);
            $safeFilename = $slugger->slug($originalFilename);
            $newFilename = $safeFilename.'-'.uniqid().'.'.$photoFile->guessExtension();

            try {
                $photoFile->move(
                    $this->getParameter('photos_directory'),
                    $newFilename
                );

                // Supprimer l'ancienne photo si elle existe
                if ($profil->getPhoto() && file_exists($this->getParameter('photos_directory').'/'.$profil->getPhoto())) {
                    unlink($this->getParameter('photos_directory').'/'.$profil->getPhoto());
                }

                $profil->setPhoto($newFilename);
            } catch (FileException $e) {
                $this->addFlash('error', 'Erreur lors du téléchargement de la photo.');
            }
        }

        if (!$profil->getProfilId()) {
            $entityManager->persist($profil);
        }

        $entityManager->flush();

        $this->addFlash('success', 'Profil mis à jour avec succès !');
        return $this->redirectToRoute('app_profile');
    }

    return $this->render('profile/edit.html.twig', [
        'form' => $form->createView(),
        'user' => $user,
        'profil' => $profil,
    ]);
}
#[Route('/changer-mot-de-passe', name: 'app_change_password', methods: ['GET', 'POST'])]
public function changePassword(
    Request $request,
    EntityManagerInterface $entityManager,
    UserPasswordHasherInterface $passwordHasher
): Response
{
    /** @var User $user */
    $user = $this->getUser();
    
    if ($request->isMethod('POST')) {
        $currentPassword = $request->request->get('current_password');
        $newPassword = $request->request->get('new_password');
        $confirmPassword = $request->request->get('confirm_password');
        
        // Vérifier que les champs sont remplis
        if (!$currentPassword || !$newPassword || !$confirmPassword) {
            $this->addFlash('error', 'Tous les champs sont obligatoires.');
            return $this->redirectToRoute('app_change_password');
        }
        
        // Vérifier le mot de passe actuel
        if (!$passwordHasher->isPasswordValid($user, $currentPassword)) {
            $this->addFlash('error', 'Le mot de passe actuel est incorrect.');
            return $this->redirectToRoute('app_change_password');
        }
        
        // Vérifier que les nouveaux mots de passe correspondent
        if ($newPassword !== $confirmPassword) {
            $this->addFlash('error', 'Les nouveaux mots de passe ne correspondent pas.');
            return $this->redirectToRoute('app_change_password');
        }
        
        // Vérifier la longueur du mot de passe
        if (strlen($newPassword) < 6) {
            $this->addFlash('error', 'Le mot de passe doit contenir au moins 6 caractères.');
            return $this->redirectToRoute('app_change_password');
        }
        
        // Hasher et sauvegarder le nouveau mot de passe
        $hashedPassword = $passwordHasher->hashPassword($user, $newPassword);
        $user->setPassword($hashedPassword);
        
        $entityManager->flush();
        
        $this->addFlash('success', 'Votre mot de passe a été changé avec succès !');
        return $this->redirectToRoute('app_profile');
    }
    
    return $this->render('profile/change_password.html.twig', [
        'user' => $user,
    ]);
}
    #[Route('/photo', name: 'app_profile_photo', methods: ['POST'])]
    public function updatePhoto(
        Request $request, 
        EntityManagerInterface $entityManager,
        SluggerInterface $slugger
    ): Response {
        /** @var User $user */
        $user = $this->getUser();
        $profil = $user->getProfil();

        if (!$profil) {
            $profil = new Profil();
            $profil->setUser($user);
        }

        $photoFile = $request->files->get('photo');

        if ($photoFile) {
            $originalFilename = pathinfo($photoFile->getClientOriginalName(), PATHINFO_FILENAME);
            $safeFilename = $slugger->slug($originalFilename);
            $newFilename = $safeFilename.'-'.uniqid().'.'.$photoFile->guessExtension();

            try {
                $photoFile->move(
                    $this->getParameter('photos_directory'),
                    $newFilename
                );

                // Supprimer l'ancienne photo si elle existe
                if ($profil->getPhoto() && file_exists($this->getParameter('photos_directory').'/'.$profil->getPhoto())) {
                    unlink($this->getParameter('photos_directory').'/'.$profil->getPhoto());
                }

                $profil->setPhoto($newFilename);
                $profil->setUpdatedAt(new \DateTime());

                if (!$profil->getProfilId()) {
                    $entityManager->persist($profil);
                }

                $entityManager->flush();

                $this->addFlash('success', 'Photo de profil mise à jour avec succès !');
            } catch (FileException $e) {
                $this->addFlash('error', 'Erreur lors du téléchargement de la photo.');
            }
        }

        return $this->redirectToRoute('app_profile');
    }
}