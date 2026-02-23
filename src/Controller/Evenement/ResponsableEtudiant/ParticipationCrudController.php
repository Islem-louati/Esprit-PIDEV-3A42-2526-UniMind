<?php

namespace App\Controller\Evenement\ResponsableEtudiant;

use App\Entity\Participation;
use App\Form\Evenement\ParticipationType;
use App\Repository\ParticipationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * CRUD des participations (Responsable étudiant).
 */
#[Route('/responsable-etudiant/evenements/participations', name: 'app_back_participation_')]
final class ParticipationCrudController extends AbstractController
{
    #[Route('', name: 'index', methods: ['GET'])]
    public function index(Request $request, ParticipationRepository $repository): Response
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        $q = trim((string) $request->query->get('q', ''));
        $sort = (string) $request->query->get('sort', 'date_inscription');
        $order = (string) $request->query->get('order', 'DESC');
        $participations = $repository->searchAndSortForOrganisateur($user, $q, $sort, $order);

        return $this->render('evenement/responsable_etudiant/participation_index.html.twig', [
            'route_prefix' => 'app_back_',
            'space_label' => 'Responsable étudiant',
            'show_sponsors' => false,
            'show_dashboard' => false,
            'participations' => $participations,
            'q' => $q,
            'sort' => $sort,
            'order' => $order,
        ]);
    }

    #[Route('/new', name: 'new', methods: ['GET', 'POST'])]
    public function new(Request $request, ParticipationRepository $participationRepository, EntityManagerInterface $em): Response
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        $participation = new Participation();
        $form = $this->createForm(ParticipationType::class, $participation, [
            'organisateur_evenements' => $user,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $participation = $form->getData();

            $evenement = $participation->getEvenement();
            if (!$evenement || !$evenement->getOrganisateur() || $evenement->getOrganisateur()->getId() !== $user->getId()) {
                throw $this->createAccessDeniedException();
            }

            $existing = $participationRepository->findOneBy([
                'evenement' => $evenement,
                'etudiant' => $participation->getEtudiant(),
            ]);
            if ($existing) {
                $this->addFlash('error', 'Cet étudiant est déjà inscrit à cet événement.');

                return $this->render('evenement/responsable_etudiant/participation_form.html.twig', [
                    'route_prefix' => 'app_back_',
                    'space_label' => 'Responsable étudiant',
                    'show_sponsors' => false,
                    'show_dashboard' => false,
                    'participation' => $participation,
                    'form' => $form,
                    'is_edit' => false,
                ]);
            }

            if ($participation->isConfirme() && $participation->getEvenement()) {
                $participation->getEvenement()->incrementNombreInscrits();
            }
            $em->persist($participation);
            $em->flush();
            $this->addFlash('success', 'La participation a été créée.');
            return $this->redirectToRoute('app_back_participation_index');
        }

        return $this->render('evenement/responsable_etudiant/participation_form.html.twig', [
            'route_prefix' => 'app_back_',
            'space_label' => 'Responsable étudiant',
            'show_sponsors' => false,
            'show_dashboard' => false,
            'participation' => $participation,
            'form' => $form,
            'is_edit' => false,
        ]);
    }

    #[Route('/{id}/edit', name: 'edit', requirements: ['id' => '\\d+'], methods: ['GET', 'POST'])]
    public function edit(Request $request, Participation $participation, EntityManagerInterface $em): Response
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        $evenement = $participation->getEvenement();
        if (!$evenement || !$evenement->getOrganisateur() || $evenement->getOrganisateur()->getId() !== $user->getId()) {
            throw $this->createAccessDeniedException();
        }

        $ancienStatut = $participation->isConfirme();
        $form = $this->createForm(ParticipationType::class, $participation, [
            'organisateur_evenements' => $user,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $nouveauStatut = $participation->isConfirme();
            $evenement = $participation->getEvenement();
            if ($evenement) {
                if (!$ancienStatut && $nouveauStatut) {
                    $evenement->incrementNombreInscrits();
                } elseif ($ancienStatut && !$nouveauStatut) {
                    $evenement->decrementNombreInscrits();
                }
            }
            $em->flush();
            $this->addFlash('success', 'La participation a été modifiée.');
            return $this->redirectToRoute('app_back_participation_index');
        }

        return $this->render('evenement/responsable_etudiant/participation_form.html.twig', [
            'route_prefix' => 'app_back_',
            'space_label' => 'Responsable étudiant',
            'show_sponsors' => false,
            'show_dashboard' => false,
            'participation' => $participation,
            'form' => $form,
            'is_edit' => true,
        ]);
    }

    #[Route('/{id}/delete', name: 'delete', requirements: ['id' => '\\d+'], methods: ['POST'])]
    public function delete(Request $request, Participation $participation, EntityManagerInterface $em): Response
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        $evenement = $participation->getEvenement();
        if (!$evenement || !$evenement->getOrganisateur() || $evenement->getOrganisateur()->getId() !== $user->getId()) {
            throw $this->createAccessDeniedException();
        }

        $token = $request->request->get('_token');
        if (!$this->isCsrfTokenValid('delete_participation_' . $participation->getId(), $token)) {
            $this->addFlash('error', 'Jeton de sécurité invalide.');
            return $this->redirectToRoute('app_back_participation_index');
        }
        if ($participation->isConfirme() && $participation->getEvenement()) {
            $participation->getEvenement()->decrementNombreInscrits();
        }
        $em->remove($participation);
        $em->flush();
        $this->addFlash('success', 'La participation a été supprimée.');
        return $this->redirectToRoute('app_back_participation_index');
    }
}
