<?php

namespace App\Controller\Evenement\Admin;

use App\Entity\Evenement;
use App\Enum\StatutEvenement;
use App\Form\Evenement\EvenementType;
use App\Repository\EvenementRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\String\Slugger\SluggerInterface;

/**
 * CRUD des événements..
 */
#[Route('/admin/evenements', name: 'app_admin_evenement_')]
final class EvenementCrudController extends AbstractController
{
    private SluggerInterface $slugger;

    public function __construct(SluggerInterface $slugger)
    {
        $this->slugger = $slugger;
    }
    #[Route('', name: 'index', methods: ['GET'])]
    public function index(Request $request, EvenementRepository $repository): Response
    {
        $q = trim((string) $request->query->get('q', ''));
        $sort = (string) $request->query->get('sort', 'dateDebut');
        $order = (string) $request->query->get('order', 'ASC');
        $evenements = $repository->searchAndSort($q, $sort, $order);

        return $this->render('evenement/admin/evenement_index.html.twig', [
            'route_prefix' => 'app_admin_',
            'space_label' => 'Admin',
            'show_sponsors' => true,
            'evenements' => $evenements,
            'q' => $q,
            'sort' => $sort,
            'order' => $order,
        ]);
    }

    #[Route('/search', name: 'search', methods: ['GET'])]
    public function search(Request $request, EvenementRepository $repository): JsonResponse
    {
        $q = trim((string) $request->query->get('q', ''));
        $type = trim((string) $request->query->get('type', ''));
        $statut = trim((string) $request->query->get('statut', ''));
        $sort = (string) $request->query->get('sort', 'dateDebut');
        $order = (string) $request->query->get('order', 'ASC');

        $dateFromStr = trim((string) $request->query->get('dateFrom', ''));
        $dateToStr = trim((string) $request->query->get('dateTo', ''));
        $dateFrom = $dateFromStr !== '' ? new \DateTimeImmutable($dateFromStr) : null;
        $dateTo = $dateToStr !== '' ? new \DateTimeImmutable($dateToStr) : null;

        $evenements = $repository->searchWithFilters($q, $type, $statut, $dateFrom, $dateTo, $sort, $order);

        $data = array_map(static function (Evenement $e): array {
            return [
                'id' => $e->getId(),
                'titre' => $e->getTitre(),
                'type_label' => $e->getTypeFormate(),
                'statut_label' => $e->getStatutFormate(),
                'date_debut' => $e->getDateDebut()?->format('d/m/Y H:i'),
                'lieu' => $e->getLieu(),
                'capacite_max' => $e->getCapaciteMax(),
                'nombre_inscrits' => $e->getNombreInscrits(),
                'image' => $e->getImage(),
                'organisateur' => $e->getOrganisateur() ? [
                    'id' => $e->getOrganisateur()->getId(),
                    'prenom' => $e->getOrganisateur()->getPrenom(),
                    'nom' => $e->getOrganisateur()->getNom(),
                    'email' => $e->getOrganisateur()->getEmail(),
                ] : null,
            ];
        }, $evenements);

        return $this->json([
            'evenements' => $data,
        ]);
    }

    #[Route('/{id}', name: 'show', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function show(Evenement $evenement): Response
    {
        return $this->render('evenement/admin/evenement_show.html.twig', [
            'route_prefix' => 'app_admin_',
            'space_label' => 'Admin',
            'show_sponsors' => true,
            'evenement' => $evenement,
        ]);
    }

    #[Route('/new', name: 'new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        $evenement = new Evenement();
        $form = $this->createForm(EvenementType::class, $evenement, [
            'validation_groups' => ['Default', 'creation'],
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var UploadedFile|null $imageFile */
            $imageFile = $form->get('imageFile')->getData();
            
            if ($imageFile) {
                $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $this->slugger->slug($originalFilename);
                $newFilename = $safeFilename.'-'.uniqid().'.'.$imageFile->guessExtension();
                
                $imageFile->move(
                    $this->getParameter('kernel.project_dir').'/public/uploadsEvent/evenements',
                    $newFilename
                );
                
                $evenement->setImage($newFilename);
            }
            
            $em->persist($evenement);
            $em->flush();
            $this->addFlash('success', 'L\'événement a été créé.');
            return $this->redirectToRoute('app_admin_evenement_index');
        }

        return $this->render('evenement/admin/evenement_form.html.twig', [
            'route_prefix' => 'app_admin_',
            'space_label' => 'Admin',
            'show_sponsors' => true,
            'evenement' => $evenement,
            'form' => $form,
            'is_edit' => false,
        ]);
    }

    #[Route('/{id}/edit', name: 'edit', requirements: ['id' => '\\d+'], methods: ['GET', 'POST'])]
    public function edit(Request $request, Evenement $evenement, EntityManagerInterface $em, MailerInterface $mailer): Response
    {
        $previousStatut = $evenement->getStatut();

        $form = $this->createForm(EvenementType::class, $evenement, [
            'validation_groups' => ['Default'],
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var UploadedFile|null $imageFile */
            $imageFile = $form->get('imageFile')->getData();
            
            if ($imageFile) {
                $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $this->slugger->slug($originalFilename);
                $newFilename = $safeFilename.'-'.uniqid().'.'.$imageFile->guessExtension();
                
                $imageFile->move(
                    $this->getParameter('kernel.project_dir').'/public/uploadsEvent/evenements',
                    $newFilename
                );
                
                $evenement->setImage($newFilename);
            }
            
            $em->flush();

            if ($previousStatut !== StatutEvenement::ANNULE && $evenement->getStatut() === StatutEvenement::ANNULE) {
                $recipients = [];
                foreach ($evenement->getParticipations() as $participation) {
                    if ($participation->isConfirme() && $participation->getEtudiant()?->getEmail()) {
                        $recipients[] = $participation->getEtudiant()->getEmail();
                    }
                }
                $recipients = array_values(array_unique($recipients));

                $sentCount = 0;
                $failedCount = 0;
                foreach ($recipients as $to) {
                    try {
                        $email = (new Email())
                            ->from('no-reply@unimind.tn')
                            ->to($to)
                            ->subject('Annulation d\'un événement : ' . ($evenement->getTitre() ?? ''))
                            ->text(
                                "Bonjour,\n\n" .
                                "Nous vous informons que l'événement \"" . ($evenement->getTitre() ?? '') . "\" a été annulé.\n" .
                                "Date prévue : " . $evenement->getDateDebutFormatee() . "\n" .
                                "Lieu : " . ($evenement->getLieu() ?? '') . "\n\n" .
                                "Cordialement,\nUniMind"
                            );

                        $mailer->send($email);
                        $sentCount++;
                    } catch (\Throwable $e) {
                        $failedCount++;
                        $this->addFlash('error', 'Erreur mail vers ' . $to . ' : ' . $e->getMessage());
                    }
                }

                if (count($recipients) > 0) {
                    $this->addFlash('success', 'Notification : ' . $sentCount . ' envoyé(s), ' . $failedCount . ' échec(s).');
                } else {
                    $this->addFlash('info', 'Aucun participant confirmé à notifier.');
                }
            }

            $this->addFlash('success', 'L\'événement a été modifié.');
            return $this->redirectToRoute('app_admin_evenement_index');
        }

        return $this->render('evenement/admin/evenement_form.html.twig', [
            'route_prefix' => 'app_admin_',
            'space_label' => 'Admin',
            'show_sponsors' => true,
            'evenement' => $evenement,
            'form' => $form,
            'is_edit' => true,
        ]);
    }

    #[Route('/{id}/delete', name: 'delete', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function delete(Request $request, Evenement $evenement, EntityManagerInterface $em): Response
    {
        $token = $request->request->get('_token');
        if (!$this->isCsrfTokenValid('delete_evenement_' . $evenement->getId(), $token)) {
            $this->addFlash('error', 'Jeton de sécurité invalide.');
            return $this->redirectToRoute('app_admin_evenement_index');
        }
        $em->remove($evenement);
        $em->flush();
        $this->addFlash('success', 'L\'événement a été supprimé.');
        return $this->redirectToRoute('app_admin_evenement_index');
    }
}
