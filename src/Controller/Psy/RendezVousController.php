<?php
// src/Controller/Psychologue/RendezVousController.php

namespace App\Controller\Psy;

use App\Entity\RendezVous;
use App\Entity\Consultation;
use App\Entity\User;
use App\Repository\ConsultationRepository;
use App\Enum\StatutRendezVous;
use App\Enum\StatutDisponibilite;
use App\Repository\RendezVousRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/psychologue/rendez-vous')]
#[IsGranted('ROLE_PSYCHOLOGUE')]


class RendezVousController extends AbstractController
{
 #[Route('', name: 'app_psychologue_rdv', methods: ['GET'])]
    public function index(RendezVousRepository $rdvRepository): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        
        // Récupérer tous les rendez-vous du psychologue
        $rendezVousList = $rdvRepository->findByPsyOrderedByDate($user, 'DESC');
        
        // Filtrer par statut pour les statistiques
        $demandes = array_filter($rendezVousList, fn($rdv) => $rdv->isDemande());
        $confirmes = array_filter($rendezVousList, fn($rdv) => $rdv->isConfirme());
        $enCours = array_filter($rendezVousList, fn($rdv) => $rdv->isEnCours());
        $termines = array_filter($rendezVousList, fn($rdv) => $rdv->isTermine());
        $annules = array_filter($rendezVousList, fn($rdv) => $rdv->isAnnule());
        
        return $this->render('psy/rendezvous/index.html.twig', [
            'user' => $user,
            'rendez_vous_list' => $rendezVousList, // <-- CECI EST ESSENTIEL
            'demandes' => $demandes,
            'confirmes' => $confirmes,
            'en_cours' => $enCours,
            'termines' => $termines,
            'annules' => $annules,
            'statuts' => StatutRendezVous::cases(),
        ]);
    }

    #[Route('/demandes', name: 'app_psychologue_rdv_demandes', methods: ['GET'])]
    public function demandes(RendezVousRepository $rdvRepository): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        
        // Récupérer seulement les demandes
        $rendezVousList = $rdvRepository->createQueryBuilder('r')
            ->leftJoin('r.disponibilite', 'd')
            ->andWhere('r.psy = :psy')
            ->andWhere('r.statut = :statut')
            ->setParameter('psy', $user)
            ->setParameter('statut', StatutRendezVous::DEMANDE->value)
            ->orderBy('d.date_dispo', 'ASC')
            ->addOrderBy('d.heure_debut', 'ASC')
            ->getQuery()
            ->getResult();
        
        return $this->render('psy/rendezvous/demandes.html.twig', [
            'user' => $user,
            'rendez_vous_list' => $rendezVousList,
            'statuts' => StatutRendezVous::cases(),
        ]);
    }

    #[Route('/{id}/confirmer', name: 'app_psychologue_rdv_confirmer', methods: ['POST'])]
    public function confirmer(
        RendezVous $rendezVous,
        Request $request,
        EntityManagerInterface $em
    ): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        
        // Vérifier que le rendez-vous appartient au psychologue connecté
        if ($rendezVous->getPsy() !== $user) {
            throw $this->createAccessDeniedException();
        }
        
        // Vérifier que le rendez-vous est en statut demande
        if (!$rendezVous->isDemande()) {
            $this->addFlash('error', 'Seuls les rendez-vous en attente peuvent être confirmés.');
            return $this->redirectToRoute('app_psychologue_rdv');
        }
        
        // Vérifier le token CSRF
        if (!$this->isCsrfTokenValid('confirmer' . $rendezVous->getRendezVousId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Token CSRF invalide.');
            return $this->redirectToRoute('app_psychologue_rdv');
        }
        
        try {
            // Confirmer le rendez-vous
            $rendezVous->setStatut(StatutRendezVous::CONFIRME->value);
            $rendezVous->setUpdatedAt(new \DateTime());
            
            // Marquer la disponibilité comme réservée
            $disponibilite = $rendezVous->getDisponibilite();
            if ($disponibilite) {
                $disponibilite->setStatut(StatutDisponibilite::RESERVE->value);
                $disponibilite->setUpdatedAt(new \DateTime());
            }
            
            $em->flush();
            
            $this->addFlash('success', 'Rendez-vous confirmé avec succès.');
            
        } catch (\Exception $e) {
            $this->addFlash('error', 'Erreur : ' . $e->getMessage());
        }
        
        return $this->redirectToRoute('app_psychologue_rdv');
    }

    #[Route('/{id}/refuser', name: 'app_psychologue_rdv_refuser', methods: ['POST'])]
    public function refuser(
        RendezVous $rendezVous,
        Request $request,
        EntityManagerInterface $em
    ): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        
        // Vérifier que le rendez-vous appartient au psychologue connecté
        if ($rendezVous->getPsy() !== $user) {
            throw $this->createAccessDeniedException();
        }
        
        // Vérifier que le rendez-vous est en statut demande
        if (!$rendezVous->isDemande()) {
            $this->addFlash('error', 'Seuls les rendez-vous en attente peuvent être refusés.');
            return $this->redirectToRoute('app_psychologue_rdv');
        }
        
        // Vérifier le token CSRF
        if (!$this->isCsrfTokenValid('refuser' . $rendezVous->getRendezVousId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Token CSRF invalide.');
            return $this->redirectToRoute('app_psychologue_rdv');
        }
        
        try {
            // Refuser le rendez-vous (le marquer comme annulé)
            $rendezVous->setStatut(StatutRendezVous::ANNULE->value);
            $rendezVous->setUpdatedAt(new \DateTime());
            
            // Libérer la disponibilité
            $disponibilite = $rendezVous->getDisponibilite();
            if ($disponibilite) {
                $disponibilite->setStatut(StatutDisponibilite::DISPONIBLE->value);
                $disponibilite->setUpdatedAt(new \DateTime());
            }
            
            $em->flush();
            
            $this->addFlash('success', 'Rendez-vous refusé avec succès.');
            
        } catch (\Exception $e) {
            $this->addFlash('error', 'Erreur : ' . $e->getMessage());
        }
        
        return $this->redirectToRoute('app_psychologue_rdv');
    }

    #[Route('/{id}/commencer', name: 'app_psychologue_rdv_commencer', methods: ['POST'])]
    public function commencer(
        RendezVous $rendezVous,
        Request $request,
        EntityManagerInterface $em
    ): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        
        // Vérifier que le rendez-vous appartient au psychologue connecté
        if ($rendezVous->getPsy() !== $user) {
            throw $this->createAccessDeniedException();
        }
        
        // Vérifier que le rendez-vous est confirmé
        if (!$rendezVous->isConfirme()) {
            $this->addFlash('error', 'Seuls les rendez-vous confirmés peuvent être commencés.');
            return $this->redirectToRoute('app_psychologue_rdv');
        }
        
        // Vérifier le token CSRF
        if (!$this->isCsrfTokenValid('commencer' . $rendezVous->getRendezVousId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Token CSRF invalide.');
            return $this->redirectToRoute('app_psychologue_rdv');
        }
        
        try {
            // Commencer le rendez-vous
            $rendezVous->setStatut(StatutRendezVous::EN_COURS->value);
            $rendezVous->setUpdatedAt(new \DateTime());
            
            $em->flush();
            
            $this->addFlash('success', 'Rendez-vous marqué comme "En cours".');
            
        } catch (\Exception $e) {
            $this->addFlash('error', 'Erreur : ' . $e->getMessage());
        }
        
        return $this->redirectToRoute('app_psychologue_rdv');
    }

   #[Route('/{id}/terminer', name: 'app_psychologue_rdv_terminer', methods: ['POST'])]
public function terminer(
    RendezVous $rendezVous,
    Request $request,
    EntityManagerInterface $em,
    ConsultationRepository $consultationRepository
): Response
{
    /** @var User $user */
    $user = $this->getUser();
    
    // Vérifier que le rendez-vous appartient au psychologue connecté
    if ($rendezVous->getPsy() !== $user) {
        throw $this->createAccessDeniedException();
    }
    
    // Vérifier que le rendez-vous est en cours
    if (!$rendezVous->isEnCours()) {
        $this->addFlash('error', 'Seuls les rendez-vous en cours peuvent être terminés.');
        return $this->redirectToRoute('app_psychologue_rdv');
    }
    
    // Vérifier le token CSRF
    if (!$this->isCsrfTokenValid('terminer' . $rendezVous->getRendezVousId(), $request->request->get('_token'))) {
        $this->addFlash('error', 'Token CSRF invalide.');
        return $this->redirectToRoute('app_psychologue_rdv');
    }
    
    try {
        // Terminer le rendez-vous
        $rendezVous->setStatut(StatutRendezVous::TERMINE->value);
        $rendezVous->setUpdatedAt(new \DateTime());
        
        // Vérifier si une consultation existe déjà
        $existingConsultation = $consultationRepository->findOneBy(['rendezVous' => $rendezVous]);
        
        if (!$existingConsultation) {
            // Créer une nouvelle consultation automatiquement
            $consultation = Consultation::createFromRendezVous($rendezVous, $user);
            $consultation->setAvisPsy("Consultation terminée le " . (new \DateTime())->format('d/m/Y'));
            
            $em->persist($consultation);
        }
        
        $em->flush();
        
        $this->addFlash('success', 'Rendez-vous terminé avec succès. Une consultation a été créée.');
        
    } catch (\Exception $e) {
        $this->addFlash('error', 'Erreur : ' . $e->getMessage());
    }
    
    return $this->redirectToRoute('app_psychologue_rdv');
}
    #[Route('/{id}/marquer-absent', name: 'app_psychologue_rdv_absent', methods: ['POST'])]
    public function marquerAbsent(
        RendezVous $rendezVous,
        Request $request,
        EntityManagerInterface $em
    ): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        
        // Vérifier que le rendez-vous appartient au psychologue connecté
        if ($rendezVous->getPsy() !== $user) {
            throw $this->createAccessDeniedException();
        }
        
        // Vérifier que le rendez-vous n'est pas déjà terminé ou annulé
        if ($rendezVous->isTermine() || $rendezVous->isAnnule()) {
            $this->addFlash('error', 'Ce rendez-vous ne peut pas être marqué comme absent.');
            return $this->redirectToRoute('app_psychologue_rdv');
        }
        
        // Vérifier le token CSRF
        if (!$this->isCsrfTokenValid('absent' . $rendezVous->getRendezVousId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Token CSRF invalide.');
            return $this->redirectToRoute('app_psychologue_rdv');
        }
        
        try {
            // Marquer comme absent
            $rendezVous->setStatut(StatutRendezVous::ABSENT->value);
            $rendezVous->setUpdatedAt(new \DateTime());
            
            // Libérer la disponibilité
            $disponibilite = $rendezVous->getDisponibilite();
            if ($disponibilite) {
                $disponibilite->setStatut(StatutDisponibilite::DISPONIBLE->value);
                $disponibilite->setUpdatedAt(new \DateTime());
            }
            
            $em->flush();
            
            $this->addFlash('success', 'Étudiant marqué comme absent.');
            
        } catch (\Exception $e) {
            $this->addFlash('error', 'Erreur : ' . $e->getMessage());
        }
        
        return $this->redirectToRoute('app_psychologue_rdv');
    }

    #[Route('/{id}/detail', name: 'app_psychologue_rdv_detail', methods: ['GET'])]
    public function detail(RendezVous $rendezVous): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        
        // Vérifier que le rendez-vous appartient au psychologue connecté
        if ($rendezVous->getPsy() !== $user) {
            throw $this->createAccessDeniedException();
        }
        
        return $this->render('psy/rendezvous/detail.html.twig', [
            'user' => $user,
            'rendez_vous' => $rendezVous,
            'statuts' => StatutRendezVous::cases(),
        ]);
    }

    #[Route('/{id}/annuler', name: 'app_psychologue_rdv_annuler', methods: ['POST'])]
    public function annuler(
        RendezVous $rendezVous,
        Request $request,
        EntityManagerInterface $em
    ): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        
        // Vérifier que le rendez-vous appartient au psychologue connecté
        if ($rendezVous->getPsy() !== $user) {
            throw $this->createAccessDeniedException();
        }
        
        // Vérifier que le rendez-vous peut être annulé
        if ($rendezVous->isTermine() || $rendezVous->isAbsent()) {
            $this->addFlash('error', 'Ce rendez-vous ne peut pas être annulé.');
            return $this->redirectToRoute('app_psychologue_rdv');
        }
        
        // Vérifier le token CSRF
        if (!$this->isCsrfTokenValid('annuler' . $rendezVous->getRendezVousId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Token CSRF invalide.');
            return $this->redirectToRoute('app_psychologue_rdv');
        }
        
        try {
            // Annuler le rendez-vous
            $rendezVous->setStatut(StatutRendezVous::ANNULE->value);
            $rendezVous->setUpdatedAt(new \DateTime());
            
            // Libérer la disponibilité
            $disponibilite = $rendezVous->getDisponibilite();
            if ($disponibilite) {
                $disponibilite->setStatut(StatutDisponibilite::DISPONIBLE->value);
                $disponibilite->setUpdatedAt(new \DateTime());
            }
            
            $em->flush();
            
            $this->addFlash('success', 'Rendez-vous annulé avec succès.');
            
        } catch (\Exception $e) {
            $this->addFlash('error', 'Erreur : ' . $e->getMessage());
        }
        
        return $this->redirectToRoute('app_psychologue_rdv');
    }
}