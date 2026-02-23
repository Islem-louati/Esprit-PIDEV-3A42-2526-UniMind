<?php

namespace App\Controller\Evenement\ResponsableEtudiant;

use App\Entity\EvenementSponsor;
use App\Entity\User;
use App\Form\Evenement\EvenementSponsorType;
use App\Repository\EvenementRepository;
use App\Repository\EvenementSponsorRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/responsable-etudiant/contributions', name: 'app_back_contribution_')]
final class ContributionCrudController extends AbstractController
{
    #[Route('', name: 'index', methods: ['GET'])]
    public function index(EvenementSponsorRepository $repo): Response
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException();
        }

        return $this->render('evenement/responsable_etudiant/contribution_index.html.twig', [
            'route_prefix' => 'app_back_',
            'space_label' => 'Responsable étudiant',
            'show_sponsors' => false,
            'show_dashboard' => false,
            'contributions' => $repo->findForOrganisateur($user),
        ]);
    }

    #[Route('/new', name: 'new', methods: ['GET', 'POST'])]
    public function new(Request $request, EvenementRepository $evenementRepository, EntityManagerInterface $em): Response
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException();
        }

        $evenementSponsor = new EvenementSponsor();
        $form = $this->createForm(EvenementSponsorType::class, $evenementSponsor, [
            'evenement_choices' => $evenementRepository->findBy(['organisateur' => $user], ['dateDebut' => 'ASC']),
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $evenement = $evenementSponsor->getEvenement();
            if (!$evenement || $evenement->getOrganisateur() !== $user) {
                throw $this->createAccessDeniedException();
            }

            $em->persist($evenementSponsor);
            $em->flush();
            $this->addFlash('success', 'La contribution a été enregistrée.');
            return $this->redirectToRoute('app_back_contribution_index');
        }

        return $this->render('evenement/responsable_etudiant/evenement_sponsor_form.html.twig', [
            'route_prefix' => 'app_back_',
            'space_label' => 'Responsable étudiant',
            'show_sponsors' => false,
            'show_dashboard' => false,
            'evenement_sponsor' => $evenementSponsor,
            'form' => $form,
            'is_edit' => false,
        ]);
    }

    #[Route('/{id}/edit', name: 'edit', requirements: ['id' => '\\d+'], methods: ['GET', 'POST'])]
    public function edit(Request $request, EvenementSponsor $evenementSponsor, EvenementRepository $evenementRepository, EntityManagerInterface $em): Response
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException();
        }

        $evenement = $evenementSponsor->getEvenement();
        if (!$evenement || $evenement->getOrganisateur() !== $user) {
            throw $this->createAccessDeniedException();
        }

        $form = $this->createForm(EvenementSponsorType::class, $evenementSponsor, [
            'evenement_choices' => $evenementRepository->findBy(['organisateur' => $user], ['dateDebut' => 'ASC']),
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $evenementAfter = $evenementSponsor->getEvenement();
            if (!$evenementAfter || $evenementAfter->getOrganisateur() !== $user) {
                throw $this->createAccessDeniedException();
            }

            $em->flush();
            $this->addFlash('success', 'La contribution a été modifiée.');
            return $this->redirectToRoute('app_back_contribution_index');
        }

        return $this->render('evenement/responsable_etudiant/evenement_sponsor_form.html.twig', [
            'route_prefix' => 'app_back_',
            'space_label' => 'Responsable étudiant',
            'show_sponsors' => false,
            'show_dashboard' => false,
            'evenement_sponsor' => $evenementSponsor,
            'form' => $form,
            'is_edit' => true,
        ]);
    }

    #[Route('/{id}/delete', name: 'delete', requirements: ['id' => '\\d+'], methods: ['POST'])]
    public function delete(Request $request, EvenementSponsor $evenementSponsor, EntityManagerInterface $em): Response
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException();
        }

        $evenement = $evenementSponsor->getEvenement();
        if (!$evenement || $evenement->getOrganisateur() !== $user) {
            throw $this->createAccessDeniedException();
        }

        $token = $request->request->get('_token');
        if (!$this->isCsrfTokenValid('delete_evenement_sponsor_' . $evenementSponsor->getId(), $token)) {
            $this->addFlash('error', 'Jeton de sécurité invalide.');
            return $this->redirectToRoute('app_back_contribution_index');
        }

        $em->remove($evenementSponsor);
        $em->flush();
        $this->addFlash('success', 'La contribution a été supprimée.');
        return $this->redirectToRoute('app_back_contribution_index');
    }
}
