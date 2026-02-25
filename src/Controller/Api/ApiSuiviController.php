<?php

namespace App\Controller\Api;

use App\Entity\SuiviTraitement;
use App\Entity\Traitement;
use App\Entity\User;
use App\Repository\SuiviTraitementRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\SerializerInterface;

#[Route('/api/suivis')]
class ApiSuiviController extends AbstractController
{
    private SerializerInterface $serializer;

    public function __construct(SerializerInterface $serializer)
    {
        $this->serializer = $serializer;
    }

    #[Route('/', name: 'api_suivi_index', methods: ['GET'])]
    public function index(SuiviTraitementRepository $suiviRepository, Request $request): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
            return new JsonResponse(['error' => 'Non autorisé'], Response::HTTP_UNAUTHORIZED);
        }

        // Filtrer selon le rôle
        $suivis = match (true) {
            $this->isGranted('ROLE_ADMIN') || $this->isGranted('ROLE_RESPONSABLE_ETUDIANT') => 
                $suiviRepository->findAll(),
            $this->isGranted('ROLE_PSYCHOLOGUE') => 
                $suiviRepository->findByPsychologue($user),
            $this->isGranted('ROLE_ETUDIANT') => 
                $suiviRepository->findByEtudiant($user),
            default => []
        };

        // Filtres
        $statut = $request->query->get('statut');
        if ($statut) {
            $suivis = array_filter($suivis, function($suivi) use ($statut) {
                return match($statut) {
                    'effectue' => $suivi->isEffectue(),
                    'valide' => $suivi->isValide(),
                    'en_attente' => $suivi->isEffectue() && !$suivi->isValide(),
                    'non_effectue' => !$suivi->isEffectue(),
                    default => true
                };
            });
        }

        $traitementId = $request->query->get('traitement_id');
        if ($traitementId) {
            $suivis = array_filter($suivis, function($suivi) use ($traitementId) {
                return $suivi->getTraitement()->getId() == $traitementId;
            });
        }

        // Recherche
        $search = $request->query->get('search');
        if ($search) {
            $suivis = array_filter($suivis, function($suivi) use ($search) {
                return ($suivi->getObservations() && stripos($suivi->getObservations(), $search) !== false) ||
                       ($suivi->getObservationsPsy() && stripos($suivi->getObservationsPsy(), $search) !== false);
            });
        }

        // Tri
        $sortBy = $request->query->get('sort', 'dateSuivi');
        $order = $request->query->get('order', 'desc');
        
        usort($suivis, function($a, $b) use ($sortBy, $order) {
            $method = 'get' . ucfirst($sortBy);
            $valA = $a->$method();
            $valB = $b->$method();
            
            if ($valA instanceof \DateTimeInterface) {
                $valA = $valA->getTimestamp();
                $valB = $valB->getTimestamp();
            }
            
            return $order === 'desc' ? $valB <=> $valA : $valA <=> $valB;
        });

        $data = $this->serializer->normalize($suivis, null, [
            AbstractNormalizer::GROUPS => ['suivi:read']
        ]);

        return new JsonResponse($data);
    }

    #[Route('/{id}', name: 'api_suivi_show', methods: ['GET'])]
    public function show(SuiviTraitement $suivi): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
            return new JsonResponse(['error' => 'Non autorisé'], Response::HTTP_UNAUTHORIZED);
        }

        // Vérifier les droits d'accès
        if (!$this->canAccessSuivi($suivi, $user)) {
            return new JsonResponse(['error' => 'Accès refusé'], Response::HTTP_FORBIDDEN);
        }

        $data = $this->serializer->normalize($suivi, null, [
            AbstractNormalizer::GROUPS => ['suivi:read', 'suivi:detail']
        ]);

        return new JsonResponse($data);
    }

    #[Route('/', name: 'api_suivi_create', methods: ['POST'])]
    public function create(Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
            return new JsonResponse(['error' => 'Non autorisé'], Response::HTTP_UNAUTHORIZED);
        }

        $data = json_decode($request->getContent(), true);
        if (!$data) {
            return new JsonResponse(['error' => 'Données invalides'], Response::HTTP_BAD_REQUEST);
        }

        try {
            $suivi = new SuiviTraitement();
            
            // Récupérer le traitement
            $traitement = $entityManager->getRepository(Traitement::class)->find($data['traitement_id']);
            if (!$traitement) {
                return new JsonResponse(['error' => 'Traitement non trouvé'], Response::HTTP_NOT_FOUND);
            }

            // Vérifier les droits sur le traitement
            if (!$this->canAccessTraitement($traitement, $user)) {
                return new JsonResponse(['error' => 'Accès au traitement refusé'], Response::HTTP_FORBIDDEN);
            }

            $suivi->setTraitement($traitement);
            $suivi->setDateSuivi(new \DateTime($data['dateSuivi'] ?? 'now'));
            $suivi->setObservations($data['observations'] ?? null);
            $suivi->setEvaluation($data['evaluation'] ?? null);

            // Gérer l'état effectué selon le rôle
            if ($this->isGranted('ROLE_ETUDIANT')) {
                $suivi->setEffectue($data['effectue'] ?? false);
                $suivi->setValide(false); // L'étudiant ne peut pas valider
            } else {
                $suivi->setEffectue($data['effectue'] ?? false);
                $suivi->setValide($data['valide'] ?? false);
            }

            if ($suivi->isEffectue() && !$suivi->getHeureEffective()) {
                $suivi->setHeureEffective(new \DateTime());
            }

            $entityManager->persist($suivi);
            $entityManager->flush();

            $data = $this->serializer->normalize($suivi, null, [
                AbstractNormalizer::GROUPS => ['suivi:read']
            ]);

            return new JsonResponse($data, Response::HTTP_CREATED);

        } catch (\Exception $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }

    #[Route('/{id}/effectuer', name: 'api_suivi_effectuer', methods: ['POST'])]
    public function effectuer(SuiviTraitement $suivi, EntityManagerInterface $entityManager): JsonResponse
    {
        $user = $this->getUser();
        if (!$user || !$this->canEditSuivi($suivi, $user)) {
            return new JsonResponse(['error' => 'Non autorisé'], Response::HTTP_UNAUTHORIZED);
        }

        try {
            $suivi->marquerEffectue();
            
            // Si c'est un étudiant, ne pas valider automatiquement
            if ($this->isGranted('ROLE_ETUDIANT')) {
                $suivi->setValide(false);
            }

            $entityManager->flush();

            $data = $this->serializer->normalize($suivi, null, [
                AbstractNormalizer::GROUPS => ['suivi:read']
            ]);

            return new JsonResponse($data);

        } catch (\Exception $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }

    #[Route('/{id}/valider', name: 'api_suivi_valider', methods: ['POST'])]
    public function valider(SuiviTraitement $suivi, EntityManagerInterface $entityManager): JsonResponse
    {
        $user = $this->getUser();
        if (!$user || !$this->canValidateSuivi($suivi, $user)) {
            return new JsonResponse(['error' => 'Non autorisé'], Response::HTTP_UNAUTHORIZED);
        }

        try {
            $suivi->valider();
            $entityManager->flush();

            $data = $this->serializer->normalize($suivi, null, [
                AbstractNormalizer::GROUPS => ['suivi:read']
            ]);

            return new JsonResponse($data);

        } catch (\Exception $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }

    #[Route('/statistiques', name: 'api_suivi_statistiques', methods: ['GET'])]
    public function statistiques(SuiviTraitementRepository $suiviRepository): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
            return new JsonResponse(['error' => 'Non autorisé'], Response::HTTP_UNAUTHORIZED);
        }

        $stats = match (true) {
            $this->isGranted('ROLE_PSYCHOLOGUE') => 
                $suiviRepository->getStatistiquesByPsychologue($user),
            $this->isGranted('ROLE_ETUDIANT') => 
                $suiviRepository->getStatistiquesByEtudiant($user),
            default => []
        };

        return new JsonResponse($stats);
    }

    private function canAccessSuivi(SuiviTraitement $suivi, $user): bool
    {
        return $this->canAccessTraitement($suivi->getTraitement(), $user);
    }

    private function canAccessTraitement($traitement, $user): bool
    {
        if ($this->isGranted('ROLE_ADMIN') || $this->isGranted('ROLE_RESPONSABLE_ETUDIANT')) {
            return true;
        }

        if ($this->isGranted('ROLE_PSYCHOLOGUE') && $traitement->getPsychologue() === $user) {
            return true;
        }

        if ($this->isGranted('ROLE_ETUDIANT') && $traitement->getEtudiant() === $user) {
            return true;
        }

        return false;
    }

    private function canEditSuivi(SuiviTraitement $suivi, $user): bool
    {
        if ($this->isGranted('ROLE_ADMIN')) {
            return true;
        }

        if ($this->isGranted('ROLE_PSYCHOLOGUE') && $suivi->getTraitement()->getPsychologue() === $user) {
            return true;
        }

        if ($this->isGranted('ROLE_ETUDIANT') && $suivi->getTraitement()->getEtudiant() === $user && !$suivi->isValide()) {
            return true;
        }

        return false;
    }

    private function canValidateSuivi(SuiviTraitement $suivi, $user): bool
    {
        if ($this->isGranted('ROLE_ADMIN')) {
            return true;
        }

        if ($this->isGranted('ROLE_PSYCHOLOGUE') && $suivi->getTraitement()->getPsychologue() === $user && $suivi->isEffectue() && !$suivi->isValide()) {
            return true;
        }

        return false;
    }
}
