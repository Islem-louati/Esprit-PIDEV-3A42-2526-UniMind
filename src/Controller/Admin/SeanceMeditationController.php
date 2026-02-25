<?php

namespace App\Controller\Admin;

use App\Entity\SeanceMeditation;
use App\Entity\CategorieMeditation;
use App\Form\SeanceMeditationType;
use App\Repository\SeanceMeditationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\HttpFoundation\File\Exception\FileException;

#[Route('/admin/seance-meditation')]
class SeanceMeditationController extends AbstractController
{
    #[Route('/', name: 'admin_seance_meditation_index', methods: ['GET'])]
    public function index(SeanceMeditationRepository $seanceMeditationRepository): Response
    {
        return $this->render('admin/seance_meditation/index.html.twig', [
            'seance_meditations' => $seanceMeditationRepository->findAll(),
        ]);
    }
    

    #[Route('/categorie/{id}', name: 'admin_seance_meditation_by_categorie', methods: ['GET'])]
    public function byCategorie(Request $request, CategorieMeditation $categorie, SeanceMeditationRepository $seanceMeditationRepository): Response
    {
        // Créer le formulaire pour le modal
        $seanceMeditation = new SeanceMeditation();
        $seanceMeditation->setCategorie($categorie);
        $form = $this->createForm(SeanceMeditationType::class, $seanceMeditation);
        
        return $this->render('admin/seance_meditation/by_categorie.html.twig', [
            'categorie' => $categorie,
            'seance_meditations' => $seanceMeditationRepository->findBy(['categorie' => $categorie]),
            'form' => $form->createView(), // Passer le formulaire au template
        ]);
    }

    #[Route('/new/categorie/{categorieId}', name: 'admin_seance_meditation_new', methods: ['GET', 'POST'])]
public function new(Request $request, EntityManagerInterface $entityManager, SluggerInterface $slugger, int $categorieId = null): Response
{
    $isAjax = $request->isXmlHttpRequest();
    
    $seanceMeditation = new SeanceMeditation();
    
    if ($categorieId) {
        $categorie = $entityManager->getRepository(CategorieMeditation::class)->find($categorieId);
        if ($categorie) {
            $seanceMeditation->setCategorie($categorie);
        }
    }
    
    $form = $this->createForm(SeanceMeditationType::class, $seanceMeditation);
    $form->handleRequest($request);

    if ($form->isSubmitted()) {
        if ($form->isValid()) {
            // Gestion du fichier uploadé - OBLIGATOIRE pour new
            $fichierFile = $form->get('fichier')->getData();
            
            if (!$fichierFile) {
                if ($isAjax) {
                    return new JsonResponse([
                        'success' => false,
                        'errors' => ['fichier' => 'Le fichier est obligatoire pour créer une nouvelle séance.']
                    ]);
                }
                
                $this->addFlash('error', 'Un fichier est obligatoire pour créer une nouvelle séance.');
                return $this->render('admin/seance_meditation/new.html.twig', [
                    'seance_meditation' => $seanceMeditation,
                    'form' => $form,
                    'categorie_id' => $categorieId
                ]);
            }
            
            $originalFilename = pathinfo($fichierFile->getClientOriginalName(), PATHINFO_FILENAME);
            $safeFilename = $slugger->slug($originalFilename);
            $newFilename = $safeFilename.'-'.uniqid().'.'.$fichierFile->guessExtension();
            
            try {
                $fichierFile->move(
                    $this->getParameter('upload_directory'),
                    $newFilename
                );
                
                $seanceMeditation->setFichier($newFilename);
            } catch (FileException $e) {
                if ($isAjax) {
                    return new JsonResponse([
                        'success' => false,
                        'errors' => ['fichier' => 'Erreur lors du téléchargement du fichier.']
                    ]);
                }
                
                $this->addFlash('error', 'Erreur lors du téléchargement du fichier.');
                return $this->redirectToRoute('admin_seance_meditation_new', ['categorieId' => $categorieId]);
            }
            
            // Définir la date de création
            $seanceMeditation->setCreatedAt(new \DateTime());
            $seanceMeditation->setUpdatedAt(new \DateTime());
            
            $entityManager->persist($seanceMeditation);
            $entityManager->flush();

            if ($isAjax) {
                return new JsonResponse([
                    'success' => true,
                    'message' => 'Séance créée avec succès!',
                    'seance' => [
                        'id' => $seanceMeditation->getSeanceId(),
                        'titre' => $seanceMeditation->getTitre(),
                        'categorie_id' => $seanceMeditation->getCategorie()->getCategorieId()
                    ]
                ]);
            }

            $this->addFlash('success', 'Séance créée avec succès.');
            
            if ($categorieId) {
                return $this->redirectToRoute('admin_seance_meditation_by_categorie', 
                    ['id' => $categorieId], Response::HTTP_SEE_OTHER);
            }
            
            return $this->redirectToRoute('admin_seance_meditation_index', [], Response::HTTP_SEE_OTHER);
        } else {
            // FORMULAIRE INVALIDE
            if ($isAjax) {
                $errors = [];
                foreach ($form->getErrors(true) as $error) {
                    $field = $error->getOrigin()->getName();
                    $errors[$field] = $error->getMessage();
                }
                
                // Rendre le formulaire avec les erreurs
                $html = $this->renderView('admin/seance_meditation/_form.html.twig', [
                    'form' => $form->createView(),
                    'seance_meditation' => $seanceMeditation,
                    'categorie_id' => $categorieId,
                    'button_label' => 'Créer',
                    'form_action' => $this->generateUrl('admin_seance_meditation_new', ['categorieId' => $categorieId])
                ]);
                
                return new JsonResponse([
                    'success' => false,
                    'html' => $html,
                    'errors' => $errors
                ]);
            }
        }
    }

    // Si requête AJAX pour charger le formulaire (GET)
    if ($isAjax && !$form->isSubmitted()) {
        $html = $this->renderView('admin/seance_meditation/_form.html.twig', [
            'form' => $form->createView(),
            'seance_meditation' => $seanceMeditation,
            'categorie_id' => $categorieId,
            'button_label' => 'Créer',
            'form_action' => $this->generateUrl('admin_seance_meditation_new', ['categorieId' => $categorieId])
        ]);
        
        return new JsonResponse([
            'success' => true,
            'html' => $html
        ]);
    }

    return $this->render('admin/seance_meditation/new.html.twig', [
        'seance_meditation' => $seanceMeditation,
        'form' => $form,
        'categorie_id' => $categorieId
    ]);
}

    #[Route('/{id}', name: 'admin_seance_meditation_show', methods: ['GET'])]
    public function show(SeanceMeditation $seanceMeditation): Response
    {
        return $this->render('admin/seance_meditation/show.html.twig', [
            'seance_meditation' => $seanceMeditation,
        ]);
    }

    #[Route('/{id}/edit', name: 'admin_seance_meditation_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, SeanceMeditation $seanceMeditation, EntityManagerInterface $entityManager, SluggerInterface $slugger): Response
    {
        // Sauvegarder le nom du fichier actuel avant modification
        $ancienFichier = $seanceMeditation->getFichier();
        
        $form = $this->createForm(SeanceMeditationType::class, $seanceMeditation);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Gestion du fichier uploadé - OPTIONNEL pour edit
            $fichierFile = $form->get('fichier')->getData();
            
            if ($fichierFile) {
                $originalFilename = pathinfo($fichierFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename.'-'.uniqid().'.'.$fichierFile->guessExtension();
                
                // Déplacez le fichier
                try {
                    $fichierFile->move(
                        $this->getParameter('upload_directory'),
                        $newFilename
                    );
                    
                    // Supprimez l'ancien fichier si nécessaire
                    if ($ancienFichier && file_exists($this->getParameter('upload_directory').'/'.$ancienFichier)) {
                        unlink($this->getParameter('upload_directory').'/'.$ancienFichier);
                    }
                    
                    $seanceMeditation->setFichier($newFilename);
                } catch (FileException $e) {
                    // Si requête AJAX
                    if ($request->isXmlHttpRequest()) {
                        return new JsonResponse([
                            'success' => false,
                            'message' => 'Erreur lors du téléchargement du fichier.'
                        ]);
                    }
                    
                    $this->addFlash('error', 'Erreur lors du téléchargement du fichier.');
                    return $this->render('admin/seance_meditation/edit.html.twig', [
                        'seance_meditation' => $seanceMeditation,
                        'form' => $form,
                    ]);
                }
            } else {
                // Garder l'ancien fichier
                $seanceMeditation->setFichier($ancienFichier);
            }
            
            $seanceMeditation->setUpdatedAt(new \DateTime());
            $entityManager->flush();

            // Si requête AJAX
            if ($request->isXmlHttpRequest()) {
                return new JsonResponse([
                    'success' => true,
                    'message' => 'Séance modifiée avec succès!',
                    'seance' => [
                        'id' => $seanceMeditation->getSeanceId(),
                        'titre' => $seanceMeditation->getTitre(),
                        'categorie_id' => $seanceMeditation->getCategorie()->getCategorieId()
                    ]
                ]);
            }

            $this->addFlash('success', 'Séance modifiée avec succès.');
            
            if ($seanceMeditation->getCategorie()) {
                return $this->redirectToRoute('admin_seance_meditation_by_categorie', 
                    ['id' => $seanceMeditation->getCategorie()->getCategorieId()], Response::HTTP_SEE_OTHER);
            }
            
            return $this->redirectToRoute('admin_seance_meditation_index', [], Response::HTTP_SEE_OTHER);
        }

        // Si requête AJAX avec erreurs
        if ($request->isXmlHttpRequest() && $form->isSubmitted()) {
            $errors = [];
            foreach ($form->getErrors(true) as $error) {
                $field = $error->getOrigin()->getName();
                $errors[$field] = $error->getMessage();
            }
            
            return new JsonResponse([
                'success' => false,
                'message' => 'Erreurs de validation',
                'errors' => $errors
            ]);
        }

        // Si requête AJAX pour charger le formulaire
        if ($request->isXmlHttpRequest()) {
            $html = $this->renderView('admin/seance_meditation/_form.html.twig', [
                'form' => $form->createView(),
                'seance_meditation' => $seanceMeditation,
                'button_label' => 'Modifier',
                'form_action' => $this->generateUrl('admin_seance_meditation_edit', ['id' => $seanceMeditation->getSeanceId()])
            ]);
            
            return new JsonResponse([
                'success' => true,
                'html' => $html
            ]);
        }

        return $this->render('admin/seance_meditation/edit.html.twig', [
            'seance_meditation' => $seanceMeditation,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/delete', name: 'admin_seance_meditation_delete', methods: ['POST'])]
    public function delete(Request $request, SeanceMeditation $seanceMeditation, EntityManagerInterface $entityManager): Response
    {
        // Sauvegarder l'ID de la catégorie avant suppression
        $categorieId = $seanceMeditation->getCategorie() ? $seanceMeditation->getCategorie()->getCategorieId() : null;
        
        if ($this->isCsrfTokenValid('delete'.$seanceMeditation->getSeanceId(), $request->request->get('_token'))) {
            // Supprimez le fichier associé
            $file = $this->getParameter('upload_directory').'/'.$seanceMeditation->getFichier();
            if ($seanceMeditation->getFichier() && file_exists($file)) {
                unlink($file);
            }
            
            $entityManager->remove($seanceMeditation);
            $entityManager->flush();
            
            // Si requête AJAX
            if ($request->isXmlHttpRequest()) {
                return new JsonResponse([
                    'success' => true,
                    'message' => 'Séance supprimée avec succès.'
                ]);
            }
            
            $this->addFlash('success', 'Séance supprimée avec succès.');
        } else {
            // Si requête AJAX
            if ($request->isXmlHttpRequest()) {
                return new JsonResponse([
                    'success' => false,
                    'message' => 'Token CSRF invalide.'
                ]);
            }
        }

        // Si requête AJAX, retourner une réponse JSON même en cas d'erreur
        if ($request->isXmlHttpRequest()) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Erreur lors de la suppression.'
            ]);
        }

        // Redirigez vers la page de la catégorie si elle existe
        if ($categorieId) {
            return $this->redirectToRoute('admin_seance_meditation_by_categorie', 
                ['id' => $categorieId], Response::HTTP_SEE_OTHER);
        }
        
        // Sinon, retournez à l'index général
        return $this->redirectToRoute('admin_seance_meditation_index', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/{id}/toggle-active', name: 'admin_seance_meditation_toggle_active', methods: ['POST'])]
    public function toggleActive(Request $request, SeanceMeditation $seanceMeditation, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('toggle-active'.$seanceMeditation->getSeanceId(), $request->request->get('_token'))) {
            $seanceMeditation->setIsActif(!$seanceMeditation->isIsActif());
            $entityManager->flush();
            
            $status = $seanceMeditation->isIsActif() ? 'activée' : 'désactivée';
            
            // Si requête AJAX
            if ($request->isXmlHttpRequest()) {
                return new JsonResponse([
                    'success' => true,
                    'message' => "Séance {$status} avec succès.",
                    'is_actif' => $seanceMeditation->isIsActif()
                ]);
            }
            
            $this->addFlash('success', "Séance {$status} avec succès.");
        } else {
            // Si requête AJAX
            if ($request->isXmlHttpRequest()) {
                return new JsonResponse([
                    'success' => false,
                    'message' => 'Token CSRF invalide.'
                ]);
            }
        }

        // Si requête AJAX, retourner une réponse JSON même en cas d'erreur
        if ($request->isXmlHttpRequest()) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Erreur lors du changement de statut.'
            ]);
        }

        return $this->redirectToRoute('admin_seance_meditation_by_categorie', 
            ['id' => $seanceMeditation->getCategorie()->getCategorieId()], Response::HTTP_SEE_OTHER);
    }

    
}