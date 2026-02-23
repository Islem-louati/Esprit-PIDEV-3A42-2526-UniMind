<?php

namespace App\Controller\Evenement\Admin;

use App\Repository\ParticipationRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/evenements/feedbacks', name: 'app_admin_feedback_')]
final class FeedbackCrudController extends AbstractController
{
    #[Route('', name: 'index', methods: ['GET'])]
    public function index(Request $request, ParticipationRepository $repository): Response
    {
        $q = trim((string) $request->query->get('q', ''));
        $sort = (string) $request->query->get('sort', 'feedback_at');
        $order = (string) $request->query->get('order', 'DESC');

        $feedbacks = $repository->searchFeedbacks($q, $sort, $order);

        return $this->render('evenement/admin/feedback_index.html.twig', [
            'route_prefix' => 'app_admin_',
            'space_label' => 'Admin',
            'show_sponsors' => true,
            'feedbacks' => $feedbacks,
            'q' => $q,
            'sort' => $sort,
            'order' => $order,
        ]);
    }
}
