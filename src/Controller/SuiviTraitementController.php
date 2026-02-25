<?php

namespace App\Controller;

use App\Entity\SuiviTraitement;
use App\Entity\Traitement;
use App\Entity\User;
use App\Form\SuiviTraitementType;
use App\Repository\SuiviTraitementRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Security\Core\Security;

#[Route('/suivi-traitements')]
class SuiviTraitementController extends AbstractController
{
    private Security $security;

    public function __construct(Security $security)
    {
        $this->security = $security;
    }

    #[Route('/selectionner-traitement', name: 'app_suivi_traitement_select_traitement', methods: ['GET'])]
    public function selectTraitement(EntityManagerInterface $entityManager): Response
    {
        $user = $this->security->getUser();
        
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }
        
        if ($this->isGranted('ROLE_PSYCHOLOGUE')) {
            $traitements = $entityManager->getRepository(Traitement::class)->createQueryBuilder('t')
                ->leftJoin('t.etudiant', 'e')
                ->leftJoin('t.psychologue', 'p')
                ->addSelect('e', 'p')
                ->where('t.psychologue = :user')
                ->setParameter('user', $user)
                ->getQuery()
                ->getResult();
        } elseif ($this->isGranted('ROLE_ETUDIANT')) {
            $traitements = $entityManager->getRepository(Traitement::class)->createQueryBuilder('t')
                ->leftJoin('t.etudiant', 'e')
                ->leftJoin('t.psychologue', 'p')
                ->addSelect('e', 'p')
                ->where('t.etudiant = :user')
                ->setParameter('user', $user)
                ->getQuery()
                ->getResult();
        } else {
            $traitements = [];
        }
        
        return $this->render('suivi_traitement/select_traitement.html.twig', [
            'traitements' => $traitements
        ]);
    }

    #[Route('/', name: 'app_suivi_traitement_index', methods: ['GET'])]
    public function index(SuiviTraitementRepository $suiviTraitementRepository): Response
    {
        $user = $this->security->getUser();
        
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        $suivis = [];
        
        if ($this->isGranted('ROLE_ADMIN') || $this->isGranted('ROLE_RESPONSABLE_ETUDIANT')) {
            $suivis = $suiviTraitementRepository->createQueryBuilder('s')
                ->leftJoin('s.traitement', 't')
                ->leftJoin('t.etudiant', 'e')
                ->leftJoin('t.psychologue', 'p')
                ->addSelect('t', 'e', 'p')
                ->getQuery()
                ->getResult();
        } elseif ($this->isGranted('ROLE_PSYCHOLOGUE')) {
            $suivis = $suiviTraitementRepository->createQueryBuilder('s')
                ->leftJoin('s.traitement', 't')
                ->leftJoin('t.etudiant', 'e')
                ->leftJoin('t.psychologue', 'p')
                ->addSelect('t', 'e', 'p')
                ->where('t.psychologue = :user')
                ->orderBy('t.titre', 'ASC')
                ->addOrderBy('e.nom', 'ASC')
                ->setParameter('user', $user)
                ->getQuery()
                ->getResult();
        } elseif ($this->isGranted('ROLE_ETUDIANT')) {
            $suivis = $suiviTraitementRepository->createQueryBuilder('s')
                ->leftJoin('s.traitement', 't')
                ->leftJoin('t.etudiant', 'e')
                ->leftJoin('t.psychologue', 'p')
                ->addSelect('t', 'e', 'p')
                ->where('t.etudiant = :user')
                ->orderBy('t.titre', 'ASC')
                ->addOrderBy('s.dateSuivi', 'DESC')
                ->setParameter('user', $user)
                ->getQuery()
                ->getResult();
        }

        return $this->render('suivi_traitement/index.html.twig', [
            'suivis' => $suivis,
            'user_role' => $this->getMainRole($user)
        ]);
    }

    #[Route('/a-valider', name: 'app_suivi_traitement_a_valider', methods: ['GET'])]
    #[IsGranted('ROLE_PSYCHOLOGUE')]
    public function aValider(SuiviTraitementRepository $suiviTraitementRepository): Response
    {
        $user = $this->security->getUser();
        $suivis = $suiviTraitementRepository->findNonValidesByPsychologue($user);

        return $this->render('suivi_traitement/a_valider.html.twig', [
            'suivis' => $suivis,
            'user_role' => 'psychologue'
        ]);
    }

    #[Route('/nouveau/{traitement_id}', name: 'app_suivi_traitement_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, int $traitement_id): Response
    {
        $user = $this->security->getUser();
        
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }
        
        $traitement = $entityManager->getRepository(Traitement::class)->find($traitement_id);
        
        if (!$traitement) {
            throw $this->createNotFoundException('Traitement non trouvé');
        }

        if (!$this->canAccessTraitement($traitement, $user)) {
            throw $this->createAccessDeniedException('Vous n\'avez pas accès à ce traitement');
        }

        $suivi = new SuiviTraitement();
        $suivi->setTraitement($traitement);
        
        $form = $this->createForm(SuiviTraitementType::class, $suivi, [
            'user_role' => $this->getMainRole($user),
            'is_etudiant' => $this->isGranted('ROLE_ETUDIANT')
        ]);
        
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if ($this->isGranted('ROLE_ETUDIANT')) {
                $suivi->setValide(false);
                $suivi->setSaisiPar('etudiant');
                if ($suivi->isEffectue()) {
                    $suivi->setHeureEffective(new \DateTime());
                }
            } else {
                $suivi->setSaisiPar('psychologue');
            }
            
            $entityManager->persist($suivi);
            $entityManager->flush();

            $this->addFlash('success', 'Le suivi a été créé avec succès.');

            return $this->redirectToRoute('app_traitement_show', ['id' => $traitement->getId()]);
        }

        if ($form->isSubmitted() && !$form->isValid()) {
            $this->addFlash('error', 'Le formulaire contient des erreurs. Veuillez corriger les champs obligatoires.');
        }

        return $this->render('suivi_traitement/new.html.twig', [
            'suivi' => $suivi,
            'traitement' => $traitement,
            'form' => $form->createView(),
            'user_role' => $this->getMainRole($user)
        ]);
    }

    #[Route('/{id}/modifier', name: 'app_suivi_traitement_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, SuiviTraitement $suivi, EntityManagerInterface $entityManager): Response
    {
        $user = $this->security->getUser();
        
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }
        
        if (!$this->canEditSuivi($suivi, $user)) {
            throw $this->createAccessDeniedException('Vous ne pouvez pas modifier ce suivi');
        }

        $form = $this->createForm(SuiviTraitementType::class, $suivi, [
            'user_role' => $this->getMainRole($user),
            'is_etudiant' => $this->isGranted('ROLE_ETUDIANT')
        ]);
        
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            $this->addFlash('success', 'Le suivi a été modifié avec succès.');

            return $this->redirectToRoute('app_suivi_traitement_show', ['id' => $suivi->getId()]);
        }

        if ($form->isSubmitted() && !$form->isValid()) {
            $this->addFlash('error', 'Le formulaire contient des erreurs. Veuillez corriger les champs obligatoires.');
        }

        return $this->render('suivi_traitement/edit.html.twig', [
            'suivi' => $suivi,
            'form' => $form->createView(),
            'user_role' => $this->getMainRole($user),
            'can_edit' => $this->canEditSuivi($suivi, $user),
            'can_validate' => $this->canValidateSuivi($suivi, $user)
        ]);
    }

    #[Route('/{id}/effectuer', name: 'app_suivi_traitement_effectuer', methods: ['POST'])]
    public function effectuer(Request $request, SuiviTraitement $suivi, EntityManagerInterface $entityManager): Response
    {
        $user = $this->security->getUser();
        
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }
        
        if (!$this->canEditSuivi($suivi, $user)) {
            throw $this->createAccessDeniedException('Vous ne pouvez pas modifier ce suivi');
        }

        if ($this->isCsrfTokenValid('effectuer'.$suivi->getId(), $request->request->get('_token'))) {
            $suivi->setEffectue(true);
            $suivi->setHeureEffective(new \DateTime());
            
            if ($this->isGranted('ROLE_ETUDIANT')) {
                $suivi->setValide(false);
            } else {
                $suivi->setValide(true);
            }
            
            $entityManager->flush();

            $this->addFlash('success', 'Le suivi a été marqué comme effectué.');
        }

        return $this->redirectToRoute('app_traitement_show', ['id' => $suivi->getTraitement()->getId()]);
    }

    #[Route('/{id}/valider', name: 'app_suivi_traitement_valider', methods: ['POST'])]
    #[IsGranted('ROLE_PSYCHOLOGUE')]
    public function valider(Request $request, SuiviTraitement $suivi, EntityManagerInterface $entityManager): Response
    {
        $user = $this->security->getUser();
        
        if (!$this->canValidateSuivi($suivi, $user)) {
            throw $this->createAccessDeniedException('Vous ne pouvez pas valider ce suivi');
        }

        if ($this->isCsrfTokenValid('valider'.$suivi->getId(), $request->request->get('_token'))) {
            if (!$suivi->isEffectue()) {
                $this->addFlash('error', 'Un suivi doit être effectué avant d\'être validé.');
            } else {
                $suivi->setValide(true);
                $entityManager->flush();

                $this->addFlash('success', 'Le suivi a été validé avec succès.');
            }
        }

        return $this->redirectToRoute('app_traitement_show', ['id' => $suivi->getTraitement()->getId()]);
    }

    #[Route('/{id}/supprimer', name: 'app_suivi_traitement_delete', methods: ['POST'])]
    public function delete(Request $request, SuiviTraitement $suivi, EntityManagerInterface $entityManager): Response
    {
        $user = $this->security->getUser();
        
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }
        
        if (!$this->canEditSuivi($suivi, $user)) {
            throw $this->createAccessDeniedException('Vous ne pouvez pas supprimer ce suivi');
        }

        if ($this->isCsrfTokenValid('delete'.$suivi->getId(), $request->request->get('_token'))) {
            $entityManager->remove($suivi);
            $entityManager->flush();

            $this->addFlash('success', 'Le suivi a été supprimé avec succès.');
        }

        return $this->redirectToRoute('app_traitement_show', ['id' => $suivi->getTraitement()->getId()]);
    }

    private function canAccessSuivi(SuiviTraitement $suivi, User $user): bool
    {
        return $this->canAccessTraitement($suivi->getTraitement(), $user);
    }

    private function canEditSuivi(SuiviTraitement $suivi, User $user): bool
    {
        if ($this->isGranted('ROLE_ADMIN')) {
            return true;
        }

        if ($this->isGranted('ROLE_PSYCHOLOGUE') && $suivi->getTraitement()->getPsychologue() === $user) {
            return true;
        }

        if ($this->isGranted('ROLE_ETUDIANT') && $suivi->getTraitement()->getEtudiant() === $user) {
            return true;
        }

        return false;
    }

    private function canValidateSuivi(SuiviTraitement $suivi, User $user): bool
    {
        if ($this->isGranted('ROLE_ADMIN')) {
            return true;
        }

        if ($this->isGranted('ROLE_PSYCHOLOGUE') && $suivi->getTraitement()->getPsychologue() === $user) {
            return $suivi->isEffectue() && !$suivi->isValide();
        }

        return false;
    }

    private function canAccessTraitement(Traitement $traitement, User $user): bool
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

    private function getMainRole(User $user): string
    {
        $roles = $user->getRoles();
        
        if (in_array('ROLE_ADMIN', $roles)) {
            return 'admin';
        } elseif (in_array('ROLE_PSYCHOLOGUE', $roles)) {
            return 'psychologue';
        } elseif (in_array('ROLE_ETUDIANT', $roles)) {
            return 'etudiant';
        } elseif (in_array('ROLE_RESPONSABLE_ETUDIANT', $roles)) {
            return 'responsable';
        }
        
        return 'user';
    }

    #[Route('/{id}', name: 'app_suivi_traitement_show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(int $id, SuiviTraitementRepository $suiviTraitementRepository): Response
    {
        // Vérification supplémentaire pour s'assurer que l'ID est bien un entier
        if (!is_numeric($id) || (int)$id != $id) {
            throw $this->createNotFoundException('ID invalide');
        }
        
        $suivi = $suiviTraitementRepository->find($id);
        
        if (!$suivi) {
            throw $this->createNotFoundException('Suivi non trouvé pour l\'ID: ' . $id);
        }

        $user = $this->security->getUser();

        if (!$this->canAccessSuivi($suivi, $user)) {
            throw $this->createAccessDeniedException();
        }

        return $this->render('suivi_traitement/show.html.twig', [
            'suivi' => $suivi,
            'user_role' => $this->getMainRole($user),
            'can_edit' => $this->canEditSuivi($suivi, $user),
            'can_validate' => $this->canValidateSuivi($suivi, $user)
        ]);
    }
}