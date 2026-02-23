<?php
// src/Controller/Etudiant/MesReponsesController.php

namespace App\Controller\Etudiant;

use App\Entity\ReponseQuestionnaire;
use App\Entity\Questionnaire;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/etudiant/mes-reponses')]
class MesReponsesController extends AbstractController
{
    // ✅ Route pour le détail d'une réponse
    #[Route('/detail/{id}', name: 'etudiant_mes_reponses_detail')]
    public function detail(ReponseQuestionnaire $reponse): Response
    {
        $reponsesDetaillees = $reponse->getReponsesDetaillees();
        
        return $this->render('etudiant/mes_reponses/show.html.twig', [
            'reponse' => $reponse,
            'reponses_detaillees' => $reponsesDetaillees,
        ]);
    }
    
    // ✅ Route pour la liste des réponses
    #[Route('', name: 'etudiant_mes_reponses')]
    public function index(Request $request, EntityManagerInterface $em): Response
    {
        // Récupérer tous les types de questionnaires disponibles
        $typesQuestionnaire = $em->createQuery(
            'SELECT DISTINCT q.type FROM App\Entity\Questionnaire q WHERE q.type IS NOT NULL ORDER BY q.type ASC'
        )->getResult();
        $typesQuestionnaire = array_column($typesQuestionnaire, 'type');
        
        // Récupérer les paramètres de filtrage
        $filters = $this->getDefaultFilters($request);
        
        // Construire la requête SANS l'étudiant
        $qb = $this->buildQuery($em, $filters);
        $reponses = $qb->getQuery()->getResult();
        
        // Calculer les statistiques du dashboard
        $dashboardStats = $this->calculateDashboardStats($reponses);
        
        // Pagination
        $totalPages = ceil(count($reponses) / $filters['limit']);
        $reponsesPaginatees = $this->paginateResults($reponses, $filters['page'], $filters['limit']);
        
        return $this->render('etudiant/mes_reponses/index.html.twig', [
            'reponses' => $reponsesPaginatees,
            'total_reponses' => $dashboardStats['total_reponses'],
            'score_moyen' => $dashboardStats['score_moyen_global'],
            'meilleur_score' => $dashboardStats['meilleur_score'],
            'stats_par_type' => $dashboardStats['types_avec_stats'],
            'filters' => $filters,
            'types_questionnaire' => $typesQuestionnaire,
            'current_page' => $filters['page'],
            'total_pages' => $totalPages,
        ]);
    }
    
    // ✅ ROUTE POUR L'EXPORT CSV (version simplifiée)
    #[Route('/export', name: 'etudiant_mes_reponses_export')]
    public function export(EntityManagerInterface $em): Response
    {
        // Récupérer toutes les réponses
        $reponses = $em->getRepository(ReponseQuestionnaire::class)
            ->createQueryBuilder('r')
            ->leftJoin('r.questionnaire', 'q')
            ->addSelect('q')
            ->orderBy('r.created_at', 'DESC')
            ->getQuery()
            ->getResult();
        
        // Créer l'en-tête du CSV
        $csv = "ID;Date;Questionnaire;Type;Score;Niveau\n";
        
        // Ajouter les données
        foreach ($reponses as $reponse) {
            $questionnaire = $reponse->getQuestionnaire();
            $csv .= sprintf(
                "%d;%s;%s;%s;%.1f;%s\n",
                $reponse->getReponseQuestionnaireId(),
                $reponse->getCreatedAt()->format('d/m/Y H:i'),
                $questionnaire ? $questionnaire->getNom() : 'N/A',
                $questionnaire ? $questionnaire->getType() : 'N/A',
                $reponse->getScoreTotale(),
                $reponse->getNiveau()
            );
        }
        
        // Ajouter le BOM UTF-8 pour Excel
        $csv = "\xEF\xBB\xBF" . $csv;
        
        // Créer la réponse
        $response = new Response($csv);
        $response->headers->set('Content-Type', 'text/csv; charset=utf-8');
        $response->headers->set('Content-Disposition', 'attachment; filename="reponses_' . date('Ymd_His') . '.csv"');
        
        return $response;
    }
    
    // =====================================================
    // MÉTHODES PRIVÉES
    // =====================================================
    
    private function calculateDashboardStats(array $reponses): array
    {
        if (empty($reponses)) {
            return $this->getEmptyDashboardStats();
        }
        
        $totalScore = 0;
        $meilleurScore = 0;
        $typesComplets = [];
        $statsParType = [];
        $evolutionScores = [];
        $recentResponses = [];
        
        // Trier par date pour les réponses récentes
        usort($reponses, function($a, $b) {
            return $b->getCreatedAt() <=> $a->getCreatedAt();
        });
        
        // Prendre les 5 dernières réponses
        $recentResponses = array_slice($reponses, 0, 5);
        
        foreach ($reponses as $reponse) {
            $score = $reponse->getScoreTotale();
            $questionnaire = $reponse->getQuestionnaire();
            $totalScore += $score;
            
            // Meilleur score global
            if ($score > $meilleurScore) {
                $meilleurScore = $score;
            }
            
            if ($questionnaire) {
                $type = $questionnaire->getType();
                $scoreMax = $questionnaire->getScoreMaxPossible();
                
                // Pourcentage pour ce questionnaire
                $pourcentage = $scoreMax > 0 ? round(($score / $scoreMax) * 100, 1) : 0;
                
                // Types complets (questionnaires avec score max)
                if ($scoreMax > 0) {
                    if (!isset($typesComplets[$type])) {
                        $typesComplets[$type] = [
                            'count' => 0,
                            'total_score' => 0,
                            'total_max' => 0,
                        ];
                    }
                    $typesComplets[$type]['count']++;
                    $typesComplets[$type]['total_score'] += $score;
                    $typesComplets[$type]['total_max'] += $scoreMax;
                }
                
                // Statistiques par type
                if (!isset($statsParType[$type])) {
                    $statsParType[$type] = [
                        'count' => 0,
                        'scores' => [],
                        'pourcentages' => [],
                    ];
                }
                $statsParType[$type]['count']++;
                $statsParType[$type]['scores'][] = $score;
                $statsParType[$type]['pourcentages'][] = $pourcentage;
                
                // Évolution mensuelle (pour graphique)
                $mois = $reponse->getCreatedAt()->format('Y-m');
                if (!isset($evolutionScores[$mois])) {
                    $evolutionScores[$mois] = [
                        'mois' => $reponse->getCreatedAt()->format('M Y'),
                        'total_score' => 0,
                        'count' => 0,
                    ];
                }
                $evolutionScores[$mois]['total_score'] += $score;
                $evolutionScores[$mois]['count']++;
            }
        }
        
        // Calculer les moyennes par type
        $typesAvecStats = [];
        foreach ($statsParType as $type => $data) {
            $moyenneScore = count($data['scores']) > 0 
                ? round(array_sum($data['scores']) / count($data['scores']), 1) 
                : 0;
            $moyennePourcentage = count($data['pourcentages']) > 0 
                ? round(array_sum($data['pourcentages']) / count($data['pourcentages']), 1) 
                : 0;
            
            $typesAvecStats[] = [
                'type' => $type,
                'count' => $data['count'],
                'score_moyen' => $moyenneScore,
                'pourcentage_moyen' => $moyennePourcentage,
                'niveau' => $this->getInterpretation($moyennePourcentage),
            ];
        }
        
        // Calculer les pourcentages pour les types complets
        $pourcentagesParType = [];
        foreach ($typesComplets as $type => $data) {
            $pourcentageMoyen = $data['total_max'] > 0 
                ? round(($data['total_score'] / $data['total_max']) * 100, 1) 
                : 0;
            $pourcentagesParType[$type] = $pourcentageMoyen;
        }
        
        // Préparer les données d'évolution pour le graphique
        $evolutionData = [];
        ksort($evolutionScores);
        foreach ($evolutionScores as $moisData) {
            $moyenneMois = $moisData['count'] > 0 
                ? round($moisData['total_score'] / $moisData['count'], 1) 
                : 0;
            $evolutionData[] = [
                'mois' => $moisData['mois'],
                'score_moyen' => $moyenneMois,
            ];
        }
        
        // Calculer le score moyen global
        $scoreMoyenGlobal = count($reponses) > 0 ? round($totalScore / count($reponses), 1) : 0;
        
        // Meilleur type (par pourcentage moyen)
        $meilleurType = '';
        $meilleurTypePourcentage = 0;
        if (!empty($pourcentagesParType)) {
            arsort($pourcentagesParType);
            $meilleurType = array_key_first($pourcentagesParType);
            $meilleurTypePourcentage = $pourcentagesParType[$meilleurType];
        }
        
        return [
            'total_reponses' => count($reponses),
            'score_moyen_global' => $scoreMoyenGlobal,
            'meilleur_score' => $meilleurScore,
            'types_differents' => count($statsParType),
            'meilleur_type' => $meilleurType,
            'meilleur_type_pourcentage' => $meilleurTypePourcentage,
            'reponses_recentes' => $recentResponses,
            'types_avec_stats' => $typesAvecStats,
            'evolution_scores' => $evolutionData,
            'tendance' => $this->calculateTendance($evolutionData),
            'niveau_global' => $this->getInterpretation($scoreMoyenGlobal),
        ];
    }
    
    private function calculateTendance(array $evolutionData): string
    {
        if (count($evolutionData) < 2) {
            return 'stable';
        }
        
        $recent = array_slice($evolutionData, -3);
        if (count($recent) < 2) {
            return 'stable';
        }
        
        $premier = $recent[0]['score_moyen'];
        $dernier = end($recent)['score_moyen'];
        
        if ($dernier > $premier + 5) {
            return 'amélioration';
        } elseif ($dernier < $premier - 5) {
            return 'dégradation';
        } else {
            return 'stable';
        }
    }
    
    private function getEmptyDashboardStats(): array
    {
        return [
            'total_reponses' => 0,
            'score_moyen_global' => 0,
            'meilleur_score' => 0,
            'types_differents' => 0,
            'meilleur_type' => '',
            'meilleur_type_pourcentage' => 0,
            'reponses_recentes' => [],
            'types_avec_stats' => [],
            'evolution_scores' => [],
            'tendance' => 'stable',
            'niveau_global' => 'Faible',
        ];
    }
    
    private function getDefaultFilters(Request $request): array
    {
        return [
            'type' => $request->query->get('type'),
            'date_from' => $request->query->get('date_from'),
            'date_to' => $request->query->get('date_to'),
            'score_min' => $request->query->get('score_min'),
            'score_max' => $request->query->get('score_max'),
            'search' => $request->query->get('search'),
            'sort' => $request->query->get('sort', 'created_at_desc'),
            'page' => $request->query->getInt('page', 1),
            'limit' => 10,
        ];
    }
    
    private function buildQuery(EntityManagerInterface $em, array $filters)
    {
        $qb = $em->getRepository(ReponseQuestionnaire::class)
            ->createQueryBuilder('r')
            ->leftJoin('r.questionnaire', 'q')
            ->addSelect('q');
        
        if (!empty($filters['type'])) {
            $qb->andWhere('q.type = :type')->setParameter('type', $filters['type']);
        }
        if (!empty($filters['date_from'])) {
            $qb->andWhere('r.created_at >= :date_from')
               ->setParameter('date_from', new \DateTime($filters['date_from']));
        }
        if (!empty($filters['date_to'])) {
            $qb->andWhere('r.created_at <= :date_to')
               ->setParameter('date_to', new \DateTime($filters['date_to'] . ' 23:59:59'));
        }
        if (!empty($filters['score_min'])) {
            $qb->andWhere('r.score_totale >= :score_min')
               ->setParameter('score_min', (float) $filters['score_min']);
        }
        if (!empty($filters['score_max'])) {
            $qb->andWhere('r.score_totale <= :score_max')
               ->setParameter('score_max', (float) $filters['score_max']);
        }
        if (!empty($filters['search'])) {
            $qb->andWhere($qb->expr()->orX(
                $qb->expr()->like('q.nom', ':search'),
                $qb->expr()->like('q.description', ':search')
            ))->setParameter('search', '%' . $filters['search'] . '%');
        }
        
        switch ($filters['sort']) {
            case 'created_at_asc':
                $qb->orderBy('r.created_at', 'ASC');
                break;
            case 'score_desc':
                $qb->orderBy('r.score_totale', 'DESC');
                break;
            case 'score_asc':
                $qb->orderBy('r.score_totale', 'ASC');
                break;
            default:
                $qb->orderBy('r.created_at', 'DESC');
                break;
        }
        
        return $qb;
    }
    
    private function paginateResults(array $results, int $page, int $limit): array
    {
        $offset = ($page - 1) * $limit;
        return array_slice($results, $offset, $limit);
    }
    
    private function getInterpretation(float $pourcentage): string
    {
        if ($pourcentage < 33) return 'Faible';
        if ($pourcentage < 66) return 'Modéré';
        return 'Élevé';
    }
}