<?php

namespace App\Controller\Evenement\Etudiant;

use App\Entity\Favori;
use App\Enum\RoleType;
use App\Repository\EvenementRepository;
use App\Repository\FavoriRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/etudiant/favoris', name: 'app_favori_etudiant_')]
final class FavoriController extends AbstractController
{
    #[Route('', name: 'index', methods: ['GET'])]
    public function index(FavoriRepository $favoriRepository, UserRepository $userRepository): Response
    {
        $user = $this->getUser();
        if (!$user && $this->getParameter('kernel.environment') === 'dev') {
            $user = $userRepository->findOneBy(['role' => RoleType::ETUDIANT]);
        }
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        $evenements = $favoriRepository->findEvenementsFavorisByEtudiant($user);

        return $this->render('evenement/etudiant/favoris/index.html.twig', [
            'evenements' => $evenements,
        ]);
    }

    #[Route('/{id}/toggle', name: 'toggle', requirements: ['id' => '\\d+'], methods: ['POST'])]
    public function toggle(
        int $id,
        Request $request,
        EvenementRepository $evenementRepository,
        FavoriRepository $favoriRepository,
        UserRepository $userRepository,
        EntityManagerInterface $em
    ): Response {
        $user = $this->getUser();
        if (!$user && $this->getParameter('kernel.environment') === 'dev') {
            $user = $userRepository->findOneBy(['role' => RoleType::ETUDIANT]);
        }
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        if (!method_exists($user, 'isEtudiant') || !$user->isEtudiant()) {
            throw $this->createAccessDeniedException();
        }

        $evenement = $evenementRepository->find($id);
        if (!$evenement) {
            throw $this->createNotFoundException('Événement introuvable.');
        }

        $token = (string) $request->request->get('_token');
        if (!$this->isCsrfTokenValid('toggle_favori_evenement_' . $evenement->getId(), $token)) {
            $this->addFlash('error', 'Jeton de sécurité invalide.');
            return $this->redirectToRoute('app_evenement_etudiant_show', ['id' => $evenement->getId()]);
        }

        $existing = $favoriRepository->findOneByEtudiantAndEvenement($user, $evenement);

        if ($existing) {
            $em->remove($existing);
            $em->flush();
            $this->addFlash('success', 'Événement retiré de vos favoris.');
        } else {
            $favori = new Favori();
            $favori->setEtudiant($user);
            $favori->setEvenement($evenement);
            $em->persist($favori);
            $em->flush();
            $this->addFlash('success', 'Événement ajouté à vos favoris.');
        }

        $referer = (string) $request->headers->get('referer');
        if ($referer !== '') {
            return $this->redirect($referer);
        }

        return $this->redirectToRoute('app_evenement_etudiant_show', ['id' => $evenement->getId()]);
    }
}
