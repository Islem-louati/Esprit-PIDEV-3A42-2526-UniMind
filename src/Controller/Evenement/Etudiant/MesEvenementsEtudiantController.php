<?php

namespace App\Controller\Evenement\Etudiant;

use App\Entity\Participation;
use App\Enum\RoleType;
use App\Repository\ParticipationRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/etudiant/mes-evenements', name: 'app_mes_evenements_')]
final class MesEvenementsEtudiantController extends AbstractController
{
    #[Route('', name: 'index', methods: ['GET'])]
    public function index(ParticipationRepository $participationRepository, UserRepository $userRepository): Response
    {
        $user = $this->getUser();
        if (!$user && $this->getParameter('kernel.environment') === 'dev') {
            $user = $userRepository->findOneBy(['role' => RoleType::ETUDIANT]);
        }
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        $participations = $participationRepository->findByEtudiantWithEvenement($user);

        $aVenir = [];
        $termines = [];

        foreach ($participations as $participation) {
            $evenement = $participation->getEvenement();
            if ($evenement && $evenement->isTermine()) {
                $termines[] = $participation;
            } else {
                $aVenir[] = $participation;
            }
        }

        return $this->render('evenement/etudiant/mes_evenements/index.html.twig', [
            'participations_a_venir' => $aVenir,
            'participations_terminees' => $termines,
        ]);
    }
}
