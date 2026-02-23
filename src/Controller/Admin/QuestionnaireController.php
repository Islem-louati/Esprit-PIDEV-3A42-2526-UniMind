<?php
// src/Controller/Admin/QuestionnaireController.php

namespace App\Controller\Admin;

use App\Entity\Questionnaire;
use App\Entity\Question;
use App\Entity\ReponseQuestionnaire;
use App\Form\QuestionnaireType;
use App\Repository\QuestionnaireRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin/questionnaire')]
class QuestionnaireController extends AbstractController
{
    #[Route('/', name: 'admin_questionnaire_index', methods: ['GET'])]
    public function index(QuestionnaireRepository $questionnaireRepository): Response
    {
        return $this->render('admin/questionnaire/index.html.twig', [
            'questionnaires' => $questionnaireRepository->findAllWithQuestions(),
        ]);
    }

    #[Route('/new', name: 'admin_questionnaire_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $questionnaire = new Questionnaire();
        $form = $this->createForm(QuestionnaireType::class, $questionnaire);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            if (!$form->isValid()) {
                $errors = [];
                foreach ($form->getErrors(true) as $error) {
                    $errors[] = $error->getMessage();
                }
                $this->addFlash('error', 'Des champs obligatoires sont vides ou invalides.');
            }

            if ($form->isValid()) {
                try {
                    $questionnaire->setAdmin($this->getUser());
                    $entityManager->persist($questionnaire);
                    $entityManager->flush();
                    
                    $this->addFlash('success', 'Questionnaire créé avec succès !');
                    return $this->redirectToRoute('admin_questionnaire_index');
                    
                } catch (\Exception $e) {
                    $this->addFlash('error', 'Erreur lors de la création : ' . $e->getMessage());
                }
            }
        }

        return $this->render('admin/questionnaire/new.html.twig', [
            'questionnaire' => $questionnaire,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}', name: 'admin_questionnaire_show', methods: ['GET'])]
    public function show(Questionnaire $questionnaire): Response
    {
        return $this->render('admin/questionnaire/show.html.twig', [
            'questionnaire' => $questionnaire,
        ]);
    }

    #[Route('/{id}/edit', name: 'admin_questionnaire_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Questionnaire $questionnaire, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(QuestionnaireType::class, $questionnaire);
        $form->handleRequest($request);

        if ($form->isSubmitted() && !$form->isValid()) {
            $this->addFlash('error', 'Des champs obligatoires sont vides ou invalides.');
        }

        if ($form->isSubmitted() && $form->isValid()) {
            $questionnaire->setUpdatedAt(new \DateTime());
            $entityManager->flush();

            $this->addFlash('success', 'Questionnaire modifié avec succès !');
            return $this->redirectToRoute('admin_questionnaire_show', ['id' => $questionnaire->getQuestionnaireId()]);
        }

        return $this->render('admin/questionnaire/edit.html.twig', [
            'questionnaire' => $questionnaire,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}', name: 'admin_questionnaire_delete', methods: ['POST'])]
    public function delete(Request $request, Questionnaire $questionnaire, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete' . $questionnaire->getId(), $request->request->get('_token'))) {
            $entityManager->remove($questionnaire);
            $entityManager->flush();
            $this->addFlash('success', 'Questionnaire supprimé avec succès !');
        }

        return $this->redirectToRoute('admin_questionnaire_index');
    }

    #[Route('/{id}/questions', name: 'admin_questionnaire_questions', methods: ['GET'])]
    public function questions(Questionnaire $questionnaire): Response
    {
        return $this->render('admin/questionnaire/questions.html.twig', [
            'questionnaire' => $questionnaire,
            'questions' => $questionnaire->getQuestions(),
        ]);
    }

    #[Route('/{id}/duplicate', name: 'admin_questionnaire_duplicate', methods: ['POST'])]
    public function duplicate(Request $request, Questionnaire $questionnaire, EntityManagerInterface $entityManager): Response
    {
        if (!$this->isCsrfTokenValid('duplicate' . $questionnaire->getQuestionnaireId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Token de sécurité invalide.');
            return $this->redirectToRoute('admin_questionnaire_show', ['id' => $questionnaire->getQuestionnaireId()]);
        }

        try {
            $newQuestionnaire = new Questionnaire();
            $newQuestionnaire->setNom($questionnaire->getNom() . ' (Copie)');
            $newQuestionnaire->setDescription($questionnaire->getDescription());
            $newQuestionnaire->setType($questionnaire->getType());
            $newQuestionnaire->setInterpretatLegere($questionnaire->getInterpretatLegere());
            $newQuestionnaire->setInterpretatModere($questionnaire->getInterpretatModere());
            $newQuestionnaire->setInterpretatSevere($questionnaire->getInterpretatSevere());
            $newQuestionnaire->setSeuilLeger($questionnaire->getSeuilLeger());
            $newQuestionnaire->setSeuilModere($questionnaire->getSeuilModere());
            $newQuestionnaire->setSeuilSevere($questionnaire->getSeuilSevere());
            $newQuestionnaire->setNbreQuestions($questionnaire->getNbreQuestions());
            $newQuestionnaire->setAdmin($this->getUser());
            $newQuestionnaire->setCode($newQuestionnaire->generateCode());

            foreach ($questionnaire->getQuestions() as $question) {
                $newQuestion = clone $question;
                $newQuestion->setQuestionnaire($newQuestionnaire);
                $newQuestionnaire->addQuestion($newQuestion);
            }

            $entityManager->persist($newQuestionnaire);
            $entityManager->flush();

            $this->addFlash('success', 'Questionnaire dupliqué avec succès !');
            return $this->redirectToRoute('admin_questionnaire_show', ['id' => $newQuestionnaire->getQuestionnaireId()]);

        } catch (\Exception $e) {
            $this->addFlash('error', 'Erreur lors de la duplication : ' . $e->getMessage());
            return $this->redirectToRoute('admin_questionnaire_show', ['id' => $questionnaire->getQuestionnaireId()]);
        }
    }

    #[Route('/{id}/export', name: 'admin_questionnaire_export', methods: ['GET'])]
    public function export(Questionnaire $questionnaire): Response
    {
        $data = [
            'questionnaire' => [
                'nom' => $questionnaire->getNom(),
                'code' => $questionnaire->getCode(),
                'type' => $questionnaire->getType(),
                'description' => $questionnaire->getDescription(),
                'seuils' => [
                    'leger' => $questionnaire->getSeuilLeger(),
                    'modere' => $questionnaire->getSeuilModere(),
                    'severe' => $questionnaire->getSeuilSevere()
                ],
                'interpretations' => [
                    'legere' => $questionnaire->getInterpretatLegere(),
                    'modere' => $questionnaire->getInterpretatModere(),
                    'severe' => $questionnaire->getInterpretatSevere()
                ],
                'questions' => []
            ]
        ];

        foreach ($questionnaire->getQuestions() as $question) {
            $questionData = [
                'texte' => $question->getTexte(),
                'type' => $question->getType(),
                'ordre' => $question->getOrdre(),
                'options' => []
            ];

            foreach ($question->getOptionsQuest() as $option) {
                $questionData['options'][] = [
                    'texte' => $option->getTexte(),
                    'valeur' => $option->getValeur(),
                    'ordre' => $option->getOrdre()
                ];
            }

            $data['questionnaire']['questions'][] = $questionData;
        }

        $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        
        $filename = sprintf('questionnaire_%s_%s.json', 
            $questionnaire->getCode(), 
            date('Ymd_His')
        );

        $response = new Response($json);
        $response->headers->set('Content-Type', 'application/json');
        $response->headers->set('Content-Disposition', 'attachment; filename="' . $filename . '"');

        return $response;
    }

    #[Route('/{id}/statistiques', name: 'admin_questionnaire_statistiques', methods: ['GET'])]
    public function statistiques(Questionnaire $questionnaire): Response
    {
        $stats = $this->calculerStatistiques($questionnaire);
        
        return $this->render('admin/questionnaire/statistiques.html.twig', [
            'questionnaire' => $questionnaire,
            'stats' => $stats
        ]);
    }

    #[Route('/{id}/statistiques/export/csv', name: 'admin_statistiques_export_csv', methods: ['GET'])]
    public function exportStatistiquesCsv(Questionnaire $questionnaire): Response
    {
        $stats = $this->calculerStatistiques($questionnaire);
        
        $csv = [];
        $csv[] = ['Questionnaire', $questionnaire->getNom()];
        $csv[] = ['Code', $questionnaire->getCode()];
        $csv[] = ['Type', $questionnaire->getType()];
        $csv[] = [];
        $csv[] = ['STATISTIQUES GLOBALES'];
        $csv[] = ['Total réponses', $stats['total_reponses']];
        $csv[] = ['Score moyen', $stats['score_moyen'] ?? 'N/A'];
        $csv[] = ['Score minimum', $stats['score_min'] ?? 'N/A'];
        $csv[] = ['Score maximum', $stats['score_max'] ?? 'N/A'];
        $csv[] = [];
        $csv[] = ['DISTRIBUTION PAR NIVEAU'];
        $csv[] = ['Niveau', 'Nombre', 'Pourcentage'];
        
        foreach ($stats['distribution'] as $niveau => $count) {
            $csv[] = [ucfirst($niveau), $count, ($stats['pourcentages'][$niveau] ?? 0) . '%'];
        }
        
        $csv[] = [];
        $csv[] = ['DÉTAIL DES RÉPONSES'];
        $csv[] = ['ID', 'Score', 'Date', 'Niveau'];
        
        foreach ($stats['scores'] as $score) {
            $csv[] = [$score['id'], $score['score'], $score['date'], $score['niveau']];
        }
        
        $output = fopen('php://temp', 'r+');
        foreach ($csv as $row) {
            fputcsv($output, $row, ';');
        }
        rewind($output);
        $csvContent = stream_get_contents($output);
        fclose($output);
        
        $csvContent = "\xEF\xBB\xBF" . $csvContent;
        
        $filename = sprintf('statistiques_%s_%s.csv', $questionnaire->getCode(), date('Ymd_His'));
        
        $response = new Response($csvContent);
        $response->headers->set('Content-Type', 'text/csv; charset=utf-8');
        $response->headers->set('Content-Disposition', 'attachment; filename="' . $filename . '"');
        
        return $response;
    }

    #[Route('/{id}/statistiques/export/json', name: 'admin_statistiques_export_json', methods: ['GET'])]
    public function exportStatistiquesJson(Questionnaire $questionnaire): Response
    {
        $stats = $this->calculerStatistiques($questionnaire);
        
        $data = [
            'questionnaire' => [
                'id' => $questionnaire->getQuestionnaireId(),
                'nom' => $questionnaire->getNom(),
                'code' => $questionnaire->getCode(),
                'type' => $questionnaire->getType(),
            ],
            'statistiques' => [
                'total_reponses' => $stats['total_reponses'],
                'score_moyen' => $stats['score_moyen'],
                'score_min' => $stats['score_min'],
                'score_max' => $stats['score_max'],
                'besoin_psy' => $stats['besoin_psy'],
            ],
            'distribution' => $stats['distribution'],
            'pourcentages' => $stats['pourcentages'],
            'reponses' => $stats['scores'],
            'date_export' => date('Y-m-d H:i:s')
        ];
        
        $filename = sprintf('statistiques_%s_%s.json', $questionnaire->getCode(), date('Ymd_His'));
        
        $response = new JsonResponse($data);
        $response->headers->set('Content-Disposition', 'attachment; filename="' . $filename . '"');
        
        return $response;
    }

    /**
     * ✅ Statistiques globales pour l'administration
     */
    #[Route('/global-stats', name: 'admin_global_stats', methods: ['GET'])]
    public function globalStats(EntityManagerInterface $entityManager): Response
    {
        // Récupérer toutes les réponses
        $reponses = $entityManager->getRepository(ReponseQuestionnaire::class)->findAll();
        
        // Compter les questionnaires
        $totalQuestionnaires = $entityManager->getRepository(Questionnaire::class)->count([]);
        $totalQuestions = $entityManager->getRepository(Question::class)->count([]);
        $totalReponses = count($reponses);
        
        // Calculer les scores moyens
        $scoreTotal = 0;
        $besoinPsy = 0;
        foreach ($reponses as $reponse) {
            $scoreTotal += $reponse->getScoreTotale();
            if ($reponse->isABesoinPsy()) {
                $besoinPsy++;
            }
        }
        $scoreMoyen = $totalReponses > 0 ? round($scoreTotal / $totalReponses, 1) : 0;
        
        // Répartition par niveau
        $niveaux = ['leger' => 0, 'modere' => 0, 'severe' => 0];
        foreach ($reponses as $reponse) {
            $niveau = $reponse->getNiveau();
            if (isset($niveaux[$niveau])) {
                $niveaux[$niveau]++;
            }
        }
        
        // Calculer les pourcentages
        $pourcentages = [];
        if ($totalReponses > 0) {
            foreach ($niveaux as $niveau => $count) {
                $pourcentages[$niveau] = round(($count / $totalReponses) * 100, 1);
            }
        }
        
        // Questionnaires complets
        $questionnaires = $entityManager->getRepository(Questionnaire::class)->findAll();
        $complets = 0;
        foreach ($questionnaires as $q) {
            if ($q->isComplete()) {
                $complets++;
            }
        }
        
        // Dernières réponses
        $dernieresReponses = [];
        foreach (array_slice($reponses, 0, 10) as $reponse) {
            $dernieresReponses[] = [
                'date' => $reponse->getCreatedAt()->format('d/m/Y H:i'),
                'questionnaire' => $reponse->getQuestionnaire() ? $reponse->getQuestionnaire()->getNom() : 'N/A',
                'score' => $reponse->getScoreTotale(),
                'niveau' => $reponse->getNiveau(),
                'besoin_psy' => $reponse->isABesoinPsy() ? 'Oui' : 'Non',
            ];
        }
        
        // Structure des données pour le template stats.html.twig
        $stats = [
            'generales' => [
                'questionnaires_count' => $totalQuestionnaires,
                'questions_count' => $totalQuestions,
                'reponses_count' => $totalReponses,
                'questionnaires_complets' => $complets,
                'taux_completion_moyen' => $totalQuestionnaires > 0 ? round(($complets / $totalQuestionnaires) * 100) : 0,
                'utilisateurs_uniques' => $besoinPsy,
            ],
            'questionnaires' => [
                'stress' => ['nom' => 'Stress', 'nb_questionnaires' => 0, 'nb_questions' => 0, 'nb_reponses' => 0, 'score_moyen' => 0, 'score_min' => 0, 'score_max' => 0],
                'anxiete' => ['nom' => 'Anxiété', 'nb_questionnaires' => 0, 'nb_questions' => 0, 'nb_reponses' => 0, 'score_moyen' => 0, 'score_min' => 0, 'score_max' => 0],
                'depression' => ['nom' => 'Dépression', 'nb_questionnaires' => 0, 'nb_questions' => 0, 'nb_reponses' => 0, 'score_moyen' => 0, 'score_min' => 0, 'score_max' => 0],
                'bienetre' => ['nom' => 'Bien-être', 'nb_questionnaires' => 0, 'nb_questions' => 0, 'nb_reponses' => 0, 'score_moyen' => 0, 'score_min' => 0, 'score_max' => 0],
                'sommeil' => ['nom' => 'Sommeil', 'nb_questionnaires' => 0, 'nb_questions' => 0, 'nb_reponses' => 0, 'score_moyen' => 0, 'score_min' => 0, 'score_max' => 0],
            ],
            'reponses' => [
                'repartition_niveaux' => $niveaux,
                'pourcentage_niveaux' => $pourcentages,
                'dernieres_reponses' => $dernieresReponses,
            ],
            'evolution' => [
                'mois' => ['Jan', 'Fév', 'Mar', 'Avr', 'Mai', 'Juin', 'Juil', 'Août', 'Sep', 'Oct', 'Nov', 'Déc'],
                'reponses' => [0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0],
                'scores' => [0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0],
            ]
        ];
        
        return $this->render('admin/dashboard/stats.html.twig', [
            'stats' => $stats
        ]);
    }

    /**
     * ✅ TEST - Route ultra-simple
     */
    #[Route('/test-simple', name: 'admin_test_simple', methods: ['GET'])]
    public function testSimple(): Response
    {
        return new Response('Route test OK');
    }

    /**
     * ✅ Méthode privée pour calculer les statistiques d'un questionnaire
     */
    private function calculerStatistiques(Questionnaire $questionnaire): array
    {
        $reponses = $questionnaire->getReponses();
        $totalReponses = $reponses->count();
        
        $scoreMoyen = null;
        if ($totalReponses > 0) {
            $totalScore = 0;
            foreach ($reponses as $reponse) {
                $totalScore += $reponse->getScoreTotale();
            }
            $scoreMoyen = $totalScore / $totalReponses;
        }

        $distribution = [
            'leger' => 0,
            'modere' => 0,
            'severe' => 0
        ];

        $scores = [];
        foreach ($reponses as $reponse) {
            $niveau = $reponse->getNiveau();
            if (isset($distribution[$niveau])) {
                $distribution[$niveau]++;
            }
            
            $scores[] = [
                'id' => $reponse->getId(),
                'score' => $reponse->getScoreTotale(),
                'date' => $reponse->getDateReponse()->format('d/m/Y'),
                'niveau' => $niveau
            ];
        }

        $pourcentages = [];
        if ($totalReponses > 0) {
            foreach ($distribution as $niveau => $count) {
                $pourcentages[$niveau] = round(($count / $totalReponses) * 100, 1);
            }
        }

        $besoinPsy = 0;
        foreach ($reponses as $reponse) {
            if (method_exists($reponse, 'isABesoinPsy') && $reponse->isABesoinPsy()) {
                $besoinPsy++;
            }
        }

        return [
            'total_reponses' => $totalReponses,
            'score_moyen' => $scoreMoyen ? round($scoreMoyen, 1) : null,
            'score_min' => $totalReponses > 0 ? min(array_column($scores, 'score')) : null,
            'score_max' => $totalReponses > 0 ? max(array_column($scores, 'score')) : null,
            'distribution' => $distribution,
            'pourcentages' => $pourcentages,
            'scores' => $scores,
            'besoin_psy' => $besoinPsy
        ];
    }
}