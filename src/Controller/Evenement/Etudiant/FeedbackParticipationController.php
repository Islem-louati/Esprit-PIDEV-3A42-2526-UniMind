<?php

namespace App\Controller\Evenement\Etudiant;

use App\Entity\Participation;
use App\Form\Evenement\FeedbackParticipationType;
use App\Enum\RoleType;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/etudiant/participations', name: 'app_etudiant_participation_')]
final class FeedbackParticipationController extends AbstractController
{
    #[Route('/{id}/feedback', name: 'feedback', requirements: ['id' => '\\d+'], methods: ['GET', 'POST'])]
    public function feedback(Request $request, Participation $participation, EntityManagerInterface $em, UserRepository $userRepository): Response
    {
        $user = $this->getUser();
        if (!$user && $this->getParameter('kernel.environment') === 'dev') {
            $user = $userRepository->findOneBy(['role' => RoleType::ETUDIANT]);
        }
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        if ($participation->getEtudiant() !== $user) {
            throw $this->createAccessDeniedException();
        }

        if (!$participation->canGiveFeedback()) {
            $this->addFlash('error', 'Vous ne pouvez pas donner un feedback pour cet événement.');
            return $this->redirectToRoute('app_mes_evenements_index');
        }

        $form = $this->createForm(FeedbackParticipationType::class, $participation);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $participation->setFeedbackAt(new \DateTime());
            $em->flush();
            $this->addFlash('success', 'Merci pour votre feedback !');

            return $this->redirectToRoute('app_mes_evenements_index');
        }

        return $this->render('evenement/etudiant/participation/feedback.html.twig', [
            'participation' => $participation,
            'form' => $form,
        ]);
    }
}
