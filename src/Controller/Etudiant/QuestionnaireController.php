<?php
// src/Controller/Etudiant/QuestionnaireController.php

namespace App\Controller\Etudiant;

use App\Entity\Questionnaire;
use App\Entity\ReponseQuestionnaire;
use App\Entity\Question;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/etudiant/questionnaire')]
class QuestionnaireController extends AbstractController
{
    #[Route('/liste', name: 'etudiant_questionnaire_liste')]
    public function liste(Request $request, EntityManagerInterface $em): Response
    {
        $typeSelectionne = $request->query->get('type', 'tous');
        
        $qb = $em->getRepository(Questionnaire::class)
            ->createQueryBuilder('q')
            ->where('q.nbre_questions > 0');
        
        if ($typeSelectionne !== 'tous' && $typeSelectionne !== '') {
            $qb->andWhere('q.type = :type')
               ->setParameter('type', strtoupper($typeSelectionne));
        }
        
        $questionnaires = $qb->orderBy('q.nom', 'ASC')->getQuery()->getResult();
        
        // ✅ MODIFICATION 1: Supprimer la dépendance à l'utilisateur connecté
        $questionnairesCompletesIds = []; // Par défaut, aucun questionnaire n'est complété
        
        $typesDisponibles = [];
        $typesQuery = $em->createQuery('SELECT DISTINCT q.type FROM App\Entity\Questionnaire q WHERE q.nbre_questions > 0 ORDER BY q.type');
        $typesResult = $typesQuery->getResult();
        
        foreach ($typesResult as $type) {
            if (isset($type['type'])) {
                $typesDisponibles[] = $type['type'];
            }
        }
        
        return $this->render('etudiant/questionnaire/liste.html.twig', [
            'questionnaires' => $questionnaires,
            'type_selectionne' => $typeSelectionne,
            'types_disponibles' => $typesDisponibles,
            'questionnaires_completes_ids' => $questionnairesCompletesIds, // Maintenant toujours vide
        ]);
    }
    
    #[Route('/passer/{id}', name: 'etudiant_questionnaire_passer')]
    public function passer(Questionnaire $questionnaire, EntityManagerInterface $em): Response
    {
        if ($questionnaire->getNbreQuestions() === 0 || $questionnaire->getQuestions()->count() === 0) {
            $this->addFlash('warning', 'Ce questionnaire n\'a pas encore de questions.');
            return $this->redirectToRoute('etudiant_questionnaire_liste');
        }
        
        // ✅ MODIFICATION 2: Supprimer la vérification "déjà répondu" car pas d'utilisateur
        
        $questions = $em->getRepository(Question::class)
            ->createQueryBuilder('q')
            ->where('q.questionnaire = :questionnaire')
            ->setParameter('questionnaire', $questionnaire)
            ->orderBy('q.question_id', 'ASC')
            ->getQuery()
            ->getResult();
        
        return $this->render('etudiant/questionnaire/passer.html.twig', [
            'questionnaire' => $questionnaire,
            'questions' => $questions,
        ]);
    }
    
    #[Route('/submit/{id}', name: 'etudiant_questionnaire_submit', methods: ['POST'])]
    public function submitQuestionnaire(Request $request, Questionnaire $questionnaire, EntityManagerInterface $em): Response
    {
        // ✅ MODIFICATION 3: Plus besoin d'utilisateur connecté
        // $etudiant = $this->getUser(); // SUPPRIMÉ
        
        $reponsesData = $request->request->all('reponses');
        
        if (empty($reponsesData)) {
            $this->addFlash('error', 'Aucune réponse n\'a été soumise.');
            return $this->redirectToRoute('etudiant_questionnaire_passer', ['id' => $questionnaire->getId()]);
        }
        
        // ✅ MODIFICATION 4: Créer la réponse SANS étudiant
        $reponse = ReponseQuestionnaire::createFromResponses(
            $questionnaire,
            null, // ✅ Pas d'étudiant
            $reponsesData,
            null,
            sprintf('Soumis le %s', date('d/m/Y à H:i'))
        );
        
        $em->persist($reponse);
        $em->flush();
        
        $idSauvegarde = $reponse->getReponseQuestionnaireId();
        
        $this->addFlash('success', '✅ Questionnaire soumis avec succès !');
        
        return $this->redirectToRoute('etudiant_mes_reponses_detail', [
            'id' => $idSauvegarde
        ]);
    }
    
    #[Route('/detail/{id}', name: 'etudiant_questionnaire_detail')]
    public function detail(Questionnaire $questionnaire, EntityManagerInterface $em): Response
    {
        if ($questionnaire->getNbreQuestions() === 0) {
            $this->addFlash('warning', 'Ce questionnaire n\'a pas encore de questions.');
            return $this->redirectToRoute('etudiant_questionnaire_liste');
        }
        
        $statistiques = $questionnaire->getStatistiquesReponses();
        
        $questions = $em->getRepository(Question::class)
            ->createQueryBuilder('q')
            ->where('q.questionnaire = :questionnaire')
            ->setParameter('questionnaire', $questionnaire)
            ->orderBy('q.question_id', 'ASC')
            ->getQuery()
            ->getResult();
        
        return $this->render('etudiant/questionnaire/detail.html.twig', [
            'questionnaire' => $questionnaire,
            'questions' => $questions,
            'statistiques' => $statistiques,
        ]);
    }
}