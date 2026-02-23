<?php

namespace App\Controller\Evenement\Admin;

use App\Entity\Participation;
use App\Form\Evenement\ParticipationType;
use App\Repository\ParticipationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * CRUD des participations.
 */
#[Route('/admin/evenements/participations', name: 'app_admin_participation_')]
final class ParticipationCrudController extends AbstractController
{
    #[Route('', name: 'index', methods: ['GET'])]
    public function index(Request $request, ParticipationRepository $repository): Response
    {
        $q = trim((string) $request->query->get('q', ''));
        $sort = (string) $request->query->get('sort', 'date_inscription');
        $order = (string) $request->query->get('order', 'DESC');
        $participations = $repository->searchAndSort($q, $sort, $order);

        return $this->render('evenement/admin/participation_index.html.twig', [
            'route_prefix' => 'app_admin_',
            'space_label' => 'Admin',
            'show_sponsors' => true,
            'participations' => $participations,
            'q' => $q,
            'sort' => $sort,
            'order' => $order,
        ]);
    }

    #[Route('/new', name: 'new', methods: ['GET', 'POST'])]
    public function new(Request $request, ParticipationRepository $participationRepository, EntityManagerInterface $em): Response
    {
        $participation = new Participation();
        $form = $this->createForm(ParticipationType::class, $participation);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $participation = $form->getData();
            $evenement = $participation->getEvenement();
            $etudiant = $participation->getEtudiant();

            if ($evenement && $etudiant) {
                $existing = $participationRepository->findOneBy([
                    'evenement' => $evenement,
                    'etudiant' => $etudiant,
                ]);

                if ($existing) {
                    $this->addFlash('error', 'Cet étudiant est déjà inscrit à cet événement.');

                    return $this->render('evenement/admin/participation_form.html.twig', [
                        'route_prefix' => 'app_admin_',
                        'space_label' => 'Admin',
                        'show_sponsors' => true,
                        'participation' => $participation,
                        'form' => $form,
                        'is_edit' => false,
                    ]);
                }
            }
            if ($participation->isConfirme() && $participation->getEvenement()) {
                $participation->getEvenement()->incrementNombreInscrits();
            }
            $em->persist($participation);
            $em->flush();
            $this->addFlash('success', 'La participation a été créée.');
            return $this->redirectToRoute('app_admin_participation_index');
        }

        return $this->render('evenement/admin/participation_form.html.twig', [
            'route_prefix' => 'app_admin_',
            'space_label' => 'Admin',
            'show_sponsors' => true,
            'participation' => $participation,
            'form' => $form,
            'is_edit' => false,
        ]);
    }

    #[Route('/{id}/edit', name: 'edit', requirements: ['id' => '\d+'], methods: ['GET', 'POST'])]
    public function edit(Request $request, Participation $participation, EntityManagerInterface $em): Response
    {
        $ancienStatut = $participation->isConfirme();
        $form = $this->createForm(ParticipationType::class, $participation);
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
            return $this->redirectToRoute('app_admin_participation_index');
        }

        return $this->render('evenement/admin/participation_form.html.twig', [
            'route_prefix' => 'app_admin_',
            'space_label' => 'Admin',
            'show_sponsors' => true,
            'participation' => $participation,
            'form' => $form,
            'is_edit' => true,
        ]);
    }

    #[Route('/{id}/delete', name: 'delete', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function delete(Request $request, Participation $participation, EntityManagerInterface $em): Response
    {
        $token = $request->request->get('_token');
        if (!$this->isCsrfTokenValid('delete_participation_' . $participation->getId(), $token)) {
            $this->addFlash('error', 'Jeton de sécurité invalide.');
            return $this->redirectToRoute('app_admin_participation_index');
        }
        if ($participation->isConfirme() && $participation->getEvenement()) {
            $participation->getEvenement()->decrementNombreInscrits();
        }
        $em->remove($participation);
        $em->flush();
        $this->addFlash('success', 'La participation a été supprimée.');
        return $this->redirectToRoute('app_admin_participation_index');
    }
}
