<?php
// src/Controller/DashboardController.php

namespace App\Controller;

use App\Entity\User;
use App\Entity\RendezVous;
use App\Entity\Traitement;
use App\Entity\SuiviTraitement;
use App\Entity\Profil;
use App\Entity\Evenement;
use App\Entity\Participation;
use App\Repository\CategorieMeditationRepository;
use App\Repository\SeanceMeditationRepository;
use App\Repository\QuestionnaireRepository;
use App\Repository\QuestionRepository;
use App\Repository\ReponseQuestionnaireRepository;
use App\Repository\RendezVousRepository;
use App\Repository\ConsultationRepository;
use App\Repository\TraitementRepository;
use App\Repository\DisponibilitePsyRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class DashboardController extends AbstractController
{
    // ============================
    // ROUTE PRINCIPALE
    // ============================

    #[Route('/dashboard', name: 'app_dashboard')]
    #[IsGranted('ROLE_USER')]
    public function index(): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        if (!$user instanceof User) {
            throw new \LogicException('L\'utilisateur n\'est pas une instance de App\Entity\User');
        }

        $roles = $user->getRoles();

        if (in_array('ROLE_ADMIN', $roles)) {
            return $this->redirectToRoute('admin_dashboard');
        } elseif (in_array('ROLE_PSYCHOLOGUE', $roles)) {
            return $this->redirectToRoute('app_dashboard_psy');
        } elseif (in_array('ROLE_RESPONSABLE_ETUDIANT', $roles)) {
            return $this->redirectToRoute('resp_dashboard');
        } else {
            return $this->redirectToRoute('etudiant_dashboard');
        }
    }

    // ============================
    // DASHBOARD ÉTUDIANT
    // ============================

    #[Route('/dashboard/etudiant', name: 'etudiant_dashboard')]
    #[IsGranted('ROLE_ETUDIANT')]
    public function etudiantDashboard(
        EntityManagerInterface $em,
        RendezVousRepository $rdvRepository,
        ConsultationRepository $consultationRepository,
        TraitementRepository $traitementRepository
    ): Response {
        /** @var User $user */
        $user = $this->getUser();

        // Récupérer les prochains rendez-vous (à implémenter selon votre logique)
        $prochainsRdv = $rdvRepository->findProchainsRendezVous($user); // Méthode à créer si nécessaire

        // Récupérer tous les rendez-vous pour statistiques
        $tousRdv = $rdvRepository->findBy(['etudiant' => $user]);

        // Calcul des statistiques
        $stats = [
            'prochains_rdv' => count($prochainsRdv),
            'total_consultations' => count(array_filter($tousRdv, fn($rdv) => method_exists($rdv, 'isTermine') ? $rdv->isTermine() : false)),
            'traitements_actifs' => $traitementRepository ? $traitementRepository->countActifsByEtudiant($user) : 0,
        ];

        // Rendez-vous passés
        $rdvPasses = array_filter($tousRdv, fn($rdv) => method_exists($rdv, 'isTermine') ? $rdv->isTermine() : false);

        // Questionnaires disponibles (optionnel, issu de votre version)
        $totalQuestionnaires = $em->getRepository(\App\Entity\Questionnaire::class)
            ->createQueryBuilder('q')
            ->select('COUNT(q)')
            ->where('q.nbre_questions > 0')
            ->getQuery()
            ->getSingleScalarResult();

        $questionnairesRecents = $em->getRepository(\App\Entity\Questionnaire::class)
            ->findBy([], ['created_at' => 'DESC'], 5);

        return $this->render('dashboard/etudiant.html.twig', [
            'user' => $user,
            'prochains_rdv' => $prochainsRdv,
            'rdv_passés' => $rdvPasses,
            'stats' => $stats,
            'total_questionnaires' => $totalQuestionnaires,
            'questionnaires_recents' => $questionnairesRecents,
        ]);
    }

    #[Route('/dashboard/etudiant/rendez-vous', name: 'app_etudiant_rdv')]
    #[IsGranted('ROLE_ETUDIANT')]
    public function etudiantRendezVous(RendezVousRepository $rdvRepository): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        $rendezVous = $rdvRepository->findByEtudiantOrderedByDate($user, 'DESC'); // À adapter

        return $this->render('dashboard/etudiant_rdv.html.twig', [
            'user' => $user,
            'rendez_vous' => $rendezVous,
        ]);
    }

    #[Route('/dashboard/etudiant/consultations', name: 'app_etudiant_consultations')]
    #[IsGranted('ROLE_ETUDIANT')]
    public function etudiantConsultations(ConsultationRepository $consultationRepository): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        $consultations = $consultationRepository->findBy(
            ['etudiant' => $user],
            ['date_consultation' => 'DESC']
        );

        return $this->render('dashboard/etudiant_consultations.html.twig', [
            'user' => $user,
            'consultations' => $consultations,
        ]);
    }

    #[Route('/dashboard/etudiant/traitements', name: 'app_etudiant_traitements')]
    #[IsGranted('ROLE_ETUDIANT')]
    public function etudiantTraitements(TraitementRepository $traitementRepository): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        $traitementsActifs = $traitementRepository ? $traitementRepository->findActifsByEtudiant($user) : [];
        $traitementsTermines = $traitementRepository ? $traitementRepository->findTerminesByEtudiant($user) : [];

        return $this->render('dashboard/etudiant.html.twig', [
            'user' => $user,
            'traitements_actifs' => $traitementsActifs,
            'traitements_termines' => $traitementsTermines,
        ]);
    }

    #[Route('/dashboard/etudiant/profil', name: 'app_etudiant_profil')]
    #[IsGranted('ROLE_ETUDIANT')]
    public function etudiantProfil(): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        $profil = $user->getProfil();

        if (!$profil) {
            $profil = new Profil();
            $profil->setUser($user);
        }

        return $this->render('profile/indexP.html.twig', [
            'user' => $user,
            'profil' => $profil,
        ]);
    }

    // ============================
    // DASHBOARD PSYCHOLOGUE
    // ============================

    #[Route('/dashboard/psychologue', name: 'app_dashboard_psy')]
    #[IsGranted('ROLE_PSYCHOLOGUE')]
    public function psychologueDashboard(
        EntityManagerInterface $em,
        RendezVousRepository $rdvRepository,
        ConsultationRepository $consultationRepository
    ): Response {
        /** @var User $user */
        $user = $this->getUser();

        // Statistiques (à adapter selon vos repositories)
        $stats = [
            'rdv_aujourdhui' => $rdvRepository->count(['psy' => $user]), // Ajouter filtre date si nécessaire
            'patients_actifs' => count($user->getConsultationsPsy()),
            'rdv_en_attente' => $rdvRepository->count(['psy' => $user, 'statut' => 'demande']), // Exemple
            'today_appointments' => 5, // Optionnel, issu de votre version
            'active_patients' => 23,
            'weekly_appointments' => 18,
            'fill_rate' => 75,
        ];

        $prochainsRdv = $rdvRepository->findByPsyOrderedByDate($user, 'ASC', 5); // À adapter

        return $this->render('dashboard/psychologue.html.twig', [
            'user' => $user,
            'stats' => $stats,
            'prochains_rdv' => $prochainsRdv,
        ]);
    }

    #[Route('/psychologue/consultations', name: 'app_psy_consultations')]
    #[IsGranted('ROLE_PSYCHOLOGUE')]
    public function consultations(ConsultationRepository $consultationRepository): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        $consultations = $consultationRepository->findBy(
            ['psy' => $user],
            ['date_consultation' => 'DESC']
        );

        return $this->render('dashboard/psychologue/consultations.html.twig', [
            'user' => $user,
            'consultations' => $consultations,
        ]);
    }

    #[Route('/psychologue/patients', name: 'app_psy_patients')]
    #[IsGranted('ROLE_PSYCHOLOGUE')]
    public function patients(): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        $consultations = $user->getConsultationsPsy();
        $patients = [];

        foreach ($consultations as $consultation) {
            $etudiant = $consultation->getEtudiant();
            if ($etudiant && !isset($patients[$etudiant->getUserId()])) {
                $patients[$etudiant->getUserId()] = $etudiant;
            }
        }

        return $this->render('dashboard/psychologue/patients.html.twig', [
            'user' => $user,
            'patients' => $patients,
        ]);
    }

    #[Route('/psychologue/rendez-vous', name: 'app_psy_rendez_vous')]
    #[IsGranted('ROLE_PSYCHOLOGUE')]
    public function rendezVous(RendezVousRepository $rendezVousRepository): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        $rendez_vous_list = $rendezVousRepository->findByPsyOrderedByDate($user, 'DESC'); // À adapter

        // Filtrer par statut
        $demandes = array_filter($rendez_vous_list, fn($rdv) => $rdv->getStatut() === 'demande');
        $confirmes = array_filter($rendez_vous_list, fn($rdv) => $rdv->getStatut() === 'confirme');
        $en_cours = array_filter($rendez_vous_list, fn($rdv) => $rdv->getStatut() === 'en-cours');
        $termines = array_filter($rendez_vous_list, fn($rdv) => $rdv->getStatut() === 'terminé');
        $annules = array_filter($rendez_vous_list, fn($rdv) => $rdv->getStatut() === 'annulé');

        return $this->render('psy/rendezvous/index.html.twig', [
            'user' => $user,
            'rendez_vous_list' => $rendez_vous_list,
            'demandes' => $demandes,
            'confirmes' => $confirmes,
            'en_cours' => $en_cours,
            'termines' => $termines,
            'annules' => $annules,
        ]);
    }

    #[Route('/psychologue/profil', name: 'app_psy_profil')]
    #[IsGranted('ROLE_PSYCHOLOGUE')]
    public function profil(): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        $profil = $user->getProfil();

        if (!$profil) {
            $profil = new Profil();
            $profil->setUser($user);
        }

        return $this->render('profile/indexP.html.twig', [
            'user' => $user,
            'profil' => $profil,
        ]);
    }

    // ============================
    // DASHBOARD RESPONSABLE
    // ============================

    #[Route('/dashboard/responsable', name: 'resp_dashboard')]
    #[IsGranted('ROLE_RESPONSABLE_ETUDIANT')]
    public function responsableDashboard(EntityManagerInterface $em): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        // Utilisation des repositories avec méthodes spécifiques si disponibles
        $userRepo = $em->getRepository(User::class);
        $rdvRepo = $em->getRepository(RendezVous::class);

        $stats = [
            'total_students' => $userRepo->count(['role' => 'Etudiant']),
            'active_psychologists' => method_exists($userRepo, 'countPsychologuesActifs') ? $userRepo->countPsychologuesActifs() : $userRepo->count(['role' => 'Psychologue']),
            'monthly_appointments' => method_exists($rdvRepo, 'countRendezVousMois') ? $rdvRepo->countRendezVousMois() : 0,
            'satisfaction_rate' => $this->calculateSatisfactionRate($em),
            'new_students' => method_exists($userRepo, 'countNouveauxEtudiants') ? $userRepo->countNouveauxEtudiants(30) : 0,
            'student_growth' => $this->calculateStudentGrowth($em),
            'usage_rate' => $this->calculateUsageRate($em),
            'in_person_appointments' => method_exists($rdvRepo, 'countRendezVousPresentiel') ? $rdvRepo->countRendezVousPresentiel() : 0,
            'online_appointments' => method_exists($rdvRepo, 'countRendezVousEnLigne') ? $rdvRepo->countRendezVousEnLigne() : 0,
            'in_person_percentage' => $this->calculatePercentagePresentiel($em),
            'online_percentage' => $this->calculatePercentageEnLigne($em),
        ];

        $evenementRepo = $em->getRepository(Evenement::class);
        $participationRepo = $em->getRepository(Participation::class);

        $qbEvenements = $evenementRepo->createQueryBuilder('e')
            ->andWhere('e.organisateur = :organisateur')
            ->setParameter('organisateur', $user);

        $totalMesEvenements = (int) (clone $qbEvenements)
            ->select('COUNT(e.evenement_id)')
            ->getQuery()
            ->getSingleScalarResult();

        $countMesEvenementsByStatut = static function (string $statut) use ($qbEvenements): int {
            return (int) (clone $qbEvenements)
                ->select('COUNT(e.evenement_id)')
                ->andWhere('e.statut = :statut')
                ->setParameter('statut', $statut)
                ->getQuery()
                ->getSingleScalarResult();
        };

        $qbParticipations = $participationRepo->createQueryBuilder('p')
            ->leftJoin('p.evenement', 'e')
            ->andWhere('e.organisateur = :organisateur')
            ->setParameter('organisateur', $user);

        $totalParticipations = (int) (clone $qbParticipations)
            ->select('COUNT(p.participation_id)')
            ->getQuery()
            ->getSingleScalarResult();

        $participationsConfirmees = (int) (clone $qbParticipations)
            ->select('COUNT(p.participation_id)')
            ->andWhere('p.statut = :statut_confirme')
            ->setParameter('statut_confirme', 'confirme')
            ->getQuery()
            ->getSingleScalarResult();

        $capaciteTotale = (int) (clone $qbEvenements)
            ->select('COALESCE(SUM(e.capaciteMax), 0)')
            ->getQuery()
            ->getSingleScalarResult();

        $tauxRemplissage = $capaciteTotale > 0 ? round(($participationsConfirmees / $capaciteTotale) * 100, 1) : 0.0;

        $countParticipationsByStatut = static function (string $statut) use ($qbParticipations): int {
            return (int) (clone $qbParticipations)
                ->select('COUNT(p.participation_id)')
                ->andWhere('p.statut = :statut')
                ->setParameter('statut', $statut)
                ->getQuery()
                ->getSingleScalarResult();
        };

        $totalFeedbacks = (int) (clone $qbParticipations)
            ->select('COUNT(p.participation_id)')
            ->andWhere("p.note_satisfaction IS NOT NULL OR (p.feedback_commentaire IS NOT NULL AND p.feedback_commentaire <> '')")
            ->getQuery()
            ->getSingleScalarResult();

        $moyenneSatisfaction = (clone $qbParticipations)
            ->select('AVG(p.note_satisfaction)')
            ->andWhere('p.note_satisfaction IS NOT NULL')
            ->getQuery()
            ->getSingleScalarResult();

        $prochainsEvenements = (clone $qbEvenements)
            ->addSelect('e')
            ->andWhere('e.dateDebut >= :now')
            ->setParameter('now', new \DateTimeImmutable())
            ->orderBy('e.dateDebut', 'ASC')
            ->setMaxResults(5)
            ->getQuery()
            ->getResult();

        $derniersFeedbacks = (clone $qbParticipations)
            ->leftJoin('p.etudiant', 'u')
            ->addSelect('e', 'u')
            ->andWhere("p.note_satisfaction IS NOT NULL OR (p.feedback_commentaire IS NOT NULL AND p.feedback_commentaire <> '')")
            ->orderBy('p.feedback_at', 'DESC')
            ->setMaxResults(5)
            ->getQuery()
            ->getResult();

        $evenements = [
            'total_evenements' => $totalMesEvenements,
            'evenements_a_venir' => $countMesEvenementsByStatut('a_venir'),
            'evenements_en_cours' => $countMesEvenementsByStatut('en_cours'),
            'evenements_termines' => $countMesEvenementsByStatut('termine'),
            'evenements_annules' => $countMesEvenementsByStatut('annule'),
            'total_participations' => $totalParticipations,
            'participations_en_attente' => $countParticipationsByStatut('attente'),
            'participations_confirmees' => $countParticipationsByStatut('confirme'),
            'participations_annulees' => $countParticipationsByStatut('annule'),
            'taux_remplissage' => $tauxRemplissage,
            'total_feedbacks' => $totalFeedbacks,
            'moyenne_satisfaction' => $moyenneSatisfaction !== null ? round((float) $moyenneSatisfaction, 2) : null,
            'prochains_evenements' => $prochainsEvenements,
            'derniers_feedbacks' => $derniersFeedbacks,
        ];

        return $this->render('dashboard/responsable.html.twig', [
            'user' => $user,
            'stats' => $stats,
            'evenements' => $evenements,
        ]);
    }

    // ============================
    // DASHBOARD ADMIN
    // ============================

    #[Route('/dashboard/admin', name: 'admin_dashboard')]
    #[IsGranted('ROLE_ADMIN')]
    public function adminDashboard(
        EntityManagerInterface $entityManager,
        QuestionnaireRepository $questionnaireRepository,
        QuestionRepository $questionRepository,
        ReponseQuestionnaireRepository $reponseRepository,
        CategorieMeditationRepository $categorieRepo,
        SeanceMeditationRepository $seanceRepo
    ): Response {
        /** @var User $user */
        $user = $this->getUser();

        $userRepo = $entityManager->getRepository(User::class);

        // Statistiques utilisateurs
        $statsUtilisateurs = [
            'total_utilisateurs' => $userRepo->count([]),
            'utilisateurs_en_attente' => $userRepo->count(['statut' => 'en_attente']),
            'utilisateurs_actifs' => $userRepo->count(['isActive' => true]),
            'etudiants' => $userRepo->count(['role' => 'Etudiant']),
            'psychologues' => $userRepo->count(['role' => 'Psychologue']),
            'responsables' => $userRepo->count(['role' => 'Responsable Etudiant']),
        ];

        // Statistiques questionnaires
        $tousQuestionnaires = $questionnaireRepository->findAll();

        $questionnairesIncomplets = array_values(array_filter(
            $tousQuestionnaires,
            fn($q) => count($q->getQuestions()) < $q->getNbreQuestions()
        ));

        $derniersQuestionnaires = array_slice(array_reverse($tousQuestionnaires), 0, 5);

        $totalQuestionnaires = count($tousQuestionnaires);
        $totalQuestions = count($questionRepository->findAll());
        $totalReponses = count($reponseRepository->findAll());

        // Statistiques traitements
        $traitementRepo = $entityManager->getRepository(Traitement::class);
        $suiviRepo = $entityManager->getRepository(SuiviTraitement::class);

        $tousTraitements = $traitementRepo->findAll();
        $tousSuivis = $suiviRepo->findAll();

        $statsTraitements = [
            'total_traitements' => count($tousTraitements),
            'traitements_actifs' => count(array_filter($tousTraitements, fn($t) => $t->getStatut() === 'actif')),
            'traitements_par_categorie' => $this->getTraitementsParCategorie($tousTraitements),
            'traitements_ce_mois' => $this->getTraitementsCeMois($traitementRepo),
        ];

        $statsSuivis = [
            'total_suivis' => count($tousSuivis),
            'suivis_valides' => count(array_filter($tousSuivis, fn($s) => $s->isValide())),
            'suivis_en_attente' => count(array_filter($tousSuivis, fn($s) => !$s->isValide())),
            'suivis_effectues' => count(array_filter($tousSuivis, fn($s) => $s->isEffectue())),
            'suivis_ce_mois' => $this->getSuivisCeMois($suiviRepo),
            'suivis_par_psychologue' => $this->getSuivisParPsychologue($tousSuivis),
        ];

        // Nouveautés pour la méditation
    $totalCategories = $categorieRepo->count([]);
    $totalSeances = $seanceRepo->count([]);
    $seancesActives = $seanceRepo->count(['isActif' => true]);
    $dureeTotale = $seanceRepo->createQueryBuilder('s')
        ->select('SUM(s.duree)')
        ->getQuery()
        ->getSingleScalarResult() ?? 0;
    $dureeTotaleFormatee = sprintf('%dh %dm', floor($dureeTotale / 3600), ($dureeTotale % 3600) / 60);

    // Répartition par type
    $statsType = [
        'audio' => $seanceRepo->count(['typeFichier' => 'audio']),
        'video' => $seanceRepo->count(['typeFichier' => 'video']),
    ];
    $totalSeances = $statsType['audio'] + $statsType['video'];
    $statsType['pourcentage_audio'] = $totalSeances ? round($statsType['audio'] / $totalSeances * 100) : 0;
    $statsType['pourcentage_video'] = 100 - $statsType['pourcentage_audio'];

    // Répartition par niveau
    $statsNiveau = [
        'debutant' => $seanceRepo->count(['niveau' => 'débutant']),
        'intermediaire' => $seanceRepo->count(['niveau' => 'intermédiaire']),
        'avance' => $seanceRepo->count(['niveau' => 'avancé']),
    ];
    $totalSeances = array_sum($statsNiveau);
    $statsNiveau['pourcentage_debutant'] = $totalSeances ? round($statsNiveau['debutant'] / $totalSeances * 100) : 0;
    $statsNiveau['pourcentage_intermediaire'] = $totalSeances ? round($statsNiveau['intermediaire'] / $totalSeances * 100) : 0;
    $statsNiveau['pourcentage_avance'] = $totalSeances ? round($statsNiveau['avance'] / $totalSeances * 100) : 0;

    // Dernières séances
    $dernieresSeances = $seanceRepo->findBy([], ['createdAt' => 'DESC'], 5);

        // Graphiques (30 derniers jours)
        $graphiqueLabels = [];
        $graphiqueData = [];

        for ($i = 29; $i >= 0; $i--) {
            $date = new \DateTime("-$i days");
            $graphiqueLabels[] = $date->format('d/m');

            $count = $reponseRepository->createQueryBuilder('r')
                ->select('COUNT(r)')
                ->where('r.created_at >= :start')
                ->andWhere('r.created_at < :end')
                ->setParameter('start', $date->format('Y-m-d 00:00:00'))
                ->setParameter('end', $date->format('Y-m-d 23:59:59'))
                ->getQuery()
                ->getSingleScalarResult();

            $graphiqueData[] = $count;
        }

        // Répartition par type de questionnaire
        $repartitionTypes = $questionnaireRepository->createQueryBuilder('q')
            ->select('q.type, COUNT(q.questionnaire_id) as nombre')
            ->groupBy('q.type')
            ->getQuery()
            ->getResult();

        $typesFormatted = [];
        foreach ($repartitionTypes as $type) {
            $typesFormatted[] = [
                'label' => $type['type'] ?? 'Non défini',
                'nombre_questionnaires' => $type['nombre'],
                'color' => $this->getColorForType($type['type'])
            ];
        }

        // Statistiques globales
        $statsGlobales = [
            'total_questionnaires' => $totalQuestionnaires,
            'total_questions' => $totalQuestions,
            'total_reponses' => $totalReponses,
            'total_etudiants' => $statsUtilisateurs['etudiants'],
            'questionnaires_complets' => $this->countQuestionnairesComplets($tousQuestionnaires),
            'reponses_ce_mois' => $this->countReponsesThisMonth($reponseRepository),
            'reponses_severes' => $this->countReponsesSeveres($reponseRepository),
            'evolution_pourcentage' => $this->calculateEvolutionPercentage($reponseRepository),
            'repartition_types' => $typesFormatted,
            'graphique_labels' => $graphiqueLabels,
            'graphique_data' => $graphiqueData,
        ];

        $utilisateursRecents = $userRepo->findBy([], ['created_at' => 'DESC'], 10);

        return $this->render('admin/dashboard/index.html.twig', [
            'user' => $user,
            'stats_utilisateurs' => $statsUtilisateurs,
            'stats_globales' => $statsGlobales,
            'stats_traitements' => $statsTraitements,
            'stats_suivis' => $statsSuivis,
            'questionnaires_count' => $totalQuestionnaires,
            'questions_count' => $totalQuestions,
            'reponses_count' => $totalReponses,
            'traitements_count' => $statsTraitements['total_traitements'],
            'suivis_count' => $statsSuivis['total_suivis'],
            'derniers_questionnaires' => $derniersQuestionnaires,
            'questionnaires_incomplets' => $questionnairesIncomplets,
            'utilisateurs_recents' => $utilisateursRecents,
            'meditation' => [
            'total_categories' => $totalCategories,
            'total_seances' => $totalSeances,
            'seances_actives' => $seancesActives,
            'duree_totale_formatee' => $dureeTotaleFormatee,
            'stats_type' => $statsType,
            'stats_niveau' => $statsNiveau,
            'dernieres_seances' => $dernieresSeances,
        ],
        ]);
    }

    #[Route('/admin/stats', name: 'admin_global_stats')]
    #[IsGranted('ROLE_ADMIN')]
    public function adminGlobalStats(
        QuestionnaireRepository $questionnaireRepository,
        ReponseQuestionnaireRepository $reponseRepository
    ): Response {
        return $this->render('admin/stats/index.html.twig', [
            'stats_questionnaires' => $questionnaireRepository->getDetailedStats(),
            'stats_reponses' => $reponseRepository->getDetailedStats(),
            'evolution_mensuelle' => $reponseRepository->getMonthlyEvolution(),
        ]);
    }

    #[Route('/admin/users', name: 'admin_users')]
    #[IsGranted('ROLE_ADMIN')]
    public function adminUsers(EntityManagerInterface $entityManager): Response
    {
        $users = $entityManager->getRepository(User::class)->findAll();

        return $this->render('admin/users/users.html.twig', [
            'users' => $users,
        ]);
    }

    #[Route('/admin/users/pending', name: 'admin_users_pending')]
    #[IsGranted('ROLE_ADMIN')]
    public function adminUsersPending(EntityManagerInterface $entityManager): Response
    {
        $pendingUsers = $entityManager->getRepository(User::class)->findBy([
            'statut' => 'en_attente'
        ], ['created_at' => 'DESC']);

        return $this->render('admin/users/pending_users.html.twig', [
            'pending_users' => $pendingUsers,
        ]);
    }

    #[Route('/admin/user/{id}/activate', name: 'admin_user_activate')]
    #[IsGranted('ROLE_ADMIN')]
    public function activateUser(User $user, EntityManagerInterface $entityManager): Response
    {
        $user->setStatut('actif');
        $user->setIsActive(true);
        $entityManager->flush();

        $this->addFlash('success', 'Utilisateur activé avec succès.');

        return $this->redirectToRoute('admin_users_pending');
    }

    #[Route('/admin/user/{id}/reject', name: 'admin_user_reject')]
    #[IsGranted('ROLE_ADMIN')]
    public function rejectUser(User $user, EntityManagerInterface $entityManager): Response
    {
        $user->setStatut('rejeté');
        $user->setIsActive(false);
        $entityManager->flush();

        $this->addFlash('warning', 'Utilisateur rejeté.');

        return $this->redirectToRoute('admin_users_pending');
    }

    #[Route('/admin/user/{id}/delete', name: 'admin_user_delete')]
    #[IsGranted('ROLE_ADMIN')]
    public function deleteUser(User $user, EntityManagerInterface $entityManager): Response
    {
        $entityManager->remove($user);
        $entityManager->flush();

        $this->addFlash('danger', 'Utilisateur supprimé avec succès.');

        return $this->redirectToRoute('admin_users');
    }

    #[Route('/admin/user/{id}/toggle-active', name: 'admin_user_toggle_active')]
    #[IsGranted('ROLE_ADMIN')]
    public function toggleUserActive(User $user, EntityManagerInterface $entityManager): Response
    {
        $user->setIsActive(!$user->isIsActive());
        $entityManager->flush();

        $status = $user->isIsActive() ? 'activé' : 'désactivé';
        $this->addFlash('info', "Utilisateur $status.");

        return $this->redirectToRoute('admin_users');
    }

    #[Route('/admin/user/{id}/edit', name: 'admin_user_edit')]
    #[IsGranted('ROLE_ADMIN')]
    public function editUser(User $user, Request $request, EntityManagerInterface $entityManager): Response
    {
        if ($request->isMethod('POST')) {
            $nom = $request->request->get('nom');
            $prenom = $request->request->get('prenom');
            $email = $request->request->get('email');
            $statut = $request->request->get('statut');

            if ($nom && $prenom && $email) {
                $user->setNom($nom);
                $user->setPrenom($prenom);
                $user->setEmail($email);
                $user->setStatut($statut);

                $entityManager->flush();

                $this->addFlash('success', 'Utilisateur mis à jour avec succès.');
                return $this->redirectToRoute('admin_users');
            } else {
                $this->addFlash('error', 'Tous les champs sont obligatoires.');
            }
        }

        return $this->render('admin/users/edit_user.html.twig', [
            'user' => $user,
        ]);
    }

    // ============================
    // MÉTHODES PRIVÉES DE CALCUL STATISTIQUE
    // ============================

    private function calculateSatisfactionRate(EntityManagerInterface $em): float
    {
        // À implémenter selon votre logique métier
        return 88.5;
    }

    private function calculateStudentGrowth(EntityManagerInterface $em): float
    {
        return 8.3;
    }

    private function calculateUsageRate(EntityManagerInterface $em): float
    {
        return 65.0;
    }

    private function calculatePercentagePresentiel(EntityManagerInterface $em): float
    {
        return 67.0;
    }

    private function calculatePercentageEnLigne(EntityManagerInterface $em): float
    {
        return 33.0;
    }

    private function countQuestionnairesComplets(array $questionnaires): int
    {
        return count(array_filter(
            $questionnaires,
            fn($q) => count($q->getQuestions()) >= $q->getNbreQuestions()
        ));
    }

    private function countReponsesThisMonth(ReponseQuestionnaireRepository $reponseRepository): int
    {
        $start = (new \DateTime())->modify('first day of this month')->setTime(0, 0, 0);
        $end = (new \DateTime())->modify('last day of this month')->setTime(23, 59, 59);

        return $reponseRepository->createQueryBuilder('r')
            ->select('COUNT(r)')
            ->where('r.created_at BETWEEN :start AND :end')
            ->setParameter('start', $start)
            ->setParameter('end', $end)
            ->getQuery()
            ->getSingleScalarResult();
    }

    private function countReponsesSeveres(ReponseQuestionnaireRepository $reponseRepository): int
    {
        return $reponseRepository->createQueryBuilder('r')
            ->select('COUNT(r)')
            ->where('r.niveau = :niveau')
            ->setParameter('niveau', 'sévère')
            ->getQuery()
            ->getSingleScalarResult();
    }

    private function calculateEvolutionPercentage(ReponseQuestionnaireRepository $reponseRepository): float
    {
        $moisActuel = $this->countReponsesThisMonth($reponseRepository);

        $moisDernier = $reponseRepository->createQueryBuilder('r')
            ->select('COUNT(r)')
            ->where('r.created_at BETWEEN :start AND :end')
            ->setParameter('start', (new \DateTime())->modify('first day of last month')->setTime(0, 0, 0))
            ->setParameter('end', (new \DateTime())->modify('last day of last month')->setTime(23, 59, 59))
            ->getQuery()
            ->getSingleScalarResult();

        if ($moisDernier > 0) {
            return round((($moisActuel - $moisDernier) / $moisDernier) * 100, 1);
        }

        return 0;
    }

    private function getColorForType(?string $type): string
    {
        $colors = [
            'ANXETE' => 'danger',
            'DEPRESSION' => 'warning',
            'STRESS' => 'info',
            'BIEN_ETRE' => 'success',
            'ESTIME_SOI' => 'primary',
        ];

        return $colors[$type] ?? 'secondary';
    }

    // Méthodes pour les statistiques de traitements (de votre version)
    private function getTraitementsParCategorie(array $traitements): array
    {
        $categories = [];
        foreach ($traitements as $traitement) {
            $categorie = $traitement->getCategorie() ?? 'Non défini';
            if (!isset($categories[$categorie])) {
                $categories[$categorie] = 0;
            }
            $categories[$categorie]++;
        }
        return $categories;
    }

    private function getTraitementsCeMois($traitementRepo): int
    {
        $start = (new \DateTime())->modify('first day of this month')->setTime(0, 0, 0);
        $end = (new \DateTime())->modify('last day of this month')->setTime(23, 59, 59);

        return $traitementRepo->createQueryBuilder('t')
            ->select('COUNT(t)')
            ->where('t.created_at BETWEEN :start AND :end')
            ->setParameter('start', $start)
            ->setParameter('end', $end)
            ->getQuery()
            ->getSingleScalarResult() ?? 0;
    }

    private function getSuivisCeMois($suiviRepo): int
    {
        $start = (new \DateTime())->modify('first day of this month')->setTime(0, 0, 0);
        $end = (new \DateTime())->modify('last day of this month')->setTime(23, 59, 59);

        return $suiviRepo->createQueryBuilder('s')
            ->select('COUNT(s)')
            ->where('s.created_at BETWEEN :start AND :end')
            ->setParameter('start', $start)
            ->setParameter('end', $end)
            ->getQuery()
            ->getSingleScalarResult() ?? 0;
    }

    private function getSuivisParPsychologue(array $suivis): array
    {
        $psychologues = [];
        foreach ($suivis as $suivi) {
            if ($suivi->getPsychologue()) {
                $nom = $suivi->getPsychologue()->getPrenom() . ' ' . $suivi->getPsychologue()->getNom();
                if (!isset($psychologues[$nom])) {
                    $psychologues[$nom] = 0;
                }
                $psychologues[$nom]++;
            }
        }
        return $psychologues;
    }
}