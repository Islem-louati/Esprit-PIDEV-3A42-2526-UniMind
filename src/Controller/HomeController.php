<?php

namespace App\Controller;

use App\Repository\EvenementRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    public function index(EvenementRepository $evenementRepository): Response
    {
        $evenements = $evenementRepository->searchWithFilters(statut: 'a_venir', sort: 'dateDebut', order: 'ASC');

        return $this->render('home/index.html.twig', [
            'controller_name' => 'HomeController',
            'evenements' => array_slice($evenements, 0, 6),
        ]);
    }
}
