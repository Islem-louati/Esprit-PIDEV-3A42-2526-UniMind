<?php

namespace App\Controller\Etudiant;

use App\Entity\Post;
use App\Entity\User;
use App\Form\PostType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;


#[Route('/etudiant/forum')]
class PostController extends AbstractController
{
     #[Route('/post/new', name: 'etudiant_forum_post_new', methods: ['POST'])]
#[IsGranted('ROLE_ETUDIANT')]
public function newPost(Request $request, EntityManagerInterface $entityManager): Response
{
    $isAjax = $request->isXmlHttpRequest();

    $post = new Post();
    
    /** @var \App\Entity\User $user */
    $user = $this->getUser();
    if (!$user) {
        return $this->json(['success' => false, 'errors' => ['user' => 'Non authentifié']], 401);
    }
    $post->setUser($user);

    $form = $this->createForm(PostType::class, $post);
    $form->handleRequest($request);

    if ($form->isSubmitted()) {
        if ($form->isValid()) {
            $post->setCreatedAt(new \DateTime());
            $post->setUpdatedAt(new \DateTime());

            $entityManager->persist($post);
            $entityManager->flush();

            if ($isAjax) {
                return $this->json(['success' => true, 'message' => 'Discussion créée avec succès !']);
            }

            $this->addFlash('success', 'Discussion créée avec succès.');
            return $this->redirectToRoute('etudiant_meditation_index');
        }

        $errors = [];
        foreach ($form->getErrors(true) as $error) {
            $field = $error->getOrigin() ? $error->getOrigin()->getName() : 'global';
            $errors[$field] = $error->getMessage();
        }

        if ($isAjax) {
            return $this->json(['success' => false, 'errors' => $errors], 400);
        }
    }

    return $this->redirectToRoute('etudiant_meditation_index');
}

    #[Route('/post/{id}/edit', name: 'etudiant_forum_post_edit', methods: ['POST'])]
public function editPost(
    Request $request,
    Post $post,
    EntityManagerInterface $entityManager
): Response
{
    $isAjax = $request->isXmlHttpRequest();
    /** @var \App\Entity\User $user */
    $user = $this->getUser();
    
    // Récupérer le token CSRF
    $token = $request->request->get('_token');
    
    // Vérifier le token CSRF
    if (!$this->isCsrfTokenValid('edit_post_' . $post->getPostId(), $token)) {
        if ($isAjax) {
            return $this->json([
                'success' => false,
                'errors' => ['post' => 'Token CSRF invalide. Veuillez rafraîchir la page.']
            ], 400);
        }
        
        $this->addFlash('error', 'Token CSRF invalide.');
        return $this->redirectToRoute('etudiant_meditation_index');
    }
    
    // Récupérer les données du formulaire
    $titre = $request->request->get('titre');
    $contenu = $request->request->get('contenu');
    $isAnonyme = $request->request->get('is_anonyme') === 'on';
    
    // Validation simple
    $errors = [];
    if (empty($titre)) {
        $errors['titre'] = 'Le titre est obligatoire.';
    } elseif (strlen($titre) < 5) {
        $errors['titre'] = 'Le titre doit faire au moins 5 caractères.';
    }
    
    if (empty($contenu)) {
        $errors['contenu'] = 'Le contenu est obligatoire.';
    } elseif (strlen($contenu) < 10) {
        $errors['contenu'] = 'Le contenu doit faire au moins 10 caractères.';
    }
    
    if (!empty($errors)) {
        if ($isAjax) {
            return $this->json([
                'success' => false,
                'errors' => $errors
            ], 400);
        }
        
        foreach ($errors as $error) {
            $this->addFlash('error', $error);
        }
        return $this->redirectToRoute('etudiant_meditation_index');
    }
    
    // Mise à jour
    $post->setTitre($titre);
    $post->setContenu($contenu);
    $post->setIsAnonyme($isAnonyme);
    $post->setUpdatedAt(new \DateTime());
    
    $entityManager->flush();
    
    if ($isAjax) {
        return $this->json([
            'success' => true,
            'message' => 'Discussion modifiée avec succès.',
            'post' => [
                'id' => $post->getPostId(),
                'titre' => $post->getTitre(),
                'contenu' => $post->getContenu(),
                'isAnonyme' => $post->isAnonyme(),
                'user' => $post->isAnonyme() ? 'Anonyme' : ($user ? $user->getFullName() : 'Anonyme')
            ]
        ]);
    }
    
    $this->addFlash('success', 'Discussion modifiée avec succès.');
    return $this->redirectToRoute('etudiant_meditation_index');
}
    #[Route('/post/{id}', name: 'etudiant_forum_post_delete', methods: ['POST'])]
    public function deletePost(
        Request $request,
        Post $post,
        EntityManagerInterface $entityManager
    ): Response
    {
        // COMMENTER ou SUPPRIMER la vérification d'utilisateur
        // if ($post->getUser() !== $security->getUser()) {
        //     $this->addFlash('error', 'Vous ne pouvez pas supprimer cette discussion.');
        //     return $this->redirectToRoute('etudiant_meditation_index');
        // }
        
        if ($this->isCsrfTokenValid('delete'.$post->getPostId(), $request->request->get('_token'))) {
            $entityManager->remove($post);
            $entityManager->flush();
            
            if ($request->isXmlHttpRequest()) {
                return new JsonResponse([
                    'success' => true,
                    'message' => 'Post supprimé avec succès.'
                ]);
            }
            
            $this->addFlash('success', 'Discussion supprimée avec succès.');
        }
        
        return $this->redirectToRoute('etudiant_meditation_index');
    }
}