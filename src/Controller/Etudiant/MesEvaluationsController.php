<?php
// src/Controller/Etudiant/MesEvaluationsController.php

namespace App\Controller\Etudiant;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\Request;

#[Route('/etudiant')]
class MesEvaluationsController extends AbstractController
{
    #[Route('/mes-evaluations', name: 'etudiant_mes_evaluations')]
    public function index(): Response
    {
        // Données SIMPLES pour tester
        $etudiant = [
            'prenom' => 'Jean',
            'nom' => 'Dupont',
            'email' => 'jean.dupont@test.com',
        ];
        
        $reponses = [
            [
                'id' => 1,
                'score' => 85.5,
                'date' => '2024-02-08',
                'interpretation' => 'Niveau modéré'
            ],
            [
                'id' => 2,
                'score' => 92.0,
                'date' => '2024-02-05',
                'interpretation' => 'Excellent résultat'
            ],
        ];
        
        return $this->render('etudiant/mes_evaluations/index.html.twig', [
            'etudiant' => $etudiant,
            'reponses' => $reponses,
        ]);
    }
    
    #[Route('/mes-evaluations/detail/{id}', name: 'etudiant_mes_evaluations_detail')]
    public function detail(int $id): Response
    {
        $etudiant = [
            'prenom' => 'Jean',
            'nom' => 'Dupont',
        ];
        
        $reponse = [
            'id' => $id,
            'score' => 85.5,
            'date' => '2024-02-08',
            'interpretation' => 'Votre niveau est modéré',
        ];
        
        return $this->render('etudiant/mes_evaluations/detail.html.twig', [
            'etudiant' => $etudiant,
            'reponse' => $reponse,
        ]);
    }
}