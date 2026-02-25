<?php

namespace App\Controller\Admin;

use App\Entity\CategorieMeditation;
use App\Form\CategorieMeditationType;
use App\Repository\CategorieMeditationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use App\Repository\SeanceMeditationRepository;

#[Route('/admin/categorie-meditation')]
class CategorieMeditationController extends AbstractController
{
    #[Route('/', name: 'admin_categorie_meditation_index', methods: ['GET'])]
    public function index(
        Request $request, 
        CategorieMeditationRepository $categorieMeditationRepository,
        SeanceMeditationRepository $seanceMeditationRepository // Ajoutez ce repository
    ): Response {
        // Créer le formulaire pour le modal
        $categorieMeditation = new CategorieMeditation();
        $form = $this->createForm(CategorieMeditationType::class, $categorieMeditation);
        
        // Récupérer le nombre total de séances
        $totalSeances = $seanceMeditationRepository->count([]);
        
        return $this->render('admin/categorie_meditation/index.html.twig', [
            'categorie_meditations' => $categorieMeditationRepository->findAll(),
            'form' => $form->createView(),
            'total_seances' => $totalSeances, // Ajoutez cette variable
        ]);
    }

#[Route('/new', name: 'admin_categorie_meditation_new', methods: ['GET', 'POST'])]
public function new(Request $request, EntityManagerInterface $entityManager): Response
{
    // DEBUG: Vérifier si c'est AJAX
    $isAjax = $request->isXmlHttpRequest();
    error_log("NEW - AJAX: " . ($isAjax ? 'YES' : 'NO'));
    
    $categorieMeditation = new CategorieMeditation();
    $form = $this->createForm(CategorieMeditationType::class, $categorieMeditation);
    $form->handleRequest($request);

    if ($form->isSubmitted()) {
        if ($form->isValid()) {
            $entityManager->persist($categorieMeditation);
            $entityManager->flush();

            error_log("NEW - Formulaire valide, AJAX: " . ($isAjax ? 'YES' : 'NO'));

            if ($isAjax) {
                return new JsonResponse([
                    'success' => true,
                    'message' => 'Catégorie créée avec succès!'
                ]);
            }

            $this->addFlash('success', 'Catégorie créée avec succès.');
            return $this->redirectToRoute('admin_categorie_meditation_index', [], Response::HTTP_SEE_OTHER);
        } else {
            // FORMULAIRE INVALIDE
            error_log("NEW - Formulaire invalide, erreurs: " . json_encode($form->getErrors(true, false)));
            
            if ($isAjax) {
                $html = $this->renderView('admin/categorie_meditation/_form.html.twig', [
                    'form' => $form->createView(),
                    'button_label' => 'Créer',
                    'form_action' => $this->generateUrl('admin_categorie_meditation_new')
                ]);
                
                return new JsonResponse([
                    'success' => false,  // ← Maintenant c'est false !
                    'html' => $html,
                    'errors' => $this->getFormErrors($form) // Optionnel: pour debug
                ]);
            }
        }
    }

    // Si c'est une requête AJAX en GET (premier chargement du modal)
    if ($isAjax && !$form->isSubmitted()) {
        error_log("NEW - Premier chargement du formulaire AJAX");
        
        $html = $this->renderView('admin/categorie_meditation/_form.html.twig', [
            'form' => $form->createView(),
            'button_label' => 'Créer',
            'form_action' => $this->generateUrl('admin_categorie_meditation_new')
        ]);
        
        return new JsonResponse([
            'success' => true,
            'html' => $html
        ]);
    }

    // Requête normale (non-AJAX)
    return $this->render('admin/categorie_meditation/new.html.twig', [
        'categorie_meditation' => $categorieMeditation,
        'form' => $form->createView(),
    ]);
}

// Ajoutez cette méthode helper dans votre contrôleur
private function getFormErrors($form): array
{
    $errors = [];
    foreach ($form->getErrors(true) as $error) {
        $errors[] = $error->getMessage();
    }
    return $errors;
}

    #[Route('/{id}/edit', name: 'admin_categorie_meditation_edit', methods: ['GET', 'POST'])]
public function edit(Request $request, CategorieMeditation $categorieMeditation, EntityManagerInterface $entityManager): Response
{
    // DEBUG: Vérifier si c'est AJAX
    $isAjax = $request->isXmlHttpRequest();
    error_log("EDIT - AJAX: " . ($isAjax ? 'YES' : 'NO'));
    
    $form = $this->createForm(CategorieMeditationType::class, $categorieMeditation);
    $form->handleRequest($request);

    // Si formulaire soumis et valide
    if ($form->isSubmitted() && $form->isValid()) {
        $categorieMeditation->setUpdatedAt(new \DateTimeImmutable());
        $entityManager->flush();

        error_log("EDIT - Formulaire valide, AJAX: " . ($isAjax ? 'YES' : 'NO'));

        if ($isAjax) {
            return new JsonResponse([
                'success' => true,
                'message' => 'Catégorie modifiée avec succès!'
            ]);
        }

        $this->addFlash('success', 'Catégorie modifiée avec succès.');
        return $this->redirectToRoute('admin_categorie_meditation_index', [], Response::HTTP_SEE_OTHER);
    }

    // Si c'est une requête AJAX
    if ($isAjax) {
        error_log("EDIT - Retour JSON pour AJAX");
        
        $html = $this->renderView('admin/categorie_meditation/_form.html.twig', [
            'form' => $form->createView(),
            'button_label' => 'Modifier',
            'form_action' => $this->generateUrl('admin_categorie_meditation_edit', ['id' => $categorieMeditation->getCategorieId()])
        ]);
        
        // Pour le chargement GET ou erreurs POST, toujours retourner success: true
        return new JsonResponse([
            'success' => true,
            'html' => $html
        ]);
    }

    // Requête normale (non-AJAX)
    return $this->render('admin/categorie_meditation/edit.html.twig', [
        'categorie_meditation' => $categorieMeditation,
        'form' => $form->createView(),
    ]);
}

    #[Route('/{id}', name: 'admin_categorie_meditation_delete', methods: ['POST'])]
    public function delete(Request $request, CategorieMeditation $categorieMeditation, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$categorieMeditation->getCategorieId(), $request->request->get('_token'))) {
            $entityManager->remove($categorieMeditation);
            $entityManager->flush();
            
            // Si c'est une requête AJAX
            if ($request->isXmlHttpRequest()) {
                return new JsonResponse([
                    'success' => true,
                    'message' => 'Catégorie supprimée avec succès.'
                ]);
            }
            
            $this->addFlash('success', 'Catégorie supprimée avec succès.');
        } else {
            // Si c'est une requête AJAX avec token invalide
            if ($request->isXmlHttpRequest()) {
                return new JsonResponse([
                    'success' => false,
                    'message' => 'Token CSRF invalide.'
                ]);
            }
        }

        return $this->redirectToRoute('admin_categorie_meditation_index', [], Response::HTTP_SEE_OTHER);
    }
}