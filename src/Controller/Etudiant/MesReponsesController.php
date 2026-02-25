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
use TCPDF;

#[Route('/etudiant/mes-reponses')]
class MesReponsesController extends AbstractController
{
    #[Route('/detail/{id}', name: 'etudiant_mes_reponses_detail')]
    public function detail(ReponseQuestionnaire $reponse): Response
    {
        return $this->render('etudiant/mes_reponses/show.html.twig', [
            'reponse'             => $reponse,
            'reponses_detaillees' => $reponse->getReponsesDetaillees(),
        ]);
    }

    #[Route('', name: 'etudiant_mes_reponses')]
    public function index(Request $request, EntityManagerInterface $em): Response
    {
        $typesQuestionnaire = array_column(
            $em->createQuery(
                'SELECT DISTINCT q.type FROM App\Entity\Questionnaire q WHERE q.type IS NOT NULL ORDER BY q.type ASC'
            )->getResult(),
            'type'
        );

        $filters            = $this->getDefaultFilters($request);
        $reponses           = $this->buildQuery($em, $filters)->getQuery()->getResult();
        $dashboardStats     = $this->calculateDashboardStats($reponses);
        $totalPages         = ceil(count($reponses) / $filters['limit']);
        $reponsesPaginatees = $this->paginateResults($reponses, $filters['page'], $filters['limit']);

        return $this->render('etudiant/mes_reponses/index.html.twig', [
            'reponses'            => $reponsesPaginatees,
            'total_reponses'      => $dashboardStats['total_reponses'],
            'score_moyen'         => $dashboardStats['score_moyen_global'],
            'meilleur_score'      => $dashboardStats['meilleur_score'],
            'stats_par_type'      => $dashboardStats['types_avec_stats'],
            'filters'             => $filters,
            'types_questionnaire' => $typesQuestionnaire,
            'current_page'        => $filters['page'],
            'total_pages'         => $totalPages,
        ]);
    }

    #[Route('/export', name: 'etudiant_mes_reponses_export')]
    public function export(EntityManagerInterface $em): Response
    {
        $reponses = $em->getRepository(ReponseQuestionnaire::class)
            ->createQueryBuilder('r')
            ->leftJoin('r.questionnaire', 'q')
            ->addSelect('q')
            ->orderBy('r.created_at', 'DESC')
            ->getQuery()
            ->getResult();

        $csv = "ID;Date;Questionnaire;Type;Score;Niveau\n";
        foreach ($reponses as $reponse) {
            $q    = $reponse->getQuestionnaire();
            $csv .= sprintf(
                "%d;%s;%s;%s;%.1f;%s\n",
                $reponse->getReponseQuestionnaireId(),
                $reponse->getCreatedAt()->format('d/m/Y H:i'),
                $q ? $q->getNom()  : 'N/A',
                $q ? $q->getType() : 'N/A',
                $reponse->getScoreTotale(),
                $reponse->getNiveau()
            );
        }

        $response = new Response("\xEF\xBB\xBF" . $csv);
        $response->headers->set('Content-Type', 'text/csv; charset=utf-8');
        $response->headers->set('Content-Disposition', 'attachment; filename="reponses_' . date('Ymd_His') . '.csv"');
        return $response;
    }

    // =========================================================================
    // PDF avec vrais champs éditables
    // =========================================================================

    #[Route('/detail/{id}/pdf', name: 'etudiant_mes_reponses_pdf')]
    public function pdf(ReponseQuestionnaire $reponse): Response
    {
        $reponsesDetaillees = $reponse->getReponsesDetaillees();
        $questionnaire      = $reponse->getQuestionnaire();

        // ── Couleur selon niveau ──────────────────────────────────────────────
        $niveau = strtolower((string) $reponse->getNiveau());
        if ($niveau === 'leger')      { [$nR, $nG, $nB] = [28, 200, 138]; }
        elseif ($niveau === 'modere') { [$nR, $nG, $nB] = [246, 194, 62]; }
        elseif ($niveau === 'severe') { [$nR, $nG, $nB] = [231, 74, 59];  }
        else                          { [$nR, $nG, $nB] = [78, 115, 223]; }

        $couleurNiveauHex = sprintf('#%02x%02x%02x', $nR, $nG, $nB);

        // ── Construire les lignes du tableau des réponses ─────────────────────
        $lignesReponses = '';
        foreach ($reponsesDetaillees as $i => $detail) {
            $bg = ($i % 2 === 0) ? '#f8f9fc' : '#ffffff';
            $lignesReponses .= sprintf(
                '<tr style="background-color:%s;">
                    <td width="5%%"  style="padding:4px 6px;border:1px solid #dee2e6;text-align:center;font-size:8px;">%d</td>
                    <td width="55%%" style="padding:4px 6px;border:1px solid #dee2e6;font-size:8px;">%s</td>
                    <td width="30%%" style="padding:4px 6px;border:1px solid #dee2e6;font-size:8px;">%s</td>
                    <td width="10%%" style="padding:4px 6px;border:1px solid #dee2e6;text-align:center;font-size:8px;">%s</td>
                </tr>',
                $bg,
                $i + 1,
                htmlspecialchars((string) $detail['question_texte']),
                htmlspecialchars((string) $detail['reponse']),
                htmlspecialchars((string) $detail['score'])
            );
        }

        // ── Bloc alerte psy ───────────────────────────────────────────────────
        $alertePsy = '';
        if ($reponse->isABesoinPsy()) {
            $alertePsy = '
            <table style="background-color:#fce8e6;border:1px solid #e74a3b;margin-top:6px;" cellpadding="0" cellspacing="0">
                <tr><td style="padding:8px;color:#e74a3b;font-size:9px;">
                    <b>Attention :</b> Votre score indique un niveau necessitant une attention particuliere.
                    Nous vous recommandons de consulter un professionnel de sante mentale.
                </td></tr>
            </table>';
        }

        // ── Bloc interprétation ───────────────────────────────────────────────
        $blocInterpretation = '';
        if ($reponse->getInterpretation()) {
            $blocInterpretation = '
            <h2 style="color:#4e73df;font-size:12px;border-bottom:1px solid #4e73df;padding-bottom:2px;margin-bottom:4px;">
                Interpretation
            </h2>
            <p style="font-size:9px;color:#333333;margin:0 0 4px;">
                ' . htmlspecialchars((string) $reponse->getInterpretation()) . '
            </p>
            ' . $alertePsy . '
            <br>';
        }

        // ── Bloc tableau réponses ─────────────────────────────────────────────
        $blocTableau = '';
        if (!empty($reponsesDetaillees)) {
            $blocTableau = '
            <h2 style="color:#4e73df;font-size:12px;border-bottom:1px solid #4e73df;padding-bottom:2px;margin-bottom:4px;">
                Detail des reponses
            </h2>
            <table style="width:100%;border-collapse:collapse;" cellpadding="0" cellspacing="0">
                <thead>
                    <tr style="background-color:#4e73df;">
                        <th width="5%%"  style="padding:5px 6px;color:#ffffff;font-size:9px;border:1px solid #4e73df;text-align:center;">#</th>
                        <th width="55%%" style="padding:5px 6px;color:#ffffff;font-size:9px;border:1px solid #4e73df;">Question</th>
                        <th width="30%%" style="padding:5px 6px;color:#ffffff;font-size:9px;border:1px solid #4e73df;">Reponse</th>
                        <th width="10%%" style="padding:5px 6px;color:#ffffff;font-size:9px;border:1px solid #4e73df;text-align:center;">Score</th>
                    </tr>
                </thead>
                <tbody>' . $lignesReponses . '</tbody>
            </table><br>';
        }

        // ── HTML principal (partie statique uniquement) ───────────────────────
        $htmlStatique = '
        <style>
            body  { font-family: helvetica; font-size: 10px; color: #333333; }
            h1    { font-size: 16px; color: #ffffff; margin: 0; padding: 0; }
            h2    { font-size: 12px; color: #4e73df; margin: 0 0 4px; }
            table { font-size: 10px; }
        </style>

        <!-- EN-TÊTE BLEU -->
        <table style="background-color:#4e73df;padding:14px;" cellpadding="0" cellspacing="0">
            <tr><td style="text-align:center;">
                <h1>UniMind - Resultat du Questionnaire</h1>
                <p style="color:#dddddd;font-size:9px;margin:4px 0 0;">Genere le ' . date('d/m/Y H:i') . '</p>
            </td></tr>
        </table>
        <br>

        <!-- SCORE / NIVEAU / PSY -->
        <table style="background-color:#4e73df;" cellpadding="0" cellspacing="0">
            <tr>
                <td width="33%%" style="text-align:center;color:#ffffff;padding:10px;">
                    <span style="font-size:24px;font-weight:bold;">' . number_format((float) $reponse->getScoreTotale(), 1) . '</span><br>
                    <span style="font-size:9px;">Score total</span>
                </td>
                <td width="34%%" style="text-align:center;padding:10px;">
                    <span style="font-size:18px;font-weight:bold;color:' . $couleurNiveauHex . ';">' . strtoupper($niveau) . '</span><br>
                    <span style="font-size:9px;color:#ffffff;">Niveau</span>
                </td>
                <td width="33%%" style="text-align:center;color:#ffffff;padding:10px;">
                    <span style="font-size:14px;font-weight:bold;">' . ($reponse->isABesoinPsy() ? 'OUI' : 'NON') . '</span><br>
                    <span style="font-size:9px;">Besoin psychologue</span>
                </td>
            </tr>
        </table>
        <br>

        <!-- INFORMATIONS GÉNÉRALES -->
        <h2 style="color:#4e73df;font-size:12px;border-bottom:1px solid #4e73df;padding-bottom:2px;margin-bottom:6px;">
            Informations generales
        </h2>
        <table cellpadding="0" cellspacing="0">
            <tr>
                <td width="25%%" style="font-weight:bold;color:#555555;font-size:9px;padding:3px 0;">Questionnaire :</td>
                <td width="25%%" style="font-size:9px;padding:3px 6px;">' . htmlspecialchars($questionnaire->getNom()) . '</td>
                <td width="25%%" style="font-weight:bold;color:#555555;font-size:9px;padding:3px 0;">Type :</td>
                <td width="25%%" style="font-size:9px;padding:3px 6px;">' . ucfirst($questionnaire->getType()) . '</td>
            </tr>
            <tr>
                <td style="font-weight:bold;color:#555555;font-size:9px;padding:3px 0;">Date :</td>
                <td style="font-size:9px;padding:3px 6px;">' . $reponse->getCreatedAt()->format('d/m/Y H:i') . '</td>
                <td style="font-weight:bold;color:#555555;font-size:9px;padding:3px 0;">Score :</td>
                <td style="font-size:9px;padding:3px 6px;">'
                    . number_format((float) $reponse->getScoreTotale(), 1)
                    . ' / '
                    . number_format((float) $reponse->getScoreMaxPossible(), 1)
                . '</td>
            </tr>
        </table>
        <br>

        ' . $blocInterpretation . '

        <!-- TITRE COMMENTAIRES -->
        <h2 style="color:#4e73df;font-size:12px;border-bottom:1px solid #4e73df;padding-bottom:2px;margin-bottom:4px;">
            Commentaires personnels
        </h2>
        <p style="font-size:8px;color:#999999;font-style:italic;margin:0 0 3px;">
            Cliquez sur le champ ci-dessous pour ecrire vos commentaires.
        </p>';

        // ── TCPDF : écrire le contenu statique ───────────────────────────────
        $pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8');
        $pdf->SetCreator('UniMind');
        $pdf->SetAuthor('UniMind');
        $pdf->SetTitle('Resultat - ' . $questionnaire->getNom());
        $pdf->SetMargins(15, 15, 15);
        $pdf->SetAutoPageBreak(true, 20);
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        $pdf->AddPage();

        $pdf->writeHTML($htmlStatique, true, false, true, false, '');

        // ── CHAMP COMMENTAIRE MULTILIGNE (natif TCPDF, option minimale) ──────
        $yCommentaire = $pdf->GetY();
        $pdf->Rect(15, $yCommentaire, 180, 30, 'D'); // Bordure visuelle

        // On utilise SetXY + TextField avec options MINIMALES pour éviter array_merge
        try {
            $pdf->TextField('commentaires_personnels', 180, 30,
                ['multiline' => true],
                '', 15, $yCommentaire
            );
        } catch (\Throwable $e) {
            // Fallback : annotation directe si TextField crash
            $pdf->Annotation(15, $yCommentaire, 180, 30, 'commentaires_personnels', [
                'Subtype' => 'Widget', 'ft' => 'Tx', 't' => 'commentaires_personnels',
                'ff' => 4096, 'v' => '', 'dv' => '', 'da' => '/Helvetica 10 Tf 0 g',
            ], 0);
        }

        $pdf->SetY($yCommentaire + 33);
        $pdf->Ln(4);

        // ── RESSENTI ──────────────────────────────────────────────────────────
        $htmlRessenti = '
        <h2 style="color:#4e73df;font-size:12px;border-bottom:1px solid #4e73df;padding-bottom:2px;margin-bottom:4px;">
            Votre ressenti global
        </h2>
        <p style="font-size:9px;color:#555555;margin:0 0 3px;">
            Notez votre ressenti (1 = tres mal, 10 = tres bien) :
        </p>';
        $pdf->writeHTML($htmlRessenti, true, false, true, false, '');

        $yRessenti = $pdf->GetY();
        $pdf->Rect(15, $yRessenti, 20, 7, 'D');

        try {
            $pdf->TextField('ressenti_note', 20, 7,
                [], '', 15, $yRessenti
            );
        } catch (\Throwable $e) {
            $pdf->Annotation(15, $yRessenti, 20, 7, 'ressenti_note', [
                'Subtype' => 'Widget', 'ft' => 'Tx', 't' => 'ressenti_note',
                'ff' => 0, 'v' => '', 'dv' => '', 'da' => '/Helvetica 10 Tf 0 g',
            ], 0);
        }

        $pdf->SetY($yRessenti + 10);
        $pdf->Ln(3);

        // ── CHECKBOX ─────────────────────────────────────────────────────────
        $htmlConfirm = '
        <h2 style="color:#4e73df;font-size:12px;border-bottom:1px solid #4e73df;padding-bottom:2px;margin-bottom:4px;">
            Confirmation
        </h2>';
        $pdf->writeHTML($htmlConfirm, true, false, true, false, '');

        $yCheck = $pdf->GetY();

        try {
            $pdf->CheckBox('prise_de_connaissance', 5, false,
                [], '', '', 15, $yCheck
            );
        } catch (\Throwable $e) {
            // Fallback : carré + annotation
            $pdf->Rect(15, $yCheck, 5, 5, 'D');
            $pdf->Annotation(15, $yCheck, 5, 5, 'prise_de_connaissance', [
                'Subtype' => 'Widget', 'ft' => 'Btn', 't' => 'prise_de_connaissance',
                'ff' => 0, 'v' => '/Off', 'dv' => '/Off', 'da' => '/ZaDb 0 Tf 0 g',
            ], 0);
        }

        $pdf->SetFont('helvetica', '', 9);
        $pdf->SetTextColor(85, 85, 85);
        $pdf->SetXY(23, $yCheck + 0.5);
        $pdf->Cell(160, 5, "J'ai pris connaissance de mes resultats et de l'interpretation.", 0, 1);

        $pdf->SetY($yCheck + 10);
        $pdf->Ln(3);

        // ── Tableau réponses + footer ─────────────────────────────────────────
        $htmlFin = $blocTableau . '
        <hr style="color:#dddddd;"/>
        <p style="text-align:center;color:#aaaaaa;font-size:8px;margin:4px 0;">
            UniMind - Plateforme de sante mentale universitaire<br/>
            Ce document est confidentiel et destine uniquement a l\'usage personnel de l\'etudiant.
        </p>';
        $pdf->writeHTML($htmlFin, true, false, true, false, '');

        // ── Sortie ────────────────────────────────────────────────────────────
        $filename = sprintf('resultat_%s_%s.pdf',
            $questionnaire->getCode(),
            $reponse->getCreatedAt()->format('Ymd_His')
        );

        return new Response(
            $pdf->Output($filename, 'S'),
            200,
            [
                'Content-Type'        => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            ]
        );
    }

    // =========================================================================
    // HELPERS PDF
    // =========================================================================

    private function pdfSectionTitle(TCPDF $pdf, string $title): void
    {
        $pdf->SetTextColor(78, 115, 223);
        $pdf->SetFont('helvetica', 'B', 12);
        $pdf->Cell(0, 8, $title, 0, 1, 'L');
        $pdf->SetDrawColor(78, 115, 223);
        $pdf->SetLineWidth(0.5);
        $pdf->Line(15, $pdf->GetY(), 195, $pdf->GetY());
        $pdf->Ln(3);
    }

    // =========================================================================
    // MÉTHODES MÉTIER
    // =========================================================================

    private function calculateDashboardStats(array $reponses): array
    {
        if (empty($reponses)) {
            return $this->getEmptyDashboardStats();
        }

        $totalScore      = 0;
        $meilleurScore   = 0;
        $typesComplets   = [];
        $statsParType    = [];
        $evolutionScores = [];

        usort($reponses, fn($a, $b) => $b->getCreatedAt() <=> $a->getCreatedAt());
        $recentResponses = array_slice($reponses, 0, 5);

        foreach ($reponses as $reponse) {
            $score         = $reponse->getScoreTotale();
            $questionnaire = $reponse->getQuestionnaire();
            $totalScore   += $score;
            if ($score > $meilleurScore) $meilleurScore = $score;

            if ($questionnaire) {
                $type        = $questionnaire->getType();
                $scoreMax    = $questionnaire->getScoreMaxPossible();
                $pourcentage = $scoreMax > 0 ? round(($score / $scoreMax) * 100, 1) : 0;

                if ($scoreMax > 0) {
                    if (!isset($typesComplets[$type])) {
                        $typesComplets[$type] = ['count' => 0, 'total_score' => 0, 'total_max' => 0];
                    }
                    $typesComplets[$type]['count']++;
                    $typesComplets[$type]['total_score'] += $score;
                    $typesComplets[$type]['total_max']   += $scoreMax;
                }

                if (!isset($statsParType[$type])) {
                    $statsParType[$type] = ['count' => 0, 'scores' => [], 'pourcentages' => []];
                }
                $statsParType[$type]['count']++;
                $statsParType[$type]['scores'][]       = $score;
                $statsParType[$type]['pourcentages'][] = $pourcentage;

                $mois = $reponse->getCreatedAt()->format('Y-m');
                if (!isset($evolutionScores[$mois])) {
                    $evolutionScores[$mois] = [
                        'mois'        => $reponse->getCreatedAt()->format('M Y'),
                        'total_score' => 0,
                        'count'       => 0,
                    ];
                }
                $evolutionScores[$mois]['total_score'] += $score;
                $evolutionScores[$mois]['count']++;
            }
        }

        $typesAvecStats = [];
        foreach ($statsParType as $type => $data) {
            $moy = count($data['pourcentages']) > 0
                ? round(array_sum($data['pourcentages']) / count($data['pourcentages']), 1)
                : 0;
            $typesAvecStats[] = [
                'type'              => $type,
                'count'             => $data['count'],
                'score_moyen'       => count($data['scores']) > 0 ? round(array_sum($data['scores']) / count($data['scores']), 1) : 0,
                'pourcentage_moyen' => $moy,
                'niveau'            => $this->getInterpretation($moy),
            ];
        }

        $pourcentagesParType = [];
        foreach ($typesComplets as $type => $data) {
            $pourcentagesParType[$type] = $data['total_max'] > 0
                ? round(($data['total_score'] / $data['total_max']) * 100, 1)
                : 0;
        }

        ksort($evolutionScores);
        $evolutionData = [];
        foreach ($evolutionScores as $moisData) {
            $evolutionData[] = [
                'mois'        => $moisData['mois'],
                'score_moyen' => $moisData['count'] > 0
                    ? round($moisData['total_score'] / $moisData['count'], 1)
                    : 0,
            ];
        }

        $scoreMoyenGlobal        = count($reponses) > 0 ? round($totalScore / count($reponses), 1) : 0;
        $meilleurType            = '';
        $meilleurTypePourcentage = 0;

        if (!empty($pourcentagesParType)) {
            arsort($pourcentagesParType);
            $meilleurType            = array_key_first($pourcentagesParType);
            $meilleurTypePourcentage = $pourcentagesParType[$meilleurType];
        }

        return [
            'total_reponses'            => count($reponses),
            'score_moyen_global'        => $scoreMoyenGlobal,
            'meilleur_score'            => $meilleurScore,
            'types_differents'          => count($statsParType),
            'meilleur_type'             => $meilleurType,
            'meilleur_type_pourcentage' => $meilleurTypePourcentage,
            'reponses_recentes'         => $recentResponses,
            'types_avec_stats'          => $typesAvecStats,
            'evolution_scores'          => $evolutionData,
            'tendance'                  => $this->calculateTendance($evolutionData),
            'niveau_global'             => $this->getInterpretation($scoreMoyenGlobal),
        ];
    }

    private function calculateTendance(array $evolutionData): string
    {
        if (count($evolutionData) < 2) return 'stable';
        $recent  = array_slice($evolutionData, -3);
        if (count($recent) < 2) return 'stable';
        $premier = $recent[0]['score_moyen'];
        $dernier = end($recent)['score_moyen'];
        if ($dernier > $premier + 5) return 'amélioration';
        if ($dernier < $premier - 5) return 'dégradation';
        return 'stable';
    }

    private function getEmptyDashboardStats(): array
    {
        return [
            'total_reponses'            => 0,
            'score_moyen_global'        => 0,
            'meilleur_score'            => 0,
            'types_differents'          => 0,
            'meilleur_type'             => '',
            'meilleur_type_pourcentage' => 0,
            'reponses_recentes'         => [],
            'types_avec_stats'          => [],
            'evolution_scores'          => [],
            'tendance'                  => 'stable',
            'niveau_global'             => 'Faible',
        ];
    }

    private function getDefaultFilters(Request $request): array
    {
        return [
            'type'      => $request->query->get('type'),
            'date_from' => $request->query->get('date_from'),
            'date_to'   => $request->query->get('date_to'),
            'score_min' => $request->query->get('score_min'),
            'score_max' => $request->query->get('score_max'),
            'search'    => $request->query->get('search'),
            'sort'      => $request->query->get('sort', 'created_at_desc'),
            'page'      => $request->query->getInt('page', 1),
            'limit'     => 10,
        ];
    }

    private function buildQuery(EntityManagerInterface $em, array $filters)
    {
        $qb = $em->getRepository(ReponseQuestionnaire::class)
            ->createQueryBuilder('r')
            ->leftJoin('r.questionnaire', 'q')
            ->addSelect('q');

        if (!empty($filters['type'])) {
            $qb->andWhere('UPPER(q.type) = UPPER(:type)')->setParameter('type', $filters['type']);
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
            case 'created_at_asc': $qb->orderBy('r.created_at',   'ASC');  break;
            case 'score_desc':     $qb->orderBy('r.score_totale', 'DESC'); break;
            case 'score_asc':      $qb->orderBy('r.score_totale', 'ASC');  break;
            default:               $qb->orderBy('r.created_at',   'DESC'); break;
        }

        return $qb;
    }

    private function paginateResults(array $results, int $page, int $limit): array
    {
        return array_slice($results, ($page - 1) * $limit, $limit);
    }

    private function getInterpretation(float $pourcentage): string
    {
        if ($pourcentage < 33) return 'Faible';
        if ($pourcentage < 66) return 'Modéré';
        return 'Élevé';
    }
}
