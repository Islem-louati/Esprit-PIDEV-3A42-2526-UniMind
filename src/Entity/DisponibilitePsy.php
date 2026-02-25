<?php

namespace App\Entity;

use App\Enum\StatutDisponibilite;
use App\Enum\TypeConsultation;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'disponibilite_psy')]
class DisponibilitePsy
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $dispo_id = null;

    #[ORM\Column(type: 'date')]
    private \DateTimeInterface $date_dispo;

    #[ORM\Column(type: 'time')]
    private \DateTimeInterface $heure_debut;

    #[ORM\Column(type: 'time')]
    private \DateTimeInterface $heure_fin;

    #[ORM\Column(type: 'string', length: 20)]
    private string $type_consult;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $lieu = null;

    #[ORM\Column(type: 'string', length: 20)]
    private string $statut;

    #[ORM\Column(type: 'datetime')]
    private \DateTimeInterface $created_at;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $updated_at = null;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'disponibilites')]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'user_id', nullable: false)]
    private ?User $user = null;

    // Ajoutez cette propriété
  #[ORM\OneToOne(mappedBy: 'disponibilite', targetEntity: RendezVous::class)]
  private ?RendezVous $rendezVous = null;


// Ajoutez ces méthodes
public function getRendezVous(): ?RendezVous
{
    return $this->rendezVous;
}

public function setRendezVous(?RendezVous $rendezVous): self
{
    // unset the owning side of the relation if necessary
    if ($rendezVous === null && $this->rendezVous !== null) {
        $this->rendezVous->setDisponibilite(null);
    }

    // set the owning side of the relation if necessary
    if ($rendezVous !== null && $rendezVous->getDisponibilite() !== $this) {
        $rendezVous->setDisponibilite($this);
    }

    $this->rendezVous = $rendezVous;
    return $this;
}

// Méthode pour vérifier si la disponibilité est réservée
public function isReserved(): bool
{
    return $this->rendezVous !== null && $this->rendezVous->isConfirme();
}

    public function __construct()
    {
        $this->created_at = new \DateTime();
        $this->statut = StatutDisponibilite::DISPONIBLE->value;
        $this->type_consult = TypeConsultation::PRESENTIEL->value;
    }

    // Getters et setters
    public function getDispoId(): ?int
    {
        return $this->dispo_id;
    }

    public function getDateDispo(): \DateTimeInterface
    {
        return $this->date_dispo;
    }

    public function setDateDispo(\DateTimeInterface $date_dispo): self
    {
        $this->date_dispo = $date_dispo;
        return $this;
    }

    public function getHeureDebut(): \DateTimeInterface
    {
        return $this->heure_debut;
    }

    public function setHeureDebut(\DateTimeInterface $heure_debut): self
    {
        $this->heure_debut = $heure_debut;
        return $this;
    }

    public function getHeureFin(): \DateTimeInterface
    {
        return $this->heure_fin;
    }

    public function setHeureFin(\DateTimeInterface $heure_fin): self
    {
        $this->heure_fin = $heure_fin;
        return $this;
    }

    public function getTypeConsult(): string
    {
        return $this->type_consult;
    }

    public function setTypeConsult(string $type_consult): self
    {
        if (!in_array($type_consult, TypeConsultation::getValues(), true)) {
            throw new \InvalidArgumentException('Type de consultation invalide');
        }
        $this->type_consult = $type_consult;
        return $this;
    }

    public function getTypeConsultEnum(): TypeConsultation
    {
        return TypeConsultation::from($this->type_consult);
    }

    public function setTypeConsultEnum(TypeConsultation $typeConsult): self
    {
        $this->type_consult = $typeConsult->value;
        return $this;
    }

    public function getLieu(): ?string
    {
        return $this->lieu;
    }

    public function setLieu(?string $lieu): self
    {
        $this->lieu = $lieu;
        return $this;
    }

    public function getStatut(): string
    {
        return $this->statut;
    }

    public function setStatut(string $statut): self
    {
        if (!in_array($statut, StatutDisponibilite::getValues(), true)) {
            throw new \InvalidArgumentException('Statut invalide');
        }
        $this->statut = $statut;
        return $this;
    }

    public function getStatutEnum(): StatutDisponibilite
    {
        return StatutDisponibilite::from($this->statut);
    }

    public function setStatutEnum(StatutDisponibilite $statut): self
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

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): self
    {
        $this->user = $user;
        return $this;
    }

    // Méthodes utilitaires
    public function __toString(): string
    {
        return sprintf(
            'Disponibilité du %s de %s à %s',
            $this->date_dispo->format('d/m/Y'),
            $this->heure_debut->format('H:i'),
            $this->heure_fin->format('H:i')
        );
    }

    public function getDuree(): string
    {
        $debut = $this->heure_debut;
        $fin = $this->heure_fin;
        
        $interval = $debut->diff($fin);
        return $interval->format('%H:%I');
    }

    public function isDisponible(): bool
    {
        return $this->statut === StatutDisponibilite::DISPONIBLE->value;
    }

    public function isReserve(): bool
    {
        return $this->statut === StatutDisponibilite::RESERVE->value;
    }

    public function isAnnule(): bool
    {
        return $this->statut === StatutDisponibilite::ANNULE->value;
    }

    public function isPresentiel(): bool
    {
        return $this->type_consult === TypeConsultation::PRESENTIEL->value;
    }

    public function isEnLigne(): bool
    {
        return $this->type_consult === TypeConsultation::EN_LIGNE->value;
    }

    public function getDateTimeDebut(): \DateTimeInterface
    {
        return \DateTime::createFromFormat(
            'Y-m-d H:i:s',
            $this->date_dispo->format('Y-m-d') . ' ' . $this->heure_debut->format('H:i:s')
        );
    }

    public function getDateTimeFin(): \DateTimeInterface
    {
        return \DateTime::createFromFormat(
            'Y-m-d H:i:s',
            $this->date_dispo->format('Y-m-d') . ' ' . $this->heure_fin->format('H:i:s')
        );
    }

    public function isPassed(): bool
    {
        return $this->getDateTimeFin() < new \DateTime();
    }

    public function isNow(): bool
    {
        $now = new \DateTime();
        return $this->getDateTimeDebut() <= $now && $this->getDateTimeFin() >= $now;
    }

    public function updateTimestamp(): self
    {
        $this->updated_at = new \DateTime();
        return $this;
    }

    // Validation des heures
    public function validateHeures(): bool
    {
        return $this->heure_debut < $this->heure_fin;
    }
}