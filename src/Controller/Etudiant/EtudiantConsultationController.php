<?php

namespace App\Controller\Etudiant;

use App\Repository\ConsultationRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/etudiant/consultations')]
#[IsGranted('ROLE_ETUDIANT')]
class EtudiantConsultationController extends AbstractController
{
    #[Route('', name: 'app_etudiant_consultations')]
    public function index(ConsultationRepository $consultationRepository): Response
    {
        $user = $this->getUser();
        
        // Récupérer toutes les consultations de l'étudiant
        $consultations = $consultationRepository->findByEtudiantOrderedByDate($user);
        
        // Calculer les statistiques
        $stats = [
            'total' => count($consultations),
            'cette_annee' => $this->countConsultationsThisYear($consultations),
            'note_moyenne' => $this->calculateAverageNote($consultations),
            'derniere_consultation' => !empty($consultations) ? $consultations[0]->getDateRedaction() : null
        ];
        
        return $this->render('etudiant/consultation/index.html.twig', [
            'consultations' => $consultations,
            'stats' => $stats,
            'user' => $user
        ]);
    }
    
    #[Route('/{id}', name: 'app_etudiant_consultation_show')]
    public function show(int $id, ConsultationRepository $consultationRepository): Response
    {
        $consultation = $consultationRepository->find($id);
        
        if (!$consultation) {
            $this->addFlash('error', 'Consultation introuvable.');
            return $this->redirectToRoute('app_etudiant_consultations');
        }
        
        // Vérifier que la consultation appartient à l'étudiant connecté
        if ($consultation->getEtudiant() !== $this->getUser()) {
            $this->addFlash('error', 'Vous n\'avez pas accès à cette consultation.');
            return $this->redirectToRoute('app_etudiant_consultations');
        }
        
        return $this->render('etudiant/consultation/show.html.twig', [
            'consultation' => $consultation,
            'user' => $this->getUser()
        ]);
    }
    
    private function countConsultationsThisYear(array $consultations): int
    {
        $thisYear = (int) date('Y');
        $count = 0;
        
        foreach ($consultations as $consultation) {
            if ((int) $consultation->getDateRedaction()->format('Y') === $thisYear) {
                $count++;
            }
        }
        
        return $count;
    }
    
    private function calculateAverageNote(array $consultations): ?float
    {
        $total = 0;
        $count = 0;
        
        foreach ($consultations as $consultation) {
            if ($consultation->getNoteSatisfaction() !== null) {
                $total += $consultation->getNoteSatisfaction();
                $count++;
            }
        }
        
        return $count > 0 ? round($total / $count, 1) : null;
    }


    
}