<?php

namespace App\Controller\Evenement\Admin;

use App\Entity\Sponsor;
use App\Entity\EvenementSponsor;
use App\Form\Evenement\SponsorType;
use App\Form\Evenement\EvenementSponsorType;
use App\Form\Evenement\SponsorContributionType;
use App\Repository\SponsorRepository;
use App\Repository\EvenementSponsorRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\String\Slugger\SluggerInterface;

/**
 * CRUD des sponsors
 */
#[Route('/admin/sponsors', name: 'app_admin_sponsor_')]
final class SponsorCrudController extends AbstractController
{
    private SluggerInterface $slugger;

    public function __construct(SluggerInterface $slugger)
    {
        $this->slugger = $slugger;
    }

    #[Route('', name: 'index', methods: ['GET'])]
    public function index(Request $request, SponsorRepository $sponsorRepository, EvenementSponsorRepository $evenementSponsorRepository): Response
    {
        // Recherche et tri sur la table unifiée (contributions)
        $q = trim((string) $request->query->get('q', ''));
        $sort = (string) $request->query->get('sort', 'dateContribution');
        $order = (string) $request->query->get('order', 'DESC');

        $evenementSponsors = $evenementSponsorRepository->searchAndSort($q, $sort, $order);

        $sponsorsSansContribution = $sponsorRepository->findSponsorsWithoutContribution();

        return $this->render('evenement/admin/sponsor_index.html.twig', [
            'route_prefix' => 'app_admin_',
            'space_label' => 'Admin',
            'show_sponsors' => true,
            'evenement_sponsors' => $evenementSponsors,
            'sponsors_sans_contribution' => $sponsorsSansContribution,
            'q' => $q,
            'sort' => $sort,
            'order' => $order,
        ]);
    }

    #[Route('/{id}', name: 'show', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function show(Sponsor $sponsor): Response
    {
        return $this->render('evenement/admin/sponsor_show.html.twig', [
            'route_prefix' => 'app_admin_',
            'space_label' => 'Admin',
            'show_sponsors' => true,
            'sponsor' => $sponsor,
        ]);
    }

    #[Route('/new', name: 'new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        $sponsor = new Sponsor();
        $form = $this->createForm(SponsorType::class, $sponsor);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var UploadedFile|null $logoFile */
            $logoFile = $form->get('logoFile')->getData();
            if ($logoFile) {
                $originalFilename = pathinfo($logoFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $this->slugger->slug($originalFilename);
                $newFilename = $safeFilename . '-' . uniqid() . '.' . $logoFile->guessExtension();
                $logoFile->move(
                    $this->getParameter('kernel.project_dir') . '/public/uploadsEvent/sponsors',
                    $newFilename
                );
                $sponsor->setLogo($newFilename);
            }

            $em->persist($sponsor);
            $em->flush();
            $this->addFlash('success', 'Le sponsor a été créé.');
            return $this->redirectToRoute('app_admin_sponsor_index');
        }

        return $this->render('evenement/admin/sponsor_form.html.twig', [
            'route_prefix' => 'app_admin_',
            'space_label' => 'Admin',
            'show_sponsors' => true,
            'sponsor' => $sponsor,
            'form' => $form,
            'is_edit' => false,
        ]);
    }

    #[Route('/{id}/edit', name: 'edit', requirements: ['id' => '\d+'], methods: ['GET', 'POST'])]
    public function edit(Request $request, Sponsor $sponsor, EntityManagerInterface $em): Response
    {
        $form = $this->createForm(SponsorType::class, $sponsor);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var UploadedFile|null $logoFile */
            $logoFile = $form->get('logoFile')->getData();
            if ($logoFile) {
                $originalFilename = pathinfo($logoFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $this->slugger->slug($originalFilename);
                $newFilename = $safeFilename . '-' . uniqid() . '.' . $logoFile->guessExtension();
                $logoFile->move(
                    $this->getParameter('kernel.project_dir') . '/public/uploadsEvent/sponsors',
                    $newFilename
                );
                $sponsor->setLogo($newFilename);
            }

            $em->flush();
            $this->addFlash('success', 'Le sponsor a été modifié.');
            return $this->redirectToRoute('app_admin_sponsor_index');
        }

        return $this->render('evenement/admin/sponsor_form.html.twig', [
            'route_prefix' => 'app_admin_',
            'space_label' => 'Admin',
            'show_sponsors' => true,
            'sponsor' => $sponsor,
            'form' => $form,
            'is_edit' => true,
        ]);
    }

    #[Route('/{id}/delete', name: 'delete', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function delete(Request $request, Sponsor $sponsor, EntityManagerInterface $em): Response
    {
        $token = $request->request->get('_token');
        if (!$this->isCsrfTokenValid('delete_sponsor_' . $sponsor->getSponsorId(), $token)) {
            $this->addFlash('error', 'Jeton de sécurité invalide.');
            return $this->redirectToRoute('app_admin_sponsor_index');
        }
        $em->remove($sponsor);
        $em->flush();
        $this->addFlash('success', 'Le sponsor a été supprimé.');
        return $this->redirectToRoute('app_admin_sponsor_index');
    }

    // --- Gestion des EvenementSponsor (liens événement-sponsor) ---

    #[Route('/contributions/new', name: 'contribution_new', methods: ['GET', 'POST'])]
    public function contributionNew(Request $request, EntityManagerInterface $em): Response
    {
        $this->addFlash('error', 'L\'admin peut gérer les sponsors uniquement. L\'attribution aux événements se fait par les responsables.');
        return $this->redirectToRoute('app_admin_sponsor_index');
    }

    #[Route('/contributions/{id}/edit', name: 'contribution_edit', requirements: ['id' => '\d+'], methods: ['GET', 'POST'])]
    public function contributionEdit(Request $request, EvenementSponsor $evenementSponsor, EntityManagerInterface $em): Response
    {
        $this->addFlash('error', 'L\'admin peut gérer les sponsors uniquement. L\'attribution aux événements se fait par les responsables.');
        return $this->redirectToRoute('app_admin_sponsor_index');
    }

    #[Route('/contributions/{id}/delete', name: 'contribution_delete', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function contributionDelete(Request $request, EvenementSponsor $evenementSponsor, EntityManagerInterface $em): Response
    {
        $this->addFlash('error', 'L\'admin peut gérer les sponsors uniquement. L\'attribution aux événements se fait par les responsables.');
        return $this->redirectToRoute('app_admin_sponsor_index');
    }

    // === Formulaire combiné Sponsor + Contribution ===

    #[Route('/add-sponsor-contribution', name: 'sponsor_contribution_new', methods: ['GET', 'POST'])]
    public function sponsorContributionNew(Request $request, EntityManagerInterface $em): Response
    {
        $this->addFlash('error', 'L\'admin peut gérer les sponsors uniquement. L\'attribution aux événements se fait par les responsables.');
        return $this->redirectToRoute('app_admin_sponsor_new');
    }

    // === Édition unifiée Sponsor + Contribution ===

    #[Route('/edit-unified/{id}', name: 'edit_unified', requirements: ['id' => '\d+'], methods: ['GET', 'POST'])]
    public function editUnified(Sponsor $sponsor, Request $request, EntityManagerInterface $em): Response
    {
        $this->addFlash('error', 'L\'admin peut gérer les sponsors uniquement. L\'attribution aux événements se fait par les responsables.');
        return $this->redirectToRoute('app_admin_sponsor_edit', ['id' => $sponsor->getSponsorId()]);
    }
}
