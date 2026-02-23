<?php

namespace App\Entity;

use App\Repository\EvenementRepository;
use App\Enum\TypeEvenement;
use App\Enum\StatutEvenement;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use App\Enum\TypeContribution;

#[ORM\Entity(repositoryClass: EvenementRepository::class)]
#[ORM\Table(name: 'evenement')]
#[ORM\HasLifecycleCallbacks]
class Evenement
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'evenement_id', type: Types::INTEGER)]
    private ?int $evenement_id = null;

    #[ORM\Column(type: Types::STRING, length: 200)]
    #[Assert\NotBlank(message: "Le titre est obligatoire")]
    #[Assert\Length(
        max: 200,
        maxMessage: "Le titre ne peut pas dépasser {{ limit }} caractères"
    )]
    private ?string $titre = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(type: Types::STRING, length: 50)]
    #[Assert\NotBlank(message: "Le type est obligatoire")]
    #[Assert\Choice(callback: [TypeEvenement::class, 'getValues'], message: "Type d'événement invalide")]
    private string $type = TypeEvenement::ATELIER->value;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    #[Assert\NotBlank(message: "La date de début est obligatoire")]
    #[Assert\GreaterThan(
        value: "now",
        message: "La date de début doit être dans le futur",
        groups: ["creation"]
    )]
    private ?\DateTimeInterface $dateDebut = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    #[Assert\NotBlank(message: "La date de fin est obligatoire")]
    #[Assert\GreaterThan(
        propertyPath: "dateDebut",
        message: "La date de fin doit être après la date de début"
    )]
    private ?\DateTimeInterface $dateFin = null;

    #[ORM\Column(type: Types::STRING, length: 255)]
    #[Assert\NotBlank(message: "Le lieu est obligatoire")]
    private ?string $lieu = null;

    #[ORM\Column(type: Types::INTEGER)]
    #[Assert\NotBlank(message: "La capacité maximale est obligatoire")]
    #[Assert\Positive(message: "La capacité doit être supérieure à 0")]
    private ?int $capaciteMax = 30;

    #[ORM\Column(type: Types::INTEGER)]
    private int $nombreInscrits = 0;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'evenementsOrganises')]
    #[ORM\JoinColumn(name: 'organisateur_id', referencedColumnName: 'user_id', nullable: false)]
    #[Assert\NotNull(message: "L'organisateur est obligatoire")]
    private ?User $organisateur = null;

    #[ORM\Column(type: Types::STRING, length: 20)]
    #[Assert\Choice(callback: [StatutEvenement::class, 'getValues'], message: "Statut d'événement invalide")]
    private string $statut = StatutEvenement::A_VENIR->value;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $dateCreation = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    #[Assert\LessThanOrEqual(
        propertyPath: "dateDebut",
        message: "La date limite d'inscription doit être avant le début de l'événement"
    )]
    private ?\DateTimeInterface $dateLimiteInscription = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $updatedAt = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $image = null;

    // Relations
    #[ORM\OneToMany(mappedBy: 'evenement', targetEntity: Participation::class, cascade: ['remove'], orphanRemoval: true)]
    private Collection $participations;

    #[ORM\OneToMany(mappedBy: 'evenement', targetEntity: EvenementSponsor::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $evenementSponsors;

    public function __construct()
    {
        $this->participations = new ArrayCollection();
        $this->evenementSponsors = new ArrayCollection();
        $this->dateCreation = new \DateTime();
    }

    // Getters et Setters

    public function getId(): ?int
    {
        return $this->evenement_id;
    }

    public function getEvenementId(): ?int
    {
        return $this->evenement_id;
    }

    public function getTitre(): ?string
    {
        return $this->titre;
    }

    public function setTitre(string $titre): self
    {
        $this->titre = $titre;
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;
        return $this;
    }

    public function getType(): TypeEvenement
    {
        return TypeEvenement::from($this->type);
    }

    public function setType(TypeEvenement $type): self
    {
        $this->type = $type->value;
        return $this;
    }

    public function getDateDebut(): ?\DateTimeInterface
    {
        return $this->dateDebut;
    }

    public function setDateDebut(?\DateTimeInterface $dateDebut): self
    {
        $this->dateDebut = $dateDebut;
        return $this;
    }

    public function getDateFin(): ?\DateTimeInterface
    {
        return $this->dateFin;
    }

    public function setDateFin(?\DateTimeInterface $dateFin): self
    {
        $this->dateFin = $dateFin;
        return $this;
    }

    public function getLieu(): ?string
    {
        return $this->lieu;
    }

    public function setLieu(string $lieu): self
    {
        $this->lieu = $lieu;
        return $this;
    }

    public function getCapaciteMax(): ?int
    {
        return $this->capaciteMax;
    }

    public function setCapaciteMax(int $capaciteMax): self
    {
        $this->capaciteMax = $capaciteMax;
        return $this;
    }

    public function getNombreInscrits(): int
    {
        return $this->nombreInscrits;
    }

    public function setNombreInscrits(int $nombreInscrits): self
    {
        $this->nombreInscrits = $nombreInscrits;
        return $this;
    }

    public function incrementNombreInscrits(): self
    {
        $this->nombreInscrits++;
        return $this;
    }

    public function decrementNombreInscrits(): self
    {
        if ($this->nombreInscrits > 0) {
            $this->nombreInscrits--;
        }
        return $this;
    }

    public function getOrganisateur(): ?User
    {
        return $this->organisateur;
    }

    public function setOrganisateur(?User $organisateur): self
    {
        $this->organisateur = $organisateur;
        return $this;
    }

    public function getStatut(): StatutEvenement
    {
        return StatutEvenement::from($this->statut);
    }

    public function setStatut(StatutEvenement $statut): self
    {
        $this->statut = $statut->value;
        return $this;
    }

    public function getDateCreation(): ?\DateTimeInterface
    {
        return $this->dateCreation;
    }

    public function setDateCreation(\DateTimeInterface $dateCreation): self
    {
        $this->dateCreation = $dateCreation;
        return $this;
    }

    public function getDateLimiteInscription(): ?\DateTimeInterface
    {
        return $this->dateLimiteInscription;
    }

    public function setDateLimiteInscription(?\DateTimeInterface $dateLimiteInscription): self
    {
        $this->dateLimiteInscription = $dateLimiteInscription;
        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?\DateTimeInterface $updatedAt): self
    {
        $this->updatedAt = $updatedAt;
        return $this;
    }

    public function getImage(): ?string
    {
        return $this->image;
    }

    public function setImage(?string $image): self
    {
        $this->image = $image;
        return $this;
    }

    /**
     * @return Collection<int, Participation>
     */
    public function getParticipations(): Collection
    {
        return $this->participations;
    }

    public function addParticipation(Participation $participation): self
    {
        if (!$this->participations->contains($participation)) {
            $this->participations->add($participation);
            $participation->setEvenement($this);
        }
        return $this;
    }

    public function removeParticipation(Participation $participation): self
    {
        if ($this->participations->removeElement($participation)) {
            if ($participation->getEvenement() === $this) {
                $participation->setEvenement(null);
            }
        }
        return $this;
    }

    /**
     * @return Collection<int, EvenementSponsor>
     */
    public function getEvenementSponsors(): Collection
    {
        return $this->evenementSponsors;
    }

    public function addEvenementSponsor(EvenementSponsor $evenementSponsor): self
    {
        if (!$this->evenementSponsors->contains($evenementSponsor)) {
            $this->evenementSponsors->add($evenementSponsor);
            $evenementSponsor->setEvenement($this);
        }
        return $this;
    }

    public function removeEvenementSponsor(EvenementSponsor $evenementSponsor): self
    {
        if ($this->evenementSponsors->removeElement($evenementSponsor)) {
            if ($evenementSponsor->getEvenement() === $this) {
                $evenementSponsor->setEvenement(null);
            }
        }
        return $this;
    }

    // Méthode pour obtenir les sponsors confirmés
    public function getSponsorsConfirmes(): array
    {
        $sponsors = [];
        foreach ($this->evenementSponsors as $es) {
            if ($es->isConfirme() && $es->getSponsor()) {
                $sponsors[] = $es->getSponsor();
            }
        }
        return $sponsors;
    }

    // Méthode pour obtenir le montant total des contributions
    public function getMontantTotalContributions(): float
    {
        $total = 0;
        foreach ($this->evenementSponsors as $es) {
            if ($es->isConfirme()) {
                $montant = $es->getMontantContributionFloat();
                if ($montant > 0) {
                    $total += $montant;
                }
            }
        }
        return $total;
    }

    // Méthode pour vérifier si un sponsor est déjà associé à l'événement
    public function hasSponsor(Sponsor $sponsor): bool
    {
        foreach ($this->evenementSponsors as $es) {
            if ($es->getSponsor() === $sponsor) {
                return true;
            }
        }
        return false;
    }

    // Méthode pour ajouter un sponsor avec contribution
    public function addSponsorWithContribution(
        Sponsor $sponsor,
        ?string $montant = '0.00',
        TypeContribution $typeContribution = TypeContribution::FINANCIER,
        ?string $description = null
    ): EvenementSponsor {
        $evenementSponsor = EvenementSponsor::create(
            $this, 
            $sponsor, 
            $montant, 
            $typeContribution, 
            $description
        );
        $this->addEvenementSponsor($evenementSponsor);
        return $evenementSponsor;
    }

    // Méthodes utiles

    public function getPlacesDisponibles(): int
    {
        return $this->capaciteMax - $this->nombreInscrits;
    }

    public function isComplet(): bool
    {
        return $this->nombreInscrits >= $this->capaciteMax;
    }

    public function isInscriptionOuverte(): bool
    {
        if (!$this->getStatut()->isAVenir()) {
            return false;
        }

        if ($this->dateLimiteInscription && $this->dateLimiteInscription < new \DateTime()) {
            return false;
        }

        if ($this->isComplet()) {
            return false;
        }

        return true;
    }

    public function getDuree(): ?\DateInterval
    {
        if ($this->dateDebut && $this->dateFin) {
            return $this->dateDebut->diff($this->dateFin);
        }
        return null;
    }

    // Méthodes de cycle de vie
    #[ORM\PreUpdate]
    public function updateTimestamp(): void
    {
        $this->updatedAt = new \DateTime();
    }

    #[ORM\PrePersist]
    #[ORM\PreUpdate]
    public function updateStatut(): void
    {
        $now = new \DateTime();
        
        // Ne pas modifier le statut si annulé
        if ($this->getStatut()->isAnnule()) {
            return;
        }
        
        if ($this->dateDebut && $this->dateFin) {
            if ($now < $this->dateDebut) {
                $this->setStatut(StatutEvenement::A_VENIR);
            } elseif ($now >= $this->dateDebut && $now <= $this->dateFin) {
                $this->setStatut(StatutEvenement::EN_COURS);
            } elseif ($now > $this->dateFin) {
                $this->setStatut(StatutEvenement::TERMINE);
            }
        }
    }

    // Méthodes pour vérifier le statut
    public function isAVenir(): bool
    {
        return $this->getStatut()->isAVenir();
    }

    public function isEnCours(): bool
    {
        return $this->getStatut()->isEnCours();
    }

    public function isTermine(): bool
    {
        return $this->getStatut()->isTermine();
    }

    public function isAnnule(): bool
    {
        return $this->getStatut()->isAnnule();
    }

    // Méthode pour vérifier si un utilisateur est déjà inscrit
    public function isUserInscrit(User $user): bool
    {
        foreach ($this->participations as $participation) {
            if ($participation->getEtudiant() === $user && $participation->isConfirme()) {
                return true;
            }
        }
        
        return false;
    }

    // Méthode pour obtenir les étudiants inscrits
    public function getEtudiantsInscrits(): array
    {
        $etudiants = [];
        foreach ($this->participations as $participation) {
            if ($participation->isConfirme() && $etudiant = $participation->getEtudiant()) {
                $etudiants[] = $etudiant;
            }
        }
        
        return $etudiants;
    }

    // Méthode pour obtenir la durée formatée
    public function getDureeFormatee(): string
    {
        $duree = $this->getDuree();
        if (!$duree) {
            return 'Durée non définie';
        }
        
        if ($duree->d > 0) {
            return $duree->d . ' jour(s)';
        } elseif ($duree->h > 0) {
            return $duree->h . ' heure(s) ' . $duree->i . ' minute(s)';
        } else {
            return $duree->i . ' minute(s)';
        }
    }

    // Méthode pour obtenir les informations de date formatées
    public function getDateDebutFormatee(): string
    {
        return $this->dateDebut ? $this->dateDebut->format('d/m/Y H:i') : 'Non définie';
    }

    public function getDateFinFormatee(): string
    {
        return $this->dateFin ? $this->dateFin->format('d/m/Y H:i') : 'Non définie';
    }

    // Méthode pour obtenir le type formaté
    public function getTypeFormate(): string
    {
        return $this->getType()->getLabel();
    }

    // Méthode pour obtenir le statut formaté
    public function getStatutFormate(): string
    {
        return $this->getStatut()->getLabel();
    }

    // Méthode pour créer un événement
    public static function create(
        string $titre,
        User $organisateur,
        TypeEvenement $type,
        \DateTimeInterface $dateDebut,
        \DateTimeInterface $dateFin,
        string $lieu,
        int $capaciteMax = 30,
        ?string $description = null,
        ?\DateTimeInterface $dateLimiteInscription = null
    ): self {
        $evenement = new self();
        $evenement->setTitre($titre);
        $evenement->setOrganisateur($organisateur);
        $evenement->setType($type);
        $evenement->setDateDebut($dateDebut);
        $evenement->setDateFin($dateFin);
        $evenement->setLieu($lieu);
        $evenement->setCapaciteMax($capaciteMax);
        $evenement->setDescription($description);
        $evenement->setDateLimiteInscription($dateLimiteInscription);
        
        return $evenement;
    }

    // Méthode pour obtenir un résumé
    public function getResume(): string
    {
        return sprintf(
            "Événement #%d: %s\n" .
            "Type: %s\n" .
            "Organisateur: %s\n" .
            "Dates: %s - %s\n" .
            "Lieu: %s\n" .
            "Capacité: %d/%d\n" .
            "Statut: %s\n" .
            "Sponsors: %d\n" .
            "Contributions totales: %.2f TND",
            $this->evenement_id,
            $this->titre,
            $this->getTypeFormate(),
            $this->organisateur ? $this->organisateur->getFullName() : 'Non défini',
            $this->getDateDebutFormatee(),
            $this->getDateFinFormatee(),
            $this->lieu,
            $this->nombreInscrits,
            $this->capaciteMax,
            $this->getStatutFormate(),
            count($this->getSponsorsConfirmes()),
            $this->getMontantTotalContributions()
        );
    }

    public function __toString(): string
    {
        return $this->titre ?? 'Événement #' . $this->evenement_id;
    }

    // Ajouter une méthode pour récupérer les événements organisés par un responsable étudiant
    public function isOrganiseParResponsableEtudiant(): bool
    {
        return $this->organisateur && $this->organisateur->isResponsableEtudiant();
    }
}