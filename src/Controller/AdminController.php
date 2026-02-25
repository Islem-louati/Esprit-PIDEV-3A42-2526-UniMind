<?php
// src/Controller/AdminController.php

namespace App\Controller;

use App\Entity\User;
use App\Entity\Profil;
use App\Entity\Traitement;
use App\Entity\SuiviTraitement;
use App\Enum\RoleType;
use App\Form\UserType;
use App\Form\UserFilterType;
use App\Repository\QuestionnaireRepository;
use App\Repository\QuestionRepository;
use App\Repository\ReponseQuestionnaireRepository;
use App\Repository\CategorieMeditationRepository;
use App\Repository\SeanceMeditationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use App\Service\ExportService;

#[IsGranted('ROLE_ADMIN')]
#[Route('/admin')]
class AdminController extends AbstractController
{
    #[Route('/dashboard', name: 'admin_dashboard')]
    public function dashboard(
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
            'etudiants' => $userRepo->count(['role' => RoleType::ETUDIANT]),
            'psychologues' => $userRepo->count(['role' => RoleType::PSYCHOLOGUE]),
            'responsables' => $userRepo->count(['role' => RoleType::RESPONSABLE_ETUDIANT]),
        ];

        // Statistiques questionnaires
        $tousQuestionnaires = $questionnaireRepository->findAll();

        $questionnairesIncomplets = array_values(array_filter(
            $tousQuestionnaires,
            fn($q) => count($q->getQuestions()) < $q->getNbreQuestions()
        ));

        $derniersQuestionnaires = array_slice(
            array_reverse($tousQuestionnaires),
            0,
            5
        );

        $totalQuestionnaires = count($tousQuestionnaires);
        $totalQuestions = count($questionRepository->findAll());
        $totalReponses = count($reponseRepository->findAll());

        // Statistiques traitements
        $traitementRepo = $entityManager->getRepository(Traitement::class);
        $suiviRepo = $entityManager->getRepository(SuiviTraitement::class);
        
        $tousTraitements = $traitementRepo->findAll();
        $tousSuivis = $suiviRepo->findAll();
        
        // Statistiques traitements
        $statsTraitements = [
            'total_traitements' => count($tousTraitements),
            'traitements_actifs' => count(array_filter($tousTraitements, fn($t) => $t->getStatut() === 'actif')),
            'traitements_par_categorie' => $this->getTraitementsParCategorie($tousTraitements),
            'traitements_ce_mois' => $this->getTraitementsCeMois($traitementRepo),
        ];
        
        // Statistiques suivis
        $statsSuivis = [
            'total_suivis' => count($tousSuivis),
            'suivis_valides' => count(array_filter($tousSuivis, fn($s) => $s->isValide())),
            'suivis_en_attente' => count(array_filter($tousSuivis, fn($s) => !$s->isValide())),
            'suivis_effectues' => count(array_filter($tousSuivis, fn($s) => $s->isEffectue())),
            'suivis_ce_mois' => $this->getSuivisCeMois($suiviRepo),
            'suivis_par_psychologue' => $this->getSuivisParPsychologue($tousSuivis),
        ];

        // Générer les données pour les graphiques
        $graphiqueLabels = [];
        $graphiqueData = [];
        
        // Récupérer les données des 30 derniers jours
        for ($i = 29; $i >= 0; $i--) {
            $date = new \DateTime("-$i days");
            $graphiqueLabels[] = $date->format('d/m');
            
            // Compter les réponses pour cette date
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

        // Récupérer la répartition par type de questionnaire
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

        // Récupérer les utilisateurs récents
        $utilisateursRecents = $userRepo->findBy([], ['created_at' => 'DESC'], 10);

        // Nouveautés pour la méditation
    $totalCategories = $categorieRepo->count([]);
    $totalSeances = $seanceRepo->count([]);
    $seancesActives = $seanceRepo->count(['is_active' => true]);
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
    $dernieresSeances = $seanceRepo->findBy([], ['created_at' => 'DESC'], 5);

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

        // Données pour graphique évolution mensuelle des séances
$evolutionLabels = [];
$evolutionData = [];
$now = new \DateTime();
for ($i = 5; $i >= 0; $i--) {
    $date = (clone $now)->modify("-$i months");
    $start = \DateTime::createFromFormat('Y-m-d H:i:s', $date->format('Y-m-01 00:00:00'));
    $end = (clone $start)->modify('last day of this month')->setTime(23, 59, 59);
    
    $count = $seanceRepo->createQueryBuilder('s')
        ->select('COUNT(s.seance_id)')
        ->where('s.created_at BETWEEN :start AND :end')
        ->setParameter('start', $start)
        ->setParameter('end', $end)
        ->getQuery()
        ->getSingleScalarResult();
    
    $evolutionLabels[] = $date->format('M'); // 'Jan', 'Fév', etc.
    $evolutionData[] = (int) $count;
}

// Top 5 catégories par nombre de séances
$topCategories = $categorieRepo->createQueryBuilder('c')
    ->select('c.nom, COUNT(s.seance_id) as nbSeances')
    ->leftJoin('c.seanceMeditations', 's')
    ->groupBy('c.categorie_id')
    ->orderBy('nbSeances', 'DESC')
    ->setMaxResults(5)
    ->getQuery()
    ->getResult();

$topCategoriesLabels = array_column($topCategories, 'nom');
$topCategoriesData = array_column($topCategories, 'nbSeances');

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
    'evolution_labels' => $evolutionLabels,
    'evolution_data' => $evolutionData,
    'top_categories_labels' => $topCategoriesLabels,
    'top_categories_data' => $topCategoriesData,
],
        ]);
    }

    #[Route('/utilisateurs', name: 'admin_users')]
    public function users(
        EntityManagerInterface $entityManager,
        Request $request
    ): Response {
        // Créer le formulaire de filtre
        $filterForm = $this->createForm(UserFilterType::class);
        $filterForm->handleRequest($request);
        
        // Construire la requête
        $queryBuilder = $entityManager->getRepository(User::class)
            ->createQueryBuilder('u')
            ->orderBy('u.created_at', 'DESC');
        
        // Appliquer les filtres
        if ($filterForm->isSubmitted() && $filterForm->isValid()) {
            $data = $filterForm->getData();
            
            if ($data['role']) {
                $queryBuilder->andWhere('u.role = :role')
                    ->setParameter('role', $data['role']);
            }
            
            if ($data['statut']) {
                $queryBuilder->andWhere('u.statut = :statut')
                    ->setParameter('statut', $data['statut']);
            }
            
            if ($data['search']) {
                $queryBuilder->andWhere(
                    $queryBuilder->expr()->orX(
                        $queryBuilder->expr()->like('u.nom', ':search'),
                        $queryBuilder->expr()->like('u.prenom', ':search'),
                        $queryBuilder->expr()->like('u.email', ':search')
                    )
                )
                ->setParameter('search', '%' . $data['search'] . '%');
            }
        }
        
        // Sauvegarder les filtres dans la session pour les exports
        $session = $request->getSession();
        if ($filterForm->isSubmitted() && $filterForm->isValid()) {
            $session->set('user_filters', $filterForm->getData());
        } elseif (!$filterForm->isSubmitted()) {
            // Réinitialiser les filtres si pas de soumission
            $session->remove('user_filters');
        }
        
        // PAGINATION MANUELLE
        $page = $request->query->getInt('page', 1);
        $limit = 10;
        $offset = ($page - 1) * $limit;
        
        // Comptage total
        $countQueryBuilder = clone $queryBuilder;
        $total = $countQueryBuilder->select('COUNT(u.user_id)')
            ->getQuery()
            ->getSingleScalarResult();
        
        // Récupérer les résultats paginés
        $users = $queryBuilder
            ->setFirstResult($offset)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
        
        // Calculer le nombre total de pages
        $pages = ceil($total / $limit);

        return $this->render('admin/users/index.html.twig', [
            'users' => $users,
            'total' => $total,
            'page' => $page,
            'pages' => $pages,
            'limit' => $limit,
            'filterForm' => $filterForm->createView(),
        ]);
    }

    #[Route('/utilisateurs/demandes', name: 'admin_pending_users')]
    public function pendingUsers(EntityManagerInterface $entityManager): Response
    {
        $pendingUsers = $entityManager->getRepository(User::class)
            ->findBy(
                ['statut' => 'en_attente'],
                ['created_at' => 'DESC']
            );

        return $this->render('admin/users/pending_users.html.twig', [
            'pending_users' => $pendingUsers,
        ]);
    }

    #[Route('/utilisateurs/{id}/valider', name: 'admin_validate_user', methods: ['POST'])]
    public function validateUser(int $id, EntityManagerInterface $entityManager): Response
    {
        $user = $entityManager->getRepository(User::class)->find($id);

        if (!$user) {
            throw $this->createNotFoundException('Utilisateur non trouvé');
        }

        $user->setStatut('actif');
        $user->setIsActive(true);
        $entityManager->flush();

        $this->addFlash('success', 'Utilisateur validé avec succès !');
        return $this->redirectToRoute('admin_pending_users');
    }

    #[Route('/utilisateurs/{id}/rejeter', name: 'admin_reject_user', methods: ['POST'])]
    public function rejectUser(int $id, EntityManagerInterface $entityManager): Response
    {
        $user = $entityManager->getRepository(User::class)->find($id);

        if (!$user) {
            throw $this->createNotFoundException('Utilisateur non trouvé');
        }

        $user->setStatut('rejeté');
        $user->setIsActive(false);
        $entityManager->flush();

        $this->addFlash('success', 'Demande d\'inscription rejetée.');
        return $this->redirectToRoute('admin_pending_users');
    }

    #[Route('/utilisateurs/{id}/activer', name: 'admin_activate_user', methods: ['POST'])]
    public function activateUser(int $id, EntityManagerInterface $entityManager): Response
    {
        $user = $entityManager->getRepository(User::class)->find($id);

        if (!$user) {
            throw $this->createNotFoundException('Utilisateur non trouvé');
        }

        $user->setIsActive(true);
        $user->setStatut('actif');
        $entityManager->flush();

        $this->addFlash('success', 'Utilisateur activé avec succès !');
        return $this->redirectToRoute('admin_users');
    }

    #[Route('/utilisateurs/{id}/desactiver', name: 'admin_deactivate_user', methods: ['POST'])]
    public function deactivateUser(int $id, EntityManagerInterface $entityManager): Response
    {
        $user = $entityManager->getRepository(User::class)->find($id);

        if (!$user) {
            throw $this->createNotFoundException('Utilisateur non trouvé');
        }

        $user->setIsActive(false);
        $user->setStatut('inactif');
        $entityManager->flush();

        $this->addFlash('success', 'Utilisateur désactivé avec succès !');
        return $this->redirectToRoute('admin_users');
    }

    #[Route('/utilisateurs/{id}/modifier', name: 'admin_edit_user')]
    public function editUser(int $id, Request $request, EntityManagerInterface $entityManager): Response
    {
        $user = $entityManager->getRepository(User::class)->find($id);

        if (!$user) {
            throw $this->createNotFoundException('Utilisateur non trouvé');
        }

        $form = $this->createForm(UserType::class, $user, [
            'is_creation' => false // Ne pas afficher le champ mot de passe pour l'édition
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
            $this->addFlash('success', 'Utilisateur modifié avec succès !');
            return $this->redirectToRoute('admin_users');
        }

        return $this->render('admin/users/edit_user.html.twig', [
            'user' => $user,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/utilisateurs/{id}/supprimer', name: 'admin_delete_user', methods: ['POST'])]
    public function deleteUser(int $id, EntityManagerInterface $entityManager): Response
    {
        $user = $entityManager->getRepository(User::class)->find($id);

        if (!$user) {
            throw $this->createNotFoundException('Utilisateur non trouvé');
        }

        // Vérifier que l'utilisateur n'est pas l'admin actuel
        if ($user === $this->getUser()) {
            $this->addFlash('error', 'Vous ne pouvez pas supprimer votre propre compte !');
            return $this->redirectToRoute('admin_users');
        }

        // Supprimer d'abord le profil associé
        if ($user->getProfil()) {
            $entityManager->remove($user->getProfil());
        }

        $entityManager->remove($user);
        $entityManager->flush();

        $this->addFlash('success', 'Utilisateur supprimé avec succès !');
        return $this->redirectToRoute('admin_users');
    }

    #[Route('/utilisateurs/{id}/profil', name: 'admin_view_profile')]
    public function viewProfile(int $id, EntityManagerInterface $entityManager): Response
    {
        $user = $entityManager->getRepository(User::class)->find($id);

        if (!$user) {
            throw $this->createNotFoundException('Utilisateur non trouvé');
        }
        
        $profil = $user->getProfil();

        return $this->render('profile/show.html.twig', [
            'user' => $user,
            'profil' => $profil,
        ]);
    }

    #[Route('/statistiques', name: 'admin_statistics')]
    public function statistics(EntityManagerInterface $entityManager): Response
    {
        $userRepository = $entityManager->getRepository(User::class);
        
        $userStats = $userRepository->createQueryBuilder('u')
            ->select('u.role, COUNT(u.user_id) as count')
            ->groupBy('u.role')
            ->getQuery()
            ->getResult();

        $statusStats = $userRepository->createQueryBuilder('u')
            ->select('u.statut, COUNT(u.user_id) as count')
            ->groupBy('u.statut')
            ->getQuery()
            ->getResult();

        // Récupérer toutes les dates
        $allUsers = $userRepository->findAll();
        $monthlyStats = [];
        $currentYear = date('Y');
        
        foreach ($allUsers as $user) {
            $createdAt = $user->getCreatedAt();
            if ($createdAt->format('Y') == $currentYear) {
                $month = (int) $createdAt->format('n'); // 1-12
                if (!isset($monthlyStats[$month])) {
                    $monthlyStats[$month] = 0;
                }
                $monthlyStats[$month]++;
            }
        }
        
        // Trier par mois
        ksort($monthlyStats);

        return $this->render('admin/statistics.html.twig', [
            'userStats' => $userStats,
            'statusStats' => $statusStats,
            'monthlyStats' => $monthlyStats,
            'currentYear' => $currentYear,
        ]);
    }

    #[Route('/utilisateurs/nouveau', name: 'admin_user_new')]
    public function newUser(Request $request, EntityManagerInterface $entityManager, UserPasswordHasherInterface $passwordHasher): Response
    {
        $user = new User();
        $form = $this->createForm(UserType::class, $user, [
            'is_creation' => true // Passer l'option pour afficher le champ mot de passe
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Hasher le mot de passe
            $plainPassword = $form->get('plainPassword')->getData();
            $hashedPassword = $passwordHasher->hashPassword($user, $plainPassword);
            $user->setPassword($hashedPassword);

            // Configurer les valeurs par défaut
            $user->setIsVerified(true);
            $user->setIsActive(true);
            $user->setCreatedAt(new \DateTime());
            
            $entityManager->persist($user);
            $entityManager->flush();

            $this->addFlash('success', 'Utilisateur créé avec succès !');
            return $this->redirectToRoute('admin_users');
        }

        return $this->render('admin/users/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/utilisateurs/{id}/verifier-email', name: 'admin_user_verify', methods: ['POST'])]
    public function verifyEmail(int $id, EntityManagerInterface $entityManager): Response
    {
        $user = $entityManager->getRepository(User::class)->find($id);

        if (!$user) {
            throw $this->createNotFoundException('Utilisateur non trouvé');
        }

        $user->setIsVerified(true);
        $entityManager->flush();

        $this->addFlash('success', 'Email vérifié avec succès !');
        return $this->redirectToRoute('admin_users');
    }

    #[Route('/utilisateurs/export/excel', name: 'admin_users_export_excel')]
    public function exportExcel(
        EntityManagerInterface $entityManager,
        ExportService $exportService,
        Request $request
    ): Response {
        // Récupérer tous les utilisateurs
        $users = $entityManager->getRepository(User::class)->findAll();
        
        return $exportService->exportToExcel($users);
    }

    #[Route('/utilisateurs/export/pdf', name: 'admin_users_export_pdf')]
    public function exportPdf(
        EntityManagerInterface $entityManager,
        ExportService $exportService,
        Request $request
    ): Response {
        // Récupérer tous les utilisateurs
        $users = $entityManager->getRepository(User::class)->findAll();
        
        return $exportService->exportToPdf($users);
    }

    #[Route('/utilisateurs/export/csv', name: 'admin_users_export_csv')]
    public function exportCsv(
        EntityManagerInterface $entityManager,
        ExportService $exportService,
        Request $request
    ): Response {
        // Récupérer tous les utilisateurs
        $users = $entityManager->getRepository(User::class)->findAll();
        
        return $exportService->exportToCsv($users);
    }

    // ============================
    // MÉTHODES PRIVÉES DE CALCUL STATISTIQUE
    // ============================

    private function countQuestionnairesComplets(array $questionnaires): int
    {
        return count(array_filter(
            $questionnaires,
            fn($q) => count($q->getQuestions()) >= $q->getNbreQuestions()
        ));
    }

    private function countReponsesThisMonth(ReponseQuestionnaireRepository $reponseRepository): int
    {
        $now = new \DateTime();
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

    // Méthodes pour les statistiques de traitements
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
            ->where('s.createdAt BETWEEN :start AND :end')
            ->setParameter('start', $start)
            ->setParameter('end', $end)
            ->getQuery()
            ->getSingleScalarResult() ?? 0;
    }

    private function getSuivisParPsychologue(array $suivis): array
    {
        $psychologues = [];
        foreach ($suivis as $suivi) {
            $traitement = $suivi->getTraitement();
            if ($traitement && $traitement->getPsychologue()) {
                $nom = $traitement->getPsychologue()->getPrenom() . ' ' . $traitement->getPsychologue()->getNom();
                if (!isset($psychologues[$nom])) {
                    $psychologues[$nom] = 0;
                }
                $psychologues[$nom]++;
            }
        }
        return $psychologues;
    }
}