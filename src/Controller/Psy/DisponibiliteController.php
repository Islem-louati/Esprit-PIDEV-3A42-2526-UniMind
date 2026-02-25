<?php
// src/Controller/Psy/DisponibiliteController.php

namespace App\Controller\Psy;

use App\Entity\DisponibilitePsy;
use App\Entity\User;
use App\Enum\StatutDisponibilite;
use App\Enum\TypeConsultation; // AJOUTEZ CET IMPORT
use App\Repository\DisponibilitePsyRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/psy/disponibilites')]
#[IsGranted('ROLE_PSYCHOLOGUE')]
class DisponibiliteController extends AbstractController
{
    #[Route('', name: 'app_psy_disponibilites', methods: ['GET', 'POST'])]
    public function index(Request $request, EntityManagerInterface $em, DisponibilitePsyRepository $dispoRepository): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        
        // Traitement POST (création)
        if ($request->isMethod('POST')) {
            // Récupérer les données du formulaire AVEC le préfixe 'disponibilite'
            $formData = $request->request->all()['disponibilite'] ?? [];
            
            $date_dispo = $formData['date_dispo'] ?? null;
            $heure_debut = $formData['heure_debut'] ?? null;
            $heure_fin = $formData['heure_fin'] ?? null;
            $type_consult = $formData['type_consult'] ?? null;
            $lieu = $formData['lieu'] ?? null;
            
            try {
                // Validation
                if (empty($date_dispo)) {
                    throw new \Exception('Date vide - reçu: ' . ($date_dispo ?: 'RIEN'));
                }
                if (empty($heure_debut)) {
                    throw new \Exception('Heure début vide');
                }
                if (empty($heure_fin)) {
                    throw new \Exception('Heure fin vide');
                }
                if (empty($type_consult)) {
                    throw new \Exception('Type consultation vide');
                }
                
                // Créer la disponibilité
                $disponibilite = new DisponibilitePsy();
                $disponibilite->setUser($user);
                
                // Convertir les dates
                $disponibilite->setDateDispo(new \DateTime($date_dispo));
                $disponibilite->setHeureDebut(\DateTime::createFromFormat('H:i', $heure_debut));
                $disponibilite->setHeureFin(\DateTime::createFromFormat('H:i', $heure_fin));
                $disponibilite->setTypeConsult($type_consult);
                
                if (!empty($lieu)) {
                    $disponibilite->setLieu($lieu);
                }
                
                // Validation des heures
                if (!$disponibilite->validateHeures()) {
                    throw new \Exception('L\'heure de fin doit être après l\'heure de début.');
                }
                
                $em->persist($disponibilite);
                $em->flush();
                
                $this->addFlash('success', 'Disponibilité créée avec succès !');
                
            } catch (\Exception $e) {
                $this->addFlash('error', 'Erreur : ' . $e->getMessage());
            }
            
            return $this->redirectToRoute('app_psy_disponibilites');
        }
        
        // GET - Afficher la liste
        $disponibilites = $dispoRepository->findBy(
            ['user' => $user],
            ['date_dispo' => 'ASC', 'heure_debut' => 'ASC']
        );
        
        $now = new \DateTime();
        $disponibilitesFutures = [];
        $disponibilitesPassees = [];
        
        foreach ($disponibilites as $dispo) {
            if ($dispo->getDateTimeFin() >= $now) {
                $disponibilitesFutures[] = $dispo;
            } else {
                $disponibilitesPassees[] = $dispo;
            }
        }
        
        // AJOUTEZ CES DEUX LIGNES POUR PASSER LES VARIABLES AU TEMPLATE
        return $this->render('psy/disponibilite/index.html.twig', [
            'user' => $user,
            'disponibilites_futures' => $disponibilitesFutures,
            'disponibilites_passees' => $disponibilitesPassees,
            'statuts' => StatutDisponibilite::getChoices(), // AJOUTEZ CETTE LIGNE
            'types_consultation' => TypeConsultation::getChoices(), // AJOUTEZ CETTE LIGNE
        ]);
    }

    #[Route('/{id}/get', name: 'app_psy_disponibilite_get', methods: ['GET'])]
    public function getDisponibilite(DisponibilitePsy $disponibilite): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        
        // Vérifier que la disponibilité appartient au psychologue connecté
        if ($disponibilite->getUser() !== $user) {
            throw $this->createAccessDeniedException();
        }
        
        // Préparer les données pour le JSON
        $data = [
            'id' => $disponibilite->getDispoId(),
            'date_dispo' => $disponibilite->getDateDispo()->format('Y-m-d'),
            'heure_debut' => $disponibilite->getHeureDebut()->format('H:i'),
            'heure_fin' => $disponibilite->getHeureFin()->format('H:i'),
            'type_consult' => $disponibilite->getTypeConsult(),
            'lieu' => $disponibilite->getLieu(),
            'statut' => $disponibilite->getStatut(),
        ];
        
        return $this->json($data);
    }

    #[Route('/{id}/modifier', name: 'app_psy_disponibilite_edit', methods: ['POST'])]
    public function edit(
        DisponibilitePsy $disponibilite,
        Request $request,
        EntityManagerInterface $em
    ): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        
        // Vérifier que la disponibilité appartient au psychologue connecté
        if ($disponibilite->getUser() !== $user) {
            throw $this->createAccessDeniedException();
        }
        
        $formData = $request->request->all()['disponibilite'] ?? [];
        
        try {
            $date_dispo = $formData['date_dispo'] ?? null;
            $heure_debut = $formData['heure_debut'] ?? null;
            $heure_fin = $formData['heure_fin'] ?? null;
            $type_consult = $formData['type_consult'] ?? null;
            $lieu = $formData['lieu'] ?? null;
            $statut = $formData['statut'] ?? StatutDisponibilite::DISPONIBLE->value;
            
            // Validation
            if (empty($date_dispo)) {
                throw new \Exception('Date requise');
            }
            if (empty($heure_debut)) {
                throw new \Exception('Heure début requise');
            }
            if (empty($heure_fin)) {
                throw new \Exception('Heure fin requise');
            }
            if (empty($type_consult)) {
                throw new \Exception('Type consultation requis');
            }
            if (empty($statut)) {
                throw new \Exception('Statut requis');
            }
            
            // Mettre à jour la disponibilité
            $disponibilite->setDateDispo(new \DateTime($date_dispo));
            $disponibilite->setHeureDebut(\DateTime::createFromFormat('H:i', $heure_debut));
            $disponibilite->setHeureFin(\DateTime::createFromFormat('H:i', $heure_fin));
            $disponibilite->setTypeConsult($type_consult);
            $disponibilite->setStatut($statut);
            $disponibilite->setUpdatedAt(new \DateTime());
            
            if (!empty($lieu)) {
                $disponibilite->setLieu($lieu);
            } else {
                $disponibilite->setLieu(null);
            }
            
            // Validation des heures
            if (!$disponibilite->validateHeures()) {
                throw new \Exception('L\'heure de fin doit être après l\'heure de début.');
            }
            
            $em->flush();
            
            $this->addFlash('success', 'Disponibilité mise à jour avec succès !');
            
        } catch (\Exception $e) {
            $this->addFlash('error', 'Erreur : ' . $e->getMessage());
        }
        
        return $this->redirectToRoute('app_psy_disponibilites');
    }

    #[Route('/{id}/supprimer', name: 'app_psy_disponibilite_delete', methods: ['POST'])]
    public function delete(
        DisponibilitePsy $disponibilite,
        Request $request,
        EntityManagerInterface $em
    ): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        
        // Vérifier que la disponibilité appartient au psychologue connecté
        if ($disponibilite->getUser() !== $user) {
            throw $this->createAccessDeniedException();
        }
        
        // Ne pas supprimer une disponibilité réservée
        if ($disponibilite->isReserve()) {
            $this->addFlash('error', 'Impossible de supprimer une disponibilité réservée.');
            return $this->redirectToRoute('app_psy_disponibilites');
        }
        
        // Vérifier le token CSRF
        if ($this->isCsrfTokenValid('delete'.$disponibilite->getDispoId(), $request->request->get('_token'))) {
            $em->remove($disponibilite);
            $em->flush();
            
            $this->addFlash('success', 'Disponibilité supprimée avec succès !');
        }
        
        return $this->redirectToRoute('app_psy_disponibilites');
    }

    
}