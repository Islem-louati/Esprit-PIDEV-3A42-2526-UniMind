<?php

namespace App\Controller\Api;

use App\Entity\Traitement;
use App\Entity\User;
use App\Repository\TraitementRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\SerializerInterface;

#[Route('/api/traitements')]
class ApiTraitementController extends AbstractController
{
    private SerializerInterface $serializer;

    public function __construct(SerializerInterface $serializer)
    {
        $this->serializer = $serializer;
    }

    #[Route('/', name: 'api_traitement_index', methods: ['GET'])]
    public function index(TraitementRepository $traitementRepository, Request $request): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
            return new JsonResponse(['error' => 'Non autorisé'], Response::HTTP_UNAUTHORIZED);
        }

        // Filtrer selon le rôle
        $traitements = match (true) {
            $this->isGranted('ROLE_ADMIN') || $this->isGranted('ROLE_RESPONSABLE_ETUDIANT') => 
                $traitementRepository->findAll(),
            $this->isGranted('ROLE_PSYCHOLOGUE') => 
                $traitementRepository->findBy(['psychologue' => $user]),
            $this->isGranted('ROLE_ETUDIANT') => 
                $traitementRepository->findBy(['etudiant' => $user]),
            default => []
        };

        // Recherche
        $search = $request->query->get('search');
        if ($search) {
            $traitements = array_filter($traitements, function($traitement) use ($search) {
                return stripos($traitement->getTitre(), $search) !== false || 
                       stripos($traitement->getDescription(), $search) !== false;
            });
        }

        // Tri
        $sortBy = $request->query->get('sort', 'createdAt');
        $order = $request->query->get('order', 'desc');
        
        usort($traitements, function($a, $b) use ($sortBy, $order) {
            $method = 'get' . ucfirst($sortBy);
            $valA = $a->$method();
            $valB = $b->$method();
            
            if ($valA instanceof \DateTimeInterface) {
                $valA = $valA->getTimestamp();
                $valB = $valB->getTimestamp();
            }
            
            return $order === 'desc' ? $valB <=> $valA : $valA <=> $valB;
        });

        $data = $this->serializer->normalize($traitements, null, [
            AbstractNormalizer::GROUPS => ['traitement:read'],
            AbstractNormalizer::IGNORED_ATTRIBUTES => ['suivis']
        ]);

        return new JsonResponse($data);
    }

    #[Route('/{id}', name: 'api_traitement_show', methods: ['GET'])]
    public function show(Traitement $traitement): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
            return new JsonResponse(['error' => 'Non autorisé'], Response::HTTP_UNAUTHORIZED);
        }

        // Vérifier les droits d'accès
        if (!$this->canAccessTraitement($traitement, $user)) {
            return new JsonResponse(['error' => 'Accès refusé'], Response::HTTP_FORBIDDEN);
        }

        $data = $this->serializer->normalize($traitement, null, [
            AbstractNormalizer::GROUPS => ['traitement:read', 'traitement:detail']
        ]);

        return new JsonResponse($data);
    }

    #[Route('/create', name: 'api_traitement_create', methods: ['POST'])]
    public function create(Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        $user = $this->getUser();
        if (!$user || !$this->isGranted('ROLE_PSYCHOLOGUE')) {
            return new JsonResponse(['error' => 'Non autorisé'], Response::HTTP_UNAUTHORIZED);
        }

        $data = json_decode($request->getContent(), true);
        if (!$data) {
            return new JsonResponse(['error' => 'Données invalides'], Response::HTTP_BAD_REQUEST);
        }

        try {
            $traitement = new Traitement();
            $traitement->setTitre($data['titre'] ?? '');
            $traitement->setDescription($data['description'] ?? '');
            $traitement->setType($data['type'] ?? '');
            $traitement->setDureeJours($data['duree_jours'] ?? 30);
            $traitement->setDateDebut(new \DateTime($data['date_debut'] ?? 'now'));
            $traitement->setObjectifTherapeutique($data['objectif_therapeutique'] ?? '');
            $traitement->setPsychologue($user);

            // Gérer l'étudiant
            if (isset($data['etudiant_id'])) {
                $etudiant = $entityManager->getRepository(User::class)->find($data['etudiant_id']);
                if ($etudiant && $etudiant->isEtudiant()) {
                    $traitement->setEtudiant($etudiant);
                }
            }

            $entityManager->persist($traitement);
            $entityManager->flush();

            $data = $this->serializer->normalize($traitement, null, [
                AbstractNormalizer::GROUPS => ['traitement:read']
            ]);

            return new JsonResponse($data, Response::HTTP_CREATED);

        } catch (\Exception $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }

    #[Route('/{id}', name: 'api_traitement_update', methods: ['PUT'])]
    public function update(Traitement $traitement, Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        $user = $this->getUser();
        if (!$user || !$this->canEditTraitement($traitement, $user)) {
            return new JsonResponse(['error' => 'Non autorisé'], Response::HTTP_UNAUTHORIZED);
        }

        $data = json_decode($request->getContent(), true);
        if (!$data) {
            return new JsonResponse(['error' => 'Données invalides'], Response::HTTP_BAD_REQUEST);
        }

        try {
            if (isset($data['titre'])) $traitement->setTitre($data['titre']);
            if (isset($data['description'])) $traitement->setDescription($data['description']);
            if (isset($data['type'])) $traitement->setType($data['type']);
            if (isset($data['duree_jours'])) $traitement->setDureeJours($data['duree_jours']);
            if (isset($data['objectif_therapeutique'])) $traitement->setObjectifTherapeutique($data['objectif_therapeutique']);

            $entityManager->flush();

            $data = $this->serializer->normalize($traitement, null, [
                AbstractNormalizer::GROUPS => ['traitement:read']
            ]);

            return new JsonResponse($data);

        } catch (\Exception $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }

    #[Route('/{id}', name: 'api_traitement_delete', methods: ['DELETE'])]
    public function delete(Traitement $traitement, EntityManagerInterface $entityManager): JsonResponse
    {
        $user = $this->getUser();
        if (!$user || !$this->canEditTraitement($traitement, $user)) {
            return new JsonResponse(['error' => 'Non autorisé'], Response::HTTP_UNAUTHORIZED);
        }

        try {
            $entityManager->remove($traitement);
            $entityManager->flush();

            return new JsonResponse(['message' => 'Traitement supprimé avec succès']);

        } catch (\Exception $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }

    private function canAccessTraitement(Traitement $traitement, $user): bool
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

    private function canEditTraitement(Traitement $traitement, $user): bool
    {
        if ($this->isGranted('ROLE_ADMIN')) {
            return true;
        }

        if ($this->isGranted('ROLE_PSYCHOLOGUE') && $traitement->getPsychologue() === $user) {
            return true;
        }

        return false;
    }
}
