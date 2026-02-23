<?php

namespace App\Controller\Evenement\ResponsableEtudiant;

use App\Repository\ParticipationRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/responsable-etudiant/evenements/feedbacks', name: 'app_back_feedback_')]
final class FeedbackCrudController extends AbstractController
{
    #[Route('', name: 'index', methods: ['GET'])]
    public function index(Request $request, ParticipationRepository $repository): Response
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        $q = trim((string) $request->query->get('q', ''));
        $sort = (string) $request->query->get('sort', 'feedback_at');
        $order = (string) $request->query->get('order', 'DESC');

        $feedbacks = $repository->searchFeedbacks($q, $sort, $order, $user);

        return $this->render('evenement/responsable_etudiant/feedback_index.html.twig', [
            'route_prefix' => 'app_back_',
            'space_label' => 'Responsable étudiant',
            'show_sponsors' => false,
            'show_dashboard' => false,
            'feedbacks' => $feedbacks,
            'q' => $q,
            'sort' => $sort,
            'order' => $order,
        ]);
    }
}
