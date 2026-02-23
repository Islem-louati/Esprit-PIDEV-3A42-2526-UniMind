<?php

namespace App\Controller\Evenement\Admin;

use App\Entity\EvenementSponsor;
use App\Form\Evenement\EvenementSponsorType;
use App\Repository\EvenementSponsorRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * CRUD des liens Événement–Sponsor (Back Office).
 */
#[Route('/admin/evenement-sponsors', name: 'app_admin_evenement_sponsor_')]
final class EvenementSponsorCrudController extends AbstractController
{
    #[Route('', name: 'index', methods: ['GET'])]
    public function index(): Response
    {
        return $this->redirectToRoute('app_admin_sponsor_index');
    }

    #[Route('/new', name: 'new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        $this->addFlash('error', 'L\'admin peut gérer les sponsors uniquement. L\'attribution aux événements se fait par les responsables.');

        return $this->redirectToRoute('app_admin_sponsor_index');
    }

    #[Route('/{id}/edit', name: 'edit', requirements: ['id' => '\d+'], methods: ['GET', 'POST'])]
    public function edit(Request $request, EvenementSponsor $evenementSponsor, EntityManagerInterface $em): Response
    {
        $this->addFlash('error', 'L\'admin peut gérer les sponsors uniquement. L\'attribution aux événements se fait par les responsables.');

        return $this->redirectToRoute('app_admin_sponsor_index');
    }

    #[Route('/{id}/delete', name: 'delete', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function delete(Request $request, EvenementSponsor $evenementSponsor, EntityManagerInterface $em): Response
    {
        $this->addFlash('error', 'L\'admin peut gérer les sponsors uniquement. L\'attribution aux événements se fait par les responsables.');

        return $this->redirectToRoute('app_admin_sponsor_index');
    }
}
