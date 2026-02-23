<?php

namespace App\Controller\Evenement\ResponsableEtudiant;

use App\Entity\User;
use App\Enum\StatutEvenement;
use App\Repository\EvenementRepository;
use App\Repository\ParticipationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Dashboard responsable étudiant – Module Événements (accès limité).
 */
#[Route('/responsable-etudiant', name: 'app_back_')]
final class BackController extends AbstractController
{
    #[Route('', name: 'dashboard', methods: ['GET'])]
    public function dashboard(
        EvenementRepository $evenementRepository,
        ParticipationRepository $participationRepository,
        EntityManagerInterface $em
    ): Response
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException();
        }

        $now = new \DateTimeImmutable();

        $totalEvenements = $evenementRepository->count(['organisateur' => $user]);
        $evenementsAVenirCount = $evenementRepository->count([
            'organisateur' => $user,
            'statut' => StatutEvenement::A_VENIR->value,
        ]);

        $totalParticipations = (int) $em->createQueryBuilder()
            ->select('COUNT(p.participation_id)')
            ->from('App\\Entity\\Participation', 'p')
            ->leftJoin('p.evenement', 'e')
            ->andWhere('e.organisateur = :org')
            ->setParameter('org', $user)
            ->getQuery()
            ->getSingleScalarResult();

        $confirmedParticipations = (int) $em->createQueryBuilder()
            ->select('COUNT(p.participation_id)')
            ->from('App\\Entity\\Participation', 'p')
            ->leftJoin('p.evenement', 'e')
            ->andWhere('e.organisateur = :org')
            ->andWhere('p.statut = :statut')
            ->setParameter('org', $user)
            ->setParameter('statut', 'confirme')
            ->getQuery()
            ->getSingleScalarResult();

        $totalCapacity = (int) $em->createQueryBuilder()
            ->select('COALESCE(SUM(e.capaciteMax), 0)')
            ->from('App\\Entity\\Evenement', 'e')
            ->andWhere('e.organisateur = :org')
            ->setParameter('org', $user)
            ->getQuery()
            ->getSingleScalarResult();

        $fillRate = $totalCapacity > 0 ? round(($confirmedParticipations / $totalCapacity) * 100, 1) : 0.0;

        $avgNote = $em->createQueryBuilder()
            ->select('AVG(p.note_satisfaction)')
            ->from('App\\Entity\\Participation', 'p')
            ->leftJoin('p.evenement', 'e')
            ->andWhere('e.organisateur = :org')
            ->andWhere('p.note_satisfaction IS NOT NULL')
            ->setParameter('org', $user)
            ->getQuery()
            ->getSingleScalarResult();
        $avgNote = $avgNote !== null ? round((float) $avgNote, 2) : null;

        $prochainsEvenements = $evenementRepository->createQueryBuilder('e')
            ->andWhere('e.organisateur = :org')
            ->andWhere('e.dateDebut >= :now')
            ->setParameter('org', $user)
            ->setParameter('now', $now)
            ->orderBy('e.dateDebut', 'ASC')
            ->setMaxResults(5)
            ->getQuery()
            ->getResult();

        $dernieresParticipations = $participationRepository->createQueryBuilder('p')
            ->leftJoin('p.evenement', 'e')
            ->leftJoin('p.etudiant', 'u')
            ->addSelect('e', 'u')
            ->andWhere('e.organisateur = :org')
            ->setParameter('org', $user)
            ->orderBy('p.date_inscription', 'DESC')
            ->setMaxResults(5)
            ->getQuery()
            ->getResult();

        return $this->render('evenement/responsable_etudiant/dashboard.html.twig', [
            'route_prefix' => 'app_back_',
            'space_label' => 'Responsable étudiant',
            'show_sponsors' => false,
            'show_dashboard' => true,
            'kpis' => [
                'total_evenements' => $totalEvenements,
                'evenements_a_venir' => $evenementsAVenirCount,
                'total_participations' => $totalParticipations,
                'taux_remplissage' => $fillRate,
                'moyenne_notes' => $avgNote,
            ],
            'prochains_evenements' => $prochainsEvenements,
            'dernieres_participations' => $dernieresParticipations,
        ]);
    }
}
