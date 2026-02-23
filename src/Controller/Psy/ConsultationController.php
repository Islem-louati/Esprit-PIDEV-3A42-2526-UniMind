<?php

namespace App\Controller\Psy;

use App\Entity\Consultation;
use App\Entity\User;
use App\Repository\ConsultationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/psychologue/consultations')]
#[IsGranted('ROLE_PSYCHOLOGUE')]
class ConsultationController extends AbstractController
{
    #[Route('', name: 'app_psy_consultations', methods: ['GET'])]
    public function index(ConsultationRepository $consultationRepository): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        
        // Récupérer toutes les consultations du psychologue
        $consultations = $consultationRepository->findBy(
            ['psy' => $user],
            ['date_redaction' => 'DESC']
        );
        
        // Calculer les statistiques
        $totalConsultations = count($consultations);
        $consultationsAvecNotes = count(array_filter($consultations, fn($c) => $c->getNoteSatisfaction() !== null));
        $consultationsSansNotes = $totalConsultations - $consultationsAvecNotes;
        
        // Calculer la moyenne des notes
        $sommeNotes = array_reduce(
            $consultations,
            fn($sum, $c) => $sum + ($c->getNoteSatisfaction() ?? 0),
            0
        );
        $moyenneNote = $consultationsAvecNotes > 0 
            ? round($sommeNotes / $consultationsAvecNotes, 1) 
            : 0;
        
        return $this->render('psy/consultations/index.html.twig', [
            'consultations' => $consultations,
            'total_consultations' => $totalConsultations,
            'consultations_avec_notes' => $consultationsAvecNotes,
            'consultations_sans_notes' => $consultationsSansNotes,
            'moyenne_note' => $moyenneNote,
            'user' => $user,
        ]);
    }
    
    #[Route('/{id}', name: 'app_psy_consultation_detail', methods: ['GET'])]
    public function show(Consultation $consultation): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        
        // Vérifier que la consultation appartient au psychologue connecté
        if ($consultation->getPsy() !== $user) {
            throw $this->createAccessDeniedException();
        }
        
        return $this->render('psy/consultations/detail.html.twig', [
            'consultation' => $consultation,
            'user' => $user,
        ]);
    }
    
    #[Route('/{id}/data', name: 'app_psy_consultation_data', methods: ['GET'])]
    public function getConsultationData(Consultation $consultation): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();
        
        // Vérifier que la consultation appartient au psychologue connecté
        if ($consultation->getPsy() !== $user) {
            return $this->json([
                'success' => false, 
                'error' => 'Accès refusé'
            ], 403);
        }
        
        try {
            // Récupérer le rendez-vous associé
            $rendezVous = $consultation->getRendezVous();
            
            // Formater la date du rendez-vous
            $dateRdv = 'Non spécifiée';
            if ($rendezVous) {
                $disponibilite = $rendezVous->getDisponibilite();
                if ($disponibilite) {
                    $dateRdv = $disponibilite->getDateDispo()->format('d/m/Y');
                }
            }
            
            // Retourner les données en JSON
            return $this->json([
                'success' => true,
                'data' => [
                    'id' => $consultation->getConsultationId(),
                    'avis_psy' => $consultation->getAvisPsy() ?? '',
                    'note_satisfaction' => $consultation->getNoteSatisfaction(),
                    'etudiant_nom' => $consultation->getEtudiant()->getPrenom() . ' ' . $consultation->getEtudiant()->getNom(),
                    'date_redaction' => $consultation->getDateRedaction()->format('d/m/Y à H:i'),
                    'date_rdv' => $dateRdv,
                ]
            ]);
            
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'error' => 'Erreur lors de la récupération des données : ' . $e->getMessage()
            ], 500);
        }
    }
    
    #[Route('/{id}/edit', name: 'app_psy_consultation_edit', methods: ['POST'])]
    public function edit(
        Consultation $consultation,
        Request $request,
        EntityManagerInterface $em
    ): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        
        // Vérifier que la consultation appartient au psychologue connecté
        if ($consultation->getPsy() !== $user) {
            throw $this->createAccessDeniedException();
        }
        
        // Vérifier le token CSRF
        $submittedToken = $request->request->get('_token');
        
        if (!$this->isCsrfTokenValid('consultation-edit', $submittedToken)) {
            $this->addFlash('error', 'Token CSRF invalide. Veuillez réessayer.');
            return $this->redirectToRoute('app_psy_consultations');
        }
        
        // Récupérer les données du formulaire
        $avis = $request->request->get('avis_psy', '');
        $note = $request->request->get('note_satisfaction');
        
        try {
            // Mettre à jour la consultation
            $consultation->setAvisPsy($avis);
            
            // Gérer la note de satisfaction
            if ($note !== null && $note !== '') {
                $noteInt = (int)$note;
                // Valider que la note est entre 1 et 5
                if ($noteInt >= 1 && $noteInt <= 5) {
                    $consultation->setNoteSatisfaction($noteInt);
                } else {
                    throw new \InvalidArgumentException('La note doit être entre 1 et 5');
                }
            } else {
                $consultation->setNoteSatisfaction(null);
            }
            
            // Mettre à jour la date de modification
            $consultation->setDateModification(new \DateTime());
            
            $em->flush();
            
            $this->addFlash('success', 'Consultation mise à jour avec succès.');
            
        } catch (\Exception $e) {
            $this->addFlash('error', 'Erreur lors de la mise à jour : ' . $e->getMessage());
        }
        
        return $this->redirectToRoute('app_psy_consultations');
    }
}