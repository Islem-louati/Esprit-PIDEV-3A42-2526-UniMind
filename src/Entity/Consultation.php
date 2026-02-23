<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'consultation')]
class Consultation
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $consultation_id = null;

    #[ORM\Column(type: 'datetime')]
    private \DateTimeInterface $date_redaction;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $date_modification = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $avis_psy = null;

    #[ORM\Column(type: 'smallint', nullable: true)]
    private ?int $note_satisfaction = null;

    #[ORM\OneToOne(inversedBy: 'consultation', targetEntity: RendezVous::class, cascade: ['persist', 'remove'])]
    #[ORM\JoinColumn(name: 'rendez_vous_id', referencedColumnName: 'rendez_vous_id', nullable: false)]
    private ?RendezVous $rendezVous = null;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'consultationsPsy')]
    #[ORM\JoinColumn(name: 'psy_user_id', referencedColumnName: 'user_id', nullable: false)]
    private ?User $psy = null;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'consultationsEtudiant')]
    #[ORM\JoinColumn(name: 'etudiant_user_id', referencedColumnName: 'user_id', nullable: false)]
    private ?User $etudiant = null;

    public function __construct()
    {
        $this->date_redaction = new \DateTime();
    }

    // Getters et setters
    public function getConsultationId(): ?int
    {
        return $this->consultation_id;
    }

    public function getDateRedaction(): \DateTimeInterface
    {
        return $this->date_redaction;
    }

    public function setDateRedaction(\DateTimeInterface $date_redaction): self
    {
        $this->date_redaction = $date_redaction;
        return $this;
    }

    public function getDateModification(): ?\DateTimeInterface
    {
        return $this->date_modification;
    }

    public function setDateModification(?\DateTimeInterface $date_modification): self
    {
        $this->date_modification = $date_modification;
        return $this;
    }

    public function getAvisPsy(): ?string
    {
        return $this->avis_psy;
    }

    public function setAvisPsy(?string $avis_psy): self
    {
        $this->avis_psy = $avis_psy;
        return $this;
    }

    public function getNoteSatisfaction(): ?int
    {
        return $this->note_satisfaction;
    }

    public function setNoteSatisfaction(?int $note_satisfaction): self
    {
        // Valider que la note est entre 1 et 5 (ou 1 et 10 selon votre besoin)
        if ($note_satisfaction !== null && ($note_satisfaction < 1 || $note_satisfaction > 5)) {
            throw new \InvalidArgumentException('La note de satisfaction doit être entre 1 et 5');
        }
        
        $this->note_satisfaction = $note_satisfaction;
        return $this;
    }

    public function getRendezVous(): ?RendezVous
    {
        return $this->rendezVous;
    }

    public function setRendezVous(?RendezVous $rendezVous): self
    {
        $this->rendezVous = $rendezVous;
        return $this;
    }

    public function getPsy(): ?User
    {
        return $this->psy;
    }

    public function setPsy(?User $psy): self
    {
        // Vérifier que l'utilisateur est bien un psychologue
        if ($psy && !$psy->isPsychologue()) {
            throw new \InvalidArgumentException('L\'utilisateur doit être un psychologue');
        }
        
        $this->psy = $psy;
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
    public function __toString(): string
    {
        return sprintf(
            'Consultation #%d - %s',
            $this->consultation_id,
            $this->etudiant ? $this->etudiant->getFullName() : 'N/A'
        );
    }

    public function updateTimestamp(): self
    {
        $this->date_modification = new \DateTime();
        return $this;
    }

    // Méthode pour obtenir l'évaluation en étoiles
    public function getNoteEtoiles(): ?string
    {
        if ($this->note_satisfaction === null) {
            return null;
        }
        
        return str_repeat('★', $this->note_satisfaction) . str_repeat('☆', 5 - $this->note_satisfaction);
    }

    // Méthode pour vérifier si la consultation a été modifiée
    public function hasBeenModified(): bool
    {
        return $this->date_modification !== null;
    }

    // Méthode pour obtenir la durée depuis la rédaction
    public function getAge(): string
    {
        $now = new \DateTime();
        $interval = $this->date_redaction->diff($now);
        
        if ($interval->y > 0) {
            return $interval->y . ' an(s)';
        } elseif ($interval->m > 0) {
            return $interval->m . ' mois';
        } elseif ($interval->d > 0) {
            return $interval->d . ' jour(s)';
        } elseif ($interval->h > 0) {
            return $interval->h . ' heure(s)';
        } else {
            return $interval->i . ' minute(s)';
        }
    }

    // Méthode pour obtenir les informations du rendez-vous associé
    public function getDateRendezVous(): ?\DateTimeInterface
    {
        return $this->rendezVous ? $this->rendezVous->getDateRendezVous() : null;
    }

    public function getMotifRendezVous(): ?string
    {
        return $this->rendezVous ? $this->rendezVous->getMotif() : null;
    }

    // Méthode pour créer une consultation à partir d'un rendez-vous
    public static function createFromRendezVous(RendezVous $rendezVous, User $psy, ?string $avis = null): self
    {
        $consultation = new self();
        $consultation->setRendezVous($rendezVous);
        $consultation->setPsy($psy);
        $consultation->setEtudiant($rendezVous->getEtudiant());
        $consultation->setAvisPsy($avis);
        
        return $consultation;
    }
}