<?php

namespace App\Entity;

use App\Enum\StatutRendezVous;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

#[ORM\Entity]
#[ORM\Table(name: 'rendez_vous')]
class RendezVous
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $rendez_vous_id = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $motif = null;

    #[ORM\Column(type: 'string', length: 20)]
    private string $statut;

    #[ORM\Column(type: 'datetime')]
    private \DateTimeInterface $created_at;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $updated_at = null;

   #[ORM\OneToOne(targetEntity: DisponibilitePsy::class, inversedBy: 'rendezVous')]
    #[ORM\JoinColumn(name: 'dispo_id', referencedColumnName: 'dispo_id', nullable: false)]
    private ?DisponibilitePsy $disponibilite = null;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'rendezVousEtudiant')]
    #[ORM\JoinColumn(name: 'etudiant_id', referencedColumnName: 'user_id', nullable: false)]
    private ?User $etudiant = null;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'rendezVousPsy')]
    #[ORM\JoinColumn(name: 'psy_id', referencedColumnName: 'user_id', nullable: false)]
    private ?User $psy = null;

    #[ORM\OneToOne(mappedBy: 'rendezVous', targetEntity: Consultation::class, cascade: ['persist', 'remove'])]
    private ?Consultation $consultation = null;

    public function __construct()
    {
        $this->created_at = new \DateTime();
        $this->statut = StatutRendezVous::DEMANDE->value;
    }

    // Getters et setters
    public function getRendezVousId(): ?int
    {
        return $this->rendez_vous_id;
    }

    public function getMotif(): ?string
    {
        return $this->motif;
    }

    public function setMotif(?string $motif): self
    {
        $this->motif = $motif;
        return $this;
    }

    public function getStatut(): string
    {
        return $this->statut;
    }

    public function setStatut(string $statut): self
    {
        if (!in_array($statut, StatutRendezVous::getValues(), true)) {
            throw new \InvalidArgumentException('Statut de rendez-vous invalide');
        }
        $this->statut = $statut;
        return $this;
    }

    public function getStatutEnum(): StatutRendezVous
    {
        return StatutRendezVous::from($this->statut);
    }

    public function setStatutEnum(StatutRendezVous $statut): self
    {
        $this->statut = $statut->value;
        return $this;
    }

    public function getCreatedAt(): \DateTimeInterface
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

    public function getDisponibilite(): ?DisponibilitePsy
    {
        return $this->disponibilite;
    }

    public function setDisponibilite(?DisponibilitePsy $disponibilite): self
    {
        $this->disponibilite = $disponibilite;
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

    public function getConsultation(): ?Consultation
    {
        return $this->consultation;
    }

    public function setConsultation(?Consultation $consultation): self
    {
        // unset the owning side of the relation if necessary
        if ($consultation === null && $this->consultation !== null) {
            $this->consultation->setRendezVous(null);
        }

        // set the owning side of the relation if necessary
        if ($consultation !== null && $consultation->getRendezVous() !== $this) {
            $consultation->setRendezVous($this);
        }

        $this->consultation = $consultation;
        return $this;
    }

    // Méthodes utilitaires
    public function __toString(): string
    {
        return sprintf(
            'Rendez-vous #%d - %s',
            $this->rendez_vous_id,
            $this->etudiant ? $this->etudiant->getFullName() : 'N/A'
        );
    }

    public function updateTimestamp(): self
    {
        $this->updated_at = new \DateTime();
        return $this;
    }

    // Méthodes pour vérifier le statut
    public function isDemande(): bool
    {
        return $this->statut === StatutRendezVous::DEMANDE->value;
    }

    public function isConfirme(): bool
    {
        return $this->statut === StatutRendezVous::CONFIRME->value;
    }

    public function isEnCours(): bool
    {
        return $this->statut === StatutRendezVous::EN_COURS->value;
    }

    public function isTermine(): bool
    {
        return $this->statut === StatutRendezVous::TERMINE->value;
    }

    public function isAnnule(): bool
    {
        return $this->statut === StatutRendezVous::ANNULE->value;
    }

    public function isAbsent(): bool
    {
        return $this->statut === StatutRendezVous::ABSENT->value;
    }

    // Méthodes de transition de statut
    public function confirmer(): self
    {
        if (!$this->isDemande()) {
            throw new \LogicException('Seuls les rendez-vous en demande peuvent être confirmés');
        }
        
        $this->setStatutEnum(StatutRendezVous::CONFIRME);
        $this->updateTimestamp();
        return $this;
    }

    public function commencer(): self
    {
        if (!$this->isConfirme()) {
            throw new \LogicException('Seuls les rendez-vous confirmés peuvent être commencés');
        }
        
        $this->setStatutEnum(StatutRendezVous::EN_COURS);
        $this->updateTimestamp();
        return $this;
    }

    public function terminer(): self
    {
        if (!$this->isEnCours()) {
            throw new \LogicException('Seuls les rendez-vous en cours peuvent être terminés');
        }
        
        // Si une consultation existe, on peut terminer le rendez-vous
        if (!$this->hasConsultation()) {
            throw new \LogicException('Un rendez-vous ne peut être terminé sans consultation');
        }
        
        $this->setStatutEnum(StatutRendezVous::TERMINE);
        $this->updateTimestamp();
        return $this;
    }

    public function annuler(): self
    {
        if ($this->isTermine() || $this->isAbsent()) {
            throw new \LogicException('Un rendez-vous terminé ou absent ne peut pas être annulé');
        }
        
        $this->setStatutEnum(StatutRendezVous::ANNULE);
        $this->updateTimestamp();
        return $this;
    }

    public function marquerAbsent(): self
    {
        if ($this->isTermine() || $this->isAnnule()) {
            throw new \LogicException('Un rendez-vous terminé ou annulé ne peut pas être marqué absent');
        }
        
        $this->setStatutEnum(StatutRendezVous::ABSENT);
        $this->updateTimestamp();
        return $this;
    }

    // Méthodes pour obtenir les informations de la disponibilité
    public function getDateRendezVous(): ?\DateTimeInterface
    {
        return $this->disponibilite ? $this->disponibilite->getDateDispo() : null;
    }

    public function getHeureDebut(): ?\DateTimeInterface
    {
        return $this->disponibilite ? $this->disponibilite->getHeureDebut() : null;
    }

    public function getHeureFin(): ?\DateTimeInterface
    {
        return $this->disponibilite ? $this->disponibilite->getHeureFin() : null;
    }

    public function getTypeConsultation(): ?string
    {
        return $this->disponibilite ? $this->disponibilite->getTypeConsult() : null;
    }

    public function getTypeConsultationEnum(): ?\App\Enum\TypeConsultation
    {
        return $this->disponibilite ? $this->disponibilite->getTypeConsultEnum() : null;
    }

    public function getLieu(): ?string
    {
        return $this->disponibilite ? $this->disponibilite->getLieu() : null;
    }

    // Méthode pour vérifier si le rendez-vous est passé
    public function isPassed(): bool
    {
        if (!$this->disponibilite) {
            return false;
        }
        
        return $this->disponibilite->getDateTimeFin() < new \DateTime();
    }

    // Méthode pour vérifier si le rendez-vous est en cours
    public function isNow(): bool
    {
        if (!$this->disponibilite) {
            return false;
        }
        
        return $this->disponibilite->isNow();
    }

    // Méthode pour vérifier si le rendez-vous a une consultation
    public function hasConsultation(): bool
    {
        return $this->consultation !== null;
    }

    // Méthode pour vérifier si le rendez-vous est éligible pour une consultation
    public function canHaveConsultation(): bool
    {
        return $this->isTermine() || $this->isAbsent();
    }

    // Méthode pour obtenir la date et heure complète du début
    public function getDateTimeDebut(): ?\DateTimeInterface
    {
        if (!$this->disponibilite) {
            return null;
        }
        
        return \DateTime::createFromFormat(
            'Y-m-d H:i:s',
            $this->disponibilite->getDateDispo()->format('Y-m-d') . ' ' . 
            $this->disponibilite->getHeureDebut()->format('H:i:s')
        );
    }

    // Méthode pour obtenir la date et heure complète de fin
    public function getDateTimeFin(): ?\DateTimeInterface
    {
        if (!$this->disponibilite) {
            return null;
        }
        
        return \DateTime::createFromFormat(
            'Y-m-d H:i:s',
            $this->disponibilite->getDateDispo()->format('Y-m-d') . ' ' . 
            $this->disponibilite->getHeureFin()->format('H:i:s')
        );
    }

    // Méthode pour obtenir la durée du rendez-vous
    public function getDuree(): ?string
    {
        if (!$this->disponibilite) {
            return null;
        }
        
        return $this->disponibilite->getDuree();
    }

    // Méthode pour obtenir le statut sous forme lisible
    public function getStatutLabel(): string
    {
        return $this->getStatutEnum()->getLabel();
    }

    // Méthode pour vérifier si le rendez-vous peut être modifié
    public function canBeModified(): bool
    {
        return !$this->isTermine() && !$this->isAnnule() && !$this->isAbsent();
    }

    // Méthode pour vérifier si le rendez-vous peut être annulé
    public function canBeCancelled(): bool
    {
        return $this->canBeModified() && !$this->isEnCours();
    }

    // Méthode pour obtenir un résumé du rendez-vous
    public function getResume(): string
    {
        return sprintf(
            "Rendez-vous #%d\n" .
            "Patient: %s\n" .
            "Psychologue: %s\n" .
            "Date: %s\n" .
            "Heure: %s - %s\n" .
            "Type: %s\n" .
            "Statut: %s",
            $this->rendez_vous_id,
            $this->etudiant ? $this->etudiant->getFullName() : 'Non défini',
            $this->psy ? $this->psy->getFullName() : 'Non défini',
            $this->getDateRendezVous() ? $this->getDateRendezVous()->format('d/m/Y') : 'Non défini',
            $this->getHeureDebut() ? $this->getHeureDebut()->format('H:i') : 'Non défini',
            $this->getHeureFin() ? $this->getHeureFin()->format('H:i') : 'Non défini',
            $this->getTypeConsultation() ?? 'Non défini',
            $this->getStatutLabel()
        );
    }

    // Méthode pour créer un rendez-vous à partir d'une disponibilité
    public static function createFromDisponibilite(
        DisponibilitePsy $disponibilite,
        User $etudiant,
        User $psy,
        ?string $motif = null
    ): self {
        $rendezVous = new self();
        $rendezVous->setDisponibilite($disponibilite);
        $rendezVous->setEtudiant($etudiant);
        $rendezVous->setPsy($psy);
        $rendezVous->setMotif($motif);
        
        return $rendezVous;
    }
}