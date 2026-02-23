<?php

namespace App\Entity;

use App\Enum\StatutParticipation;
use App\Repository\ParticipationRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ParticipationRepository::class)]
#[ORM\Table(name: 'participation', uniqueConstraints: [new ORM\UniqueConstraint(name: 'uniq_participation_evenement_etudiant', columns: ['evenement_id', 'etudiant_id'])])]
#[ORM\HasLifecycleCallbacks]
class Participation
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $participation_id = null; // Clé primaire comme demandé

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $date_inscription = null;

    #[ORM\Column(type: Types::STRING, length: 20)]
    private string $statut = 'attente';

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $created_at = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $updated_at = null;

    #[ORM\Column(type: 'smallint', nullable: true)]
    #[Assert\Range(min: 1, max: 5, notInRangeMessage: 'La note doit être entre {{ min }} et {{ max }}')]
    private ?int $note_satisfaction = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $feedback_commentaire = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $feedback_at = null;

    #[ORM\ManyToOne(targetEntity: Evenement::class, inversedBy: 'participations')]
    #[ORM\JoinColumn(name: 'evenement_id', referencedColumnName: 'evenement_id', nullable: false)]
    #[Assert\NotNull(message: "L'événement est obligatoire")]
    private ?Evenement $evenement = null;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'participations')]
    #[ORM\JoinColumn(name: 'etudiant_id', referencedColumnName: 'user_id', nullable: false)]
    #[Assert\NotNull(message: "L'étudiant est obligatoire")]
    private ?User $etudiant = null;

    public function __construct()
    {
        $this->date_inscription = new \DateTime();
        $this->created_at = new \DateTime();
    }

    // Getters et Setters
    public function getId(): ?int
    {
        return $this->participation_id;
    }

    public function getParticipationId(): ?int
    {
        return $this->participation_id;
    }

    public function getDateInscription(): ?\DateTimeInterface
    {
        return $this->date_inscription;
    }

    public function setDateInscription(\DateTimeInterface $date_inscription): self
    {
        $this->date_inscription = $date_inscription;
        return $this;
    }

    public function getStatut(): StatutParticipation
    {
        return StatutParticipation::from($this->statut);
    }

    public function setStatut(StatutParticipation $statut): self
    {
        $this->statut = $statut->value;
        return $this;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->created_at;
    }

    public function setCreatedAt(\DateTimeInterface $created_at): self
    {
        $this->created_at = $created_at;
        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeInterface
    {
        return $this->updated_at;
    }

    public function setUpdatedAt(?\DateTimeInterface $updated_at): self
    {
        $this->updated_at = $updated_at;
        return $this;
    }

    public function getNoteSatisfaction(): ?int
    {
        return $this->note_satisfaction;
    }

    public function setNoteSatisfaction(?int $note_satisfaction): self
    {
        $this->note_satisfaction = $note_satisfaction;
        return $this;
    }

    public function getFeedbackCommentaire(): ?string
    {
        return $this->feedback_commentaire;
    }

    public function setFeedbackCommentaire(?string $feedback_commentaire): self
    {
        $this->feedback_commentaire = $feedback_commentaire;
        return $this;
    }

    public function getFeedbackAt(): ?\DateTimeInterface
    {
        return $this->feedback_at;
    }

    public function setFeedbackAt(?\DateTimeInterface $feedback_at): self
    {
        $this->feedback_at = $feedback_at;
        return $this;
    }

    public function getEvenement(): ?Evenement
    {
        return $this->evenement;
    }

    public function setEvenement(?Evenement $evenement): self
    {
        $this->evenement = $evenement;
        return $this;
    }

    public function getEtudiant(): ?User
    {
        return $this->etudiant;
    }

    public function setEtudiant(?User $etudiant): self
    {
        // Vérifier que l'utilisateur est bien un étudiant
        if ($etudiant && !$etudiant->isEtudiant()) {
            throw new \InvalidArgumentException('L\'utilisateur doit être un étudiant');
        }

        $this->etudiant = $etudiant;
        return $this;
    }

    // Méthodes utilitaires
    #[ORM\PreUpdate]
    public function updateTimestamp(): void
    {
        $this->updated_at = new \DateTime();
    }

    public function isConfirme(): bool
    {
        return $this->statut === 'confirme';
    }

    public function isAttente(): bool
    {
        return $this->statut === 'attente';
    }

    public function isAnnule(): bool
    {
        return $this->statut === 'annule';
    }

    public function confirmer(): self
    {
        $this->statut = 'confirme';
        $this->updateTimestamp();
        return $this;
    }

    public function annuler(): self
    {
        $this->statut = 'annule';
        $this->updateTimestamp();

        // Décrémenter le nombre d'inscrits dans l'événement
        if ($this->evenement && $this->isConfirme()) {
            $this->evenement->decrementNombreInscrits();
        }

        return $this;
    }

    public function mettreEnAttente(): self
    {
        $this->statut = 'attente';
        $this->updateTimestamp();
        return $this;
    }

    public function hasFeedback(): bool
    {
        return $this->note_satisfaction !== null || ($this->feedback_commentaire !== null && trim($this->feedback_commentaire) !== '');
    }

    public function canGiveFeedback(): bool
    {
        if (!$this->evenement) {
            return false;
        }

        if (!$this->isConfirme()) {
            return false;
        }

        if (!$this->evenement->isTermine()) {
            return false;
        }

        if ($this->hasFeedback()) {
            return false;
        }

        return true;
    }

    public function __toString(): string
    {
        return sprintf(
            'Participation #%d - %s',
            $this->participation_id,
            $this->etudiant ? $this->etudiant->getFullName() : 'N/A'
        );
    }

    // Méthode pour créer une participation
    public static function create(Evenement $evenement, User $etudiant): self
    {
        $participation = new self();
        $participation->setEvenement($evenement);
        $participation->setEtudiant($etudiant);

        // Si l'événement a encore des places disponibles, confirmer directement
        if ($evenement->getPlacesDisponibles() > 0) {
            $participation->confirmer();
            $evenement->incrementNombreInscrits();
        }

        return $participation;
    }
}
