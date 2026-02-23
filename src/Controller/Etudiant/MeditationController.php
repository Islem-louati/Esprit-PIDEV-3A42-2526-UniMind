<?php

namespace App\Controller\Etudiant;

use App\Entity\CategorieMeditation;
use App\Entity\SeanceMeditation;
use App\Entity\Post;
use App\Form\PostType;
use App\Repository\CategorieMeditationRepository;
use App\Repository\SeanceMeditationRepository;
use App\Repository\PostRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Security;

#[Route('/etudiant/meditation')]
class MeditationController extends AbstractController
{
    #[Route('/', name: 'etudiant_meditation_index')]
    public function index(Request $request, EntityManagerInterface $entityManager): Response
    {
        // Récupérer les catégories - SANS tri par createdAt si le champ n'existe pas
        $categories = $entityManager->getRepository(CategorieMeditation::class)
    ->findAll();
            // Si vous voulez trier par nom à la place :
            // ->findBy(['isActive' => true], ['nom' => 'ASC']);
        
        // Récupérer tous les posts récents
        $posts = $entityManager->getRepository(Post::class)
            ->findBy([], ['created_at' => 'DESC']);
        
        // Créer le formulaire pour un nouveau post
        $post = new Post();
        $form = $this->createForm(PostType::class, $post);
        $form->handleRequest($request);
        
        if ($form->isSubmitted() && $form->isValid()) {
            $post->setUser($this->getUser());
            $post->setCreatedAt(new \DateTime());
            
            $entityManager->persist($post);
            $entityManager->flush();
            
            $this->addFlash('success', 'Votre discussion a été publiée avec succès!');
            return $this->redirectToRoute('etudiant_meditation_index');
        }
        
        return $this->render('etudiant/meditation/index.html.twig', [
            'categories' => $categories,
            'posts' => $posts,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/categorie/{id}', name: 'etudiant_meditation_categorie', methods: ['GET', 'POST'])]
    public function categorie(
        Request $request,
        CategorieMeditation $categorie,
        SeanceMeditationRepository $seanceMeditationRepository,
        PostRepository $postRepository,
        EntityManagerInterface $entityManager,
        Security $security
    ): Response
    {
        // Récupérer les séances actives de cette catégorie
        $seances = $seanceMeditationRepository->findBy([
            'categorie' => $categorie,
            'is_active' => true
        ], ['created_at' => 'DESC']);

        // Récupérer les posts de cette catégorie
        $posts = $postRepository->findBy(
            ['categorieMeditation' => $categorie],
            ['created_at' => 'DESC']
        );

        // Créer un nouveau post pour le formulaire
        $post = new Post();
        
        // Créer le formulaire AVANT de définir les données
        $form = $this->createForm(PostType::class, $post);
        
        // Gérer la soumission
        $form->handleRequest($request);
        
        if ($form->isSubmitted() && $form->isValid()) {
            // Définir les données manquantes qui ne sont pas dans le formulaire
            $post->setCategorieMeditation($categorie);
            $post->setUser($security->getUser());
            $post->setCreatedAt(new \DateTime());
            
            $entityManager->persist($post);
            $entityManager->flush();
            
            $this->addFlash('success', 'Votre discussion a été publiée avec succès.');
            return $this->redirectToRoute('etudiant_meditation_categorie', ['id' => $categorie->getCategorieId()]);
        } elseif ($form->isSubmitted() && !$form->isValid()) {
            // Afficher les erreurs de validation
            $errors = [];
            foreach ($form->getErrors(true) as $error) {
                $errors[] = $error->getMessage();
            }
            if (!empty($errors)) {
                $this->addFlash('error', 'Erreurs : ' . implode(', ', $errors));
            }
        }

        return $this->render('etudiant/meditation/categorie.html.twig', [
            'categorie' => $categorie,
            'seances' => $seances,
            'posts' => $posts,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/seance/{id}', name: 'etudiant_meditation_seance', methods: ['GET'])]
    public function seance(SeanceMeditation $seance, EntityManagerInterface $entityManager): Response
    {
        // Vérifier que la séance est active
        if (!$seance->isIsActif()) {
            throw $this->createAccessDeniedException('Cette séance n\'est pas disponible.');
        }

        return $this->render('etudiant/meditation/seance.html.twig', [
            'seance' => $seance,
        ]);
    }
}