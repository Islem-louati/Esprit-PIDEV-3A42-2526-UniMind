<?php

namespace App\Controller\Etudiant;

use App\Entity\Commentaire;
use App\Entity\Post;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/etudiant/forum')]
class CommentaireController extends AbstractController
{
    #[Route('/post/{id}/comment', name: 'etudiant_forum_comment_new', methods: ['POST'])]
    public function newComment(
        Request $request,
        Post $post,
        EntityManagerInterface $entityManager
    ): Response {
        $isAjax = $request->isXmlHttpRequest();

        /** @var \App\Entity\User $user */
        $user = $this->getUser();
        
        // Récupérer les données
        $contenu = trim($request->request->get('contenu', ''));
        $isAnonyme = $request->request->get('is_anonyme') === 'on';
        
        // Récupérer le token CSRF
        $token = $request->request->get('_token');
        
        // Validation du token
        if (!$this->isCsrfTokenValid('comment_' . $post->getPostId(), $token)) {
            if ($isAjax) {
                return $this->json([
                    'success' => false,
                    'errors' => ['commentaire' => 'Session expirée. Veuillez rafraîchir la page.']
                ], 400);
            }
            $this->addFlash('error', 'Token CSRF invalide.');
            return $this->redirectToRoute('etudiant_meditation_index');
        }
        
        // Validation du contenu
        if (empty($contenu)) {
            if ($isAjax) {
                return $this->json([
                    'success' => false,
                    'errors' => ['contenu' => 'Le commentaire ne peut pas être vide.']
                ], 400);
            }
            $this->addFlash('error', 'Le commentaire ne peut pas être vide.');
            return $this->redirectToRoute('etudiant_meditation_index');
        }
        
        if (strlen($contenu) < 3) {
            if ($isAjax) {
                return $this->json([
                    'success' => false,
                    'errors' => ['contenu' => 'Le commentaire doit faire au moins 3 caractères.']
                ], 400);
            }
            $this->addFlash('error', 'Le commentaire doit faire au moins 3 caractères.');
            return $this->redirectToRoute('etudiant_meditation_index');
        }
        
        // Création du commentaire
        $commentaire = new Commentaire();
        $commentaire->setContenu($contenu);
        $commentaire->setIsAnonyme($isAnonyme);
        $commentaire->setPost($post);
        $commentaire->setUser($user);
        $commentaire->setCreatedAt(new \DateTime());
        
        $entityManager->persist($commentaire);
        $entityManager->flush();
        
        if ($isAjax) {
            return $this->json([
                'success' => true,
                'message' => 'Commentaire ajouté avec succès.',
                'commentaire' => [
                    'id' => $commentaire->getCommentaireId(),
                    'contenu' => $commentaire->getContenu(),
                    'isAnonyme' => $commentaire->isAnonyme(),
                    'createdAt' => $commentaire->getCreatedAt()->format('d/m H:i'),
                    'user' => $commentaire->isAnonyme() ? 'Anonyme' : ($user ? $user->getFullName() : 'Anonyme')
                ]
            ]);
        }
        
        $this->addFlash('success', 'Commentaire ajouté avec succès.');
        return $this->redirectToRoute('etudiant_meditation_index');
    }

    #[Route('/comment/{id}/edit', name: 'etudiant_forum_comment_edit', methods: ['POST'])]
    public function editComment(
        Request $request,
        Commentaire $commentaire,
        EntityManagerInterface $entityManager
    ): Response {
        $isAjax = $request->isXmlHttpRequest();
        
        $contenu = trim($request->request->get('contenu', ''));
        $isAnonyme = $request->request->get('is_anonyme') === 'on';
        $token = $request->request->get('_token');
        
        // Validation du token
        if (!$this->isCsrfTokenValid('edit_comment_' . $commentaire->getCommentaireId(), $token)) {
            if ($isAjax) {
                return $this->json([
                    'success' => false,
                    'errors' => ['commentaire' => 'Token CSRF invalide.']
                ], 400);
            }
            $this->addFlash('error', 'Token CSRF invalide.');
            return $this->redirectToRoute('etudiant_meditation_index');
        }
        
        // Validation du contenu
        if (empty($contenu)) {
            if ($isAjax) {
                return $this->json([
                    'success' => false,
                    'errors' => ['contenu' => 'Le commentaire ne peut pas être vide.']
                ], 400);
            }
            $this->addFlash('error', 'Le commentaire ne peut pas être vide.');
            return $this->redirectToRoute('etudiant_meditation_index');
        }
        
        if (strlen($contenu) < 3) {
            if ($isAjax) {
                return $this->json([
                    'success' => false,
                    'errors' => ['contenu' => 'Le commentaire doit faire au moins 3 caractères.']
                ], 400);
            }
            $this->addFlash('error', 'Le commentaire doit faire au moins 3 caractères.');
            return $this->redirectToRoute('etudiant_meditation_index');
        }
        
        // Mise à jour
        $commentaire->setContenu($contenu);
        $commentaire->setIsAnonyme($isAnonyme);
        
        $entityManager->flush();
        
        if ($isAjax) {
            return $this->json([
                'success' => true,
                'message' => 'Commentaire modifié avec succès.',
                'commentaire' => [
                    'id' => $commentaire->getCommentaireId(),
                    'contenu' => $commentaire->getContenu(),
                    'isAnonyme' => $commentaire->isAnonyme()
                ]
            ]);
        }
        
        $this->addFlash('success', 'Commentaire modifié avec succès.');
        return $this->redirectToRoute('etudiant_meditation_index');
    }
    
    #[Route('/comment/{id}', name: 'etudiant_forum_comment_delete', methods: ['POST'])]
    public function deleteComment(
        Request $request,
        Commentaire $commentaire,
        EntityManagerInterface $entityManager
    ): Response {
        $isAjax = $request->isXmlHttpRequest();
        $token = $request->request->get('_token');
        
        if ($this->isCsrfTokenValid('delete' . $commentaire->getCommentaireId(), $token)) {
            $entityManager->remove($commentaire);
            $entityManager->flush();
            
            if ($isAjax) {
                return $this->json([
                    'success' => true,
                    'message' => 'Commentaire supprimé avec succès.'
                ]);
            }
            
            $this->addFlash('success', 'Commentaire supprimé avec succès.');
        } else {
            if ($isAjax) {
                return $this->json([
                    'success' => false,
                    'message' => 'Token CSRF invalide.'
                ], 400);
            }
            
            $this->addFlash('error', 'Token CSRF invalide.');
        }
        
        return $this->redirectToRoute('etudiant_meditation_index');
    }
}