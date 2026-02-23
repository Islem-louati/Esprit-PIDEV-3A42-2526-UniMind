<?php

namespace App\Controller\Evenement\Etudiant;

use App\Entity\Participation;
use App\Enum\StatutEvenement;
use App\Enum\TypeEvenement;
use App\Enum\RoleType;
use App\Repository\EvenementRepository;
use App\Repository\FavoriRepository;
use App\Repository\ParticipationRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Pages dédiées aux étudiants, liste et détail des événements
 */
#[Route('/etudiant/evenements', name: 'app_evenement_etudiant_')]
final class EvenementEtudiantController extends AbstractController
{
    /**
     * Liste des événements.
     */
    #[Route('', name: 'index', methods: ['GET'])]
    public function index(Request $request, EvenementRepository $evenementRepository, FavoriRepository $favoriRepository): Response
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

        $favorisByEvenementId = [];
        $user = $this->getUser();
        if ($user) {
            $evenementsFavoris = $favoriRepository->findEvenementsFavorisByEtudiant($user);
            foreach ($evenementsFavoris as $e) {
                $favorisByEvenementId[(int) $e->getId()] = true;
            }
        }

        return $this->render('evenement/etudiant/index.html.twig', [
            'evenements' => $evenements,
            'favoris_by_evenement_id' => $favorisByEvenementId,
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

    #[Route('/search', name: 'search', methods: ['GET'])]
    public function search(Request $request, EvenementRepository $evenementRepository, FavoriRepository $favoriRepository): JsonResponse
    {
        if ($request->headers->get('X-Requested-With') !== 'XMLHttpRequest') {
            return $this->json(['evenements' => []], Response::HTTP_BAD_REQUEST);
        }

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

        $favorisByEvenementId = [];
        $user = $this->getUser();
        if ($user) {
            $evenementsFavoris = $favoriRepository->findEvenementsFavorisByEtudiant($user);
            foreach ($evenementsFavoris as $e) {
                $favorisByEvenementId[(int) $e->getId()] = true;
            }
        }

        $payload = [];
        foreach ($evenements as $e) {
            $id = (int) $e->getId();
            $payload[] = [
                'id' => $id,
                'titre' => $e->getTitre(),
                'image' => $e->getImage(),
                'type_label' => method_exists($e, 'getTypeFormate') ? $e->getTypeFormate() : null,
                'date_debut' => method_exists($e, 'getDateDebutFormatee') ? $e->getDateDebutFormatee() : null,
                'description' => $e->getDescription(),
                'is_favori' => $favorisByEvenementId[$id] ?? false,
                'toggle_token' => $this->container->get('security.csrf.token_manager')->getToken('toggle_favori_evenement_' . $id)->getValue(),
            ];
        }

        return $this->json(['evenements' => $payload]);
    }

    /**
     * Détail d’un événement.
     */
    #[Route('/{id}', name: 'show', requirements: ['id' => '\\d+'], methods: ['GET'])]
    public function show(int $id, EvenementRepository $evenementRepository, UserRepository $userRepository, FavoriRepository $favoriRepository): Response
    {
        $evenement = $evenementRepository->find($id);

        if (!$evenement) {
            throw $this->createNotFoundException('Événement introuvable.');
        }

        $user = $this->getUser();
        if (!$user && $this->getParameter('kernel.environment') === 'dev') {
            $user = $userRepository->findOneBy(['role' => RoleType::ETUDIANT]);
        }
        $isInscrit = false;
        if ($user) {
            $isInscrit = $evenement->isUserInscrit($user);
        }

        $isFavori = false;
        if ($user) {
            $isFavori = $favoriRepository->existsByEtudiantAndEvenement($user, $evenement);
        }

        return $this->render('evenement/etudiant/show.html.twig', [
            'evenement' => $evenement,
            'is_inscrit' => $isInscrit,
            'is_favori' => $isFavori,
        ]);
    }

    #[Route('/{id}/participer', name: 'participer', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function participer(
        int $id,
        Request $request,
        EvenementRepository $evenementRepository,
        ParticipationRepository $participationRepository,
        UserRepository $userRepository,
        EntityManagerInterface $em
    ): Response {
        $user = $this->getUser();
        if (!$user && $this->getParameter('kernel.environment') === 'dev') {
            $user = $userRepository->findOneBy(['role' => RoleType::ETUDIANT]);
        }
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        if (!method_exists($user, 'isEtudiant') || !$user->isEtudiant()) {
            throw $this->createAccessDeniedException();
        }

        $evenement = $evenementRepository->find($id);
        if (!$evenement) {
            throw $this->createNotFoundException('Événement introuvable.');
        }

        $token = (string) $request->request->get('_token');
        if (!$this->isCsrfTokenValid('participer_evenement_' . $evenement->getId(), $token)) {
            $this->addFlash('error', 'Jeton de sécurité invalide.');
            return $this->redirectToRoute('app_evenement_etudiant_show', ['id' => $evenement->getId()]);
        }

        if (!$evenement->isInscriptionOuverte()) {
            $this->addFlash('error', 'Les inscriptions ne sont pas ouvertes pour cet événement.');
            return $this->redirectToRoute('app_evenement_etudiant_show', ['id' => $evenement->getId()]);
        }

        $existing = $participationRepository->findOneBy([
            'evenement' => $evenement,
            'etudiant' => $user,
        ]);

        if ($existing) {
            $this->addFlash('info', 'Vous êtes déjà inscrit à cet événement.');
            return $this->redirectToRoute('app_evenement_etudiant_show', ['id' => $evenement->getId()]);
        }

        $participation = Participation::create($evenement, $user);
        $em->persist($participation);
        $em->flush();

        $this->addFlash('success', 'Votre participation a été enregistrée.');
        return $this->redirectToRoute('app_mes_evenements_index');
    }
}
