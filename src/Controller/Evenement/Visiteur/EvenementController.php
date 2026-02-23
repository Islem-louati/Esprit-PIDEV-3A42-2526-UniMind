<?php

namespace App\Controller\Evenement\Visiteur;

use App\Enum\StatutEvenement;
use App\Enum\TypeEvenement;
use App\Repository\EvenementRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Contrôleur Front Office – Affichage public des événements.
 * Liste et détail pour les visiteurs / étudiants.
 */
#[Route('/evenements', name: 'app_evenement_')]
final class EvenementController extends AbstractController
{
    /**
     * Liste des événements (page publique).
     */
    #[Route('', name: 'index', methods: ['GET'])]
    public function index(Request $request, EvenementRepository $evenementRepository): Response
    {
        $q = trim((string) $request->query->get('q', ''));
        $type = trim((string) $request->query->get('type', ''));
        $statut = trim((string) $request->query->get('statut', ''));
        $sort = trim((string) $request->query->get('sort', 'dateDebut'));
        $order = trim((string) $request->query->get('order', 'ASC'));

        $dateFrom = null;
        $dateFromRaw = trim((string) $request->query->get('date_from', ''));
        if ($dateFromRaw !== '') {
            try {
                $dateFrom = new \DateTimeImmutable($dateFromRaw);
            } catch (\Exception) {
                $dateFrom = null;
            }
        }

        $dateTo = null;
        $dateToRaw = trim((string) $request->query->get('date_to', ''));
        if ($dateToRaw !== '') {
            try {
                $dateTo = new \DateTimeImmutable($dateToRaw);
            } catch (\Exception) {
                $dateTo = null;
            }
        }

        $evenements = $evenementRepository->searchWithFilters(
            q: $q,
            type: $type,
            statut: $statut,
            dateFrom: $dateFrom,
            dateTo: $dateTo,
            sort: $sort,
            order: $order
        );

        return $this->render('evenement/visiteur/index.html.twig', [
            'evenements' => $evenements,
            'filters' => [
                'q' => $q,
                'type' => $type,
                'statut' => $statut,
                'date_from' => $dateFromRaw,
                'date_to' => $dateToRaw,
                'sort' => $sort,
                'order' => strtoupper($order) === 'DESC' ? 'DESC' : 'ASC',
            ],
            'type_choices' => TypeEvenement::cases(),
            'statut_choices' => StatutEvenement::cases(),
        ]);
    }

    /**
     * Détail d’un événement (page publique).
     */
    #[Route('/{id}', name: 'show', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function show(int $id, EvenementRepository $evenementRepository): Response
    {
        $evenement = $evenementRepository->find($id);

        if (!$evenement) {
            throw $this->createNotFoundException('Événement introuvable.');
        }

        return $this->render('evenement/visiteur/show.html.twig', [
            'evenement' => $evenement,
        ]);
    }
}
