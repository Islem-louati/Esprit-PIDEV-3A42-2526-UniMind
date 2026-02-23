<?php

namespace App\Controller\Admin;

use App\Entity\Question;
use App\Entity\Questionnaire;
use App\Form\QuestionType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin/question')]  // ← Important : c'est /question, pas /questionnaire
class QuestionController extends AbstractController  // ← Le nom de la classe doit être QuestionController
{
    #[Route('/', name: 'admin_question_index', methods: ['GET'])]
    public function index(Request $request, EntityManagerInterface $em): Response
    {
        $questionnaireId = $request->query->get('questionnaire_id');
        $search = $request->query->get('search');

        $qb = $em->getRepository(Question::class)->createQueryBuilder('q')
            ->leftJoin('q.questionnaire', 'quest')
            ->addSelect('quest')
            ->orderBy('q.created_at', 'DESC');

        if ($questionnaireId) {
            $qb->where('q.questionnaire = :questionnaireId')
               ->setParameter('questionnaireId', $questionnaireId);
        }

        if ($search) {
            $qb->andWhere('q.texte LIKE :search')
               ->setParameter('search', '%' . $search . '%');
        }

        $questions = $qb->getQuery()->getResult();
        $questionnaires = $em->getRepository(Questionnaire::class)->findAll();

        return $this->render('admin/question/index.html.twig', [
            'questions' => $questions,
            'questionnaires' => $questionnaires,
            'search' => $search,
            'selected_questionnaire' => $questionnaireId,
        ]);
    }

    #[Route('/new', name: 'admin_question_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        $questionnaireId = $request->query->get('questionnaire_id');

        if (!$questionnaireId) {
            $this->addFlash('error', 'Veuillez sélectionner un questionnaire');
            return $this->redirectToRoute('admin_question_index');
        }

        $questionnaire = $em->getRepository(Questionnaire::class)->find($questionnaireId);

        if (!$questionnaire) {
            $this->addFlash('error', 'Questionnaire non trouvé');
            return $this->redirectToRoute('admin_question_index');
        }

        $question = new Question();
        $question->setQuestionnaire($questionnaire);

        if ($request->isMethod('GET')) {
            $question->setOptionsQuest(['Jamais', 'Rarement', 'Parfois', 'Souvent', 'Toujours']);
            $question->setScoreOptions([0, 1, 2, 3, 4]);
        }

        $form = $this->createForm(QuestionType::class, $question);
        $form->handleRequest($request);

        if ($form->isSubmitted() && !$form->isValid()) {
            $this->addFlash('error', 'Le champ « Texte de la question » ou des options sont vides ou invalides. Veuillez corriger les erreurs affichées ci-dessous.');
        }

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $em->persist($question);
                $em->flush();

                $this->addFlash('success', 'Question ajoutée avec succès');

                if ($request->request->get('add_another')) {
                    return $this->redirectToRoute('admin_question_new', [
                        'questionnaire_id' => $questionnaire->getId()
                    ]);
                }

                return $this->redirectToRoute('admin_question_index', [
                    'questionnaire_id' => $questionnaire->getId()
                ]);

            } catch (\Exception $e) {
                $this->addFlash('error', 'Erreur lors de l\'ajout de la question: ' . $e->getMessage());
            }
        }

        return $this->render('admin/question/new.html.twig', [
            'form' => $form->createView(),
            'questionnaire' => $questionnaire,
        ]);
    }

    #[Route('/{id}', name: 'admin_question_show', methods: ['GET'])]
    public function show(Question $question): Response
    {
        return $this->render('admin/question/show.html.twig', [
            'question' => $question,
        ]);
    }

    #[Route('/{id}/edit', name: 'admin_question_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Question $question, EntityManagerInterface $em): Response
    {
        $form = $this->createForm(QuestionType::class, $question);
        $form->handleRequest($request);

        if ($form->isSubmitted() && !$form->isValid()) {
            $this->addFlash('error', 'Des champs sont vides ou invalides. Veuillez corriger les erreurs affichées ci-dessous.');
        }

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $question->setUpdatedAt(new \DateTime());
                $em->flush();

                $this->addFlash('success', 'Question modifiée avec succès');
                return $this->redirectToRoute('admin_question_show', [
                    'id' => $question->getId()
                ]);

            } catch (\Exception $e) {
                $this->addFlash('error', 'Erreur lors de la modification: ' . $e->getMessage());
            }
        }

        return $this->render('admin/question/edit.html.twig', [
            'form' => $form->createView(),
            'question' => $question,
        ]);
    }

    #[Route('/{id}', name: 'admin_question_delete', methods: ['POST'])]
    public function delete(Request $request, Question $question, EntityManagerInterface $em): Response
    {
        $questionnaireId = $question->getQuestionnaire()->getId();

        if ($this->isCsrfTokenValid('delete' . $question->getId(), $request->request->get('_token'))) {
            try {
                $em->remove($question);
                $em->flush();
                $this->addFlash('success', 'Question supprimée avec succès');
            } catch (\Exception $e) {
                $this->addFlash('error', 'Impossible de supprimer cette question. Elle est probablement utilisée dans des réponses.');
            }
        }

        return $this->redirectToRoute('admin_question_index', [
            'questionnaire_id' => $questionnaireId
        ]);
    }
}