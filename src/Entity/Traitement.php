<?php

namespace App\Entity;

use App\Enum\StatutTraitement;
use App\Enum\CategorieTraitement;
use App\Enum\PrioriteTraitement;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity]
#[ORM\Table(name: 'traitement')]
#[ORM\HasLifecycleCallbacks]
class Traitement
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    #[Groups(['traitement:read', 'suivi:detail'])]
    private ?int $traitement_id = null;

    #[ORM\Column(type: 'string', length: 255)]
    #[Groups(['traitement:read', 'traitement:write', 'suivi:detail'])]
    #[Assert\NotBlank(message: 'Le titre du traitement est obligatoire')]
    #[Assert\Length(
        min: 3,
        max: 255,
        minMessage: 'Le titre doit faire au moins {{ limit }} caractères',
        maxMessage: 'Le titre ne peut pas dépasser {{ limit }} caractères'
    )]
    private ?string $titre = null;

    #[ORM\Column(type: 'text')]
    #[Groups(['traitement:read', 'traitement:write'])]
    #[Assert\NotBlank(message: 'La description est obligatoire')]
    #[Assert\Length(
        min: 10,
        max: 1000,
        minMessage: 'La description doit faire au moins {{ limit }} caractères',
        maxMessage: 'La description ne peut pas dépasser {{ limit }} caractères'
    )]
    private ?string $description = null;

    #[ORM\Column(type: 'string', length: 255)]
    #[Groups(['traitement:read', 'traitement:write'])]
    #[Assert\NotBlank(message: 'Le type de traitement est obligatoire')]
    #[Assert\Length(
        min: 2,
        max: 255,
        minMessage: 'Le type doit faire au moins {{ limit }} caractères',
        maxMessage: 'Le type ne peut pas dépasser {{ limit }} caractères'
    )]
    private ?string $type = null;

    #[ORM\Column(type: 'string', length: 255)]
    #[Groups(['traitement:read', 'traitement:write'])]
    #[Assert\NotBlank(message: 'La catégorie est obligatoire')]
    private ?string $categorie = null;

    #[ORM\Column(type: 'integer')]
    #[Groups(['traitement:read', 'traitement:write'])]
    #[Assert\NotBlank(message: 'La durée est obligatoire')]
    #[Assert\Positive(message: 'La durée doit être positive')]
    #[Assert\Range(
        min: 1,
        max: 365,
        notInRangeMessage: 'La durée doit être comprise entre {{ min }} et {{ max }} jours'
    )]
    private ?int $duree_jours = null;

    #[ORM\Column(type: 'text', nullable: true)]
    #[Groups(['traitement:read', 'traitement:write'])]
    private ?string $dosage = null;

    #[ORM\Column(type: 'date')]
    #[Groups(['traitement:read', 'traitement:write'])]
    #[Assert\NotBlank(message: 'La date de début est obligatoire')]
    #[Assert\Type(\DateTimeInterface::class, message: 'La date de début doit être valide')]
    private ?\DateTimeInterface $date_debut = null;

    #[ORM\Column(type: 'date', nullable: true)]
    #[Groups(['traitement:read', 'traitement:write'])]
    #[Assert\Type(\DateTimeInterface::class, message: 'La date de fin doit être valide')]
    #[Assert\Expression(
        "this.getDateFin() == null or this.getDateFin() >= this.getDateDebut()",
        message: 'La date de fin doit être postérieure à la date de début'
    )]
    private ?\DateTimeInterface $date_fin = null;

    #[ORM\Column(type: 'string', length: 255)]
    #[Groups(['traitement:read', 'traitement:write'])]
    #[Assert\NotBlank(message: 'Le statut est obligatoire')]
    private ?string $statut = null;

    #[ORM\Column(type: 'string', length: 255)]
    #[Groups(['traitement:read', 'traitement:write'])]
    #[Assert\NotBlank(message: 'La priorité est obligatoire')]
    private ?string $priorite = null;

    #[ORM\Column(type: 'text')]
    #[Groups(['traitement:read', 'traitement:write'])]
    #[Assert\NotBlank(message: 'L\'objectif thérapeutique est obligatoire')]
    #[Assert\Length(
        min: 10,
        max: 1000,
        minMessage: 'L\'objectif thérapeutique doit faire au moins {{ limit }} caractères',
        maxMessage: 'L\'objectif thérapeutique ne peut pas dépasser {{ limit }} caractères'
    )]
    private ?string $objectif_therapeutique = null;

    #[ORM\Column(type: 'datetime')]
    #[Groups(['traitement:read'])]
    private \DateTimeInterface $created_at;

    #[ORM\Column(type: 'datetime', nullable: true)]
    #[Groups(['traitement:read'])]
    private ?\DateTimeInterface $updated_at = null;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'traitementsPsychologue')]
    #[ORM\JoinColumn(name: 'psychologue_id', referencedColumnName: 'user_id', nullable: false)]
    #[Groups(['traitement:read', 'traitement:detail'])]
    private ?User $psychologue = null;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'traitementsEtudiant')]
    #[ORM\JoinColumn(name: 'etudiant_id', referencedColumnName: 'user_id', nullable: false)]
    #[Groups(['traitement:read', 'traitement:write', 'traitement:detail'])]
    private ?User $etudiant = null;

    #[ORM\OneToMany(mappedBy: 'traitement', targetEntity: SuiviTraitement::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[Groups(['traitement:detail'])]
    private Collection $suivis;

    public function __construct()
    {
        $this->created_at = new \DateTime();
        $this->date_debut = new \DateTime();
        $this->statut = StatutTraitement::EN_COURS->value;
        $this->priorite = PrioriteTraitement::MOYENNE->value;
        $this->categorie = CategorieTraitement::COGNITIF->value;
        $this->suivis = new ArrayCollection();
    }

    // Getters et setters
    public function getId(): ?int
    {
        return $this->traitement_id;
    }

    public function getTraitementId(): ?int
    {
        return $this->traitement_id;
    }

    public function getTitre(): string
    {
        return $this->titre;
    }

    public function setTitre(string $titre): self
    {
        $this->titre = $titre;
        return $this;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function setDescription(string $description): self
    {
        $this->description = $description;
        return $this;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): self
    {
        $this->type = $type;
        return $this;
    }

    public function getCategorie(): string
    {
        return $this->categorie;
    }

    public function setCategorie(string $categorie): self
    {
        if (!in_array($categorie, CategorieTraitement::getValues(), true)) {
            throw new \InvalidArgumentException('Catégorie de traitement invalide');
        }
        $this->categorie = $categorie;
        return $this;
    }

    public function getCategorieEnum(): CategorieTraitement
    {
        return CategorieTraitement::from($this->categorie);
    }

    public function setCategorieEnum(CategorieTraitement $categorie): self
    {
        $this->categorie = $categorie->value;
        return $this;
    }

    public function getDureeJours(): int
    {
        return $this->duree_jours;
    }

    public function setDureeJours(int $duree_jours): self
    {
        $this->duree_jours = $duree_jours;
        return $this;
    }

    public function getDosage(): ?string
    {
        return $this->dosage;
    }

    public function setDosage(?string $dosage): self
    {
        $this->dosage = $dosage;
        return $this;
    }

    public function getDateDebut(): \DateTimeInterface
    {
        return $this->date_debut;
    }

    public function setDateDebut(\DateTimeInterface $date_debut): self
    {
        $this->date_debut = $date_debut;
        return $this;
    }

    public function getDateFin(): ?\DateTimeInterface
    {
        return $this->date_fin;
    }

    public function setDateFin(?\DateTimeInterface $date_fin): self
    {
        $this->date_fin = $date_fin;
        return $this;
    }

    public function getStatut(): string
    {
        return $this->statut;
    }

    public function setStatut(string $statut): self
    {
        if (!in_array($statut, StatutTraitement::getValues(), true)) {
            throw new \InvalidArgumentException('Statut de traitement invalide');
        }
        $this->statut = $statut;
        return $this;
    }

    public function getStatutEnum(): StatutTraitement
    {
        return StatutTraitement::from($this->statut);
    }

    public function setStatutEnum(StatutTraitement $statut): self
    {
        $this->statut = $statut->value;
        return $this;
    }

    public function getPriorite(): string
    {
        return $this->priorite;
    }

    public function setPriorite(string $priorite): self
    {
        if (!in_array($priorite, PrioriteTraitement::getValues(), true)) {
            throw new \InvalidArgumentException('Priorité de traitement invalide');
        }
        $this->priorite = $priorite;
        return $this;
    }

    public function getPrioriteEnum(): PrioriteTraitement
    {
        return PrioriteTraitement::from($this->priorite);
    }

    public function setPrioriteEnum(PrioriteTraitement $priorite): self
    {
        $this->priorite = $priorite->value;
        return $this;
    }

    public function getObjectifTherapeutique(): string
    {
        return $this->objectif_therapeutique;
    }

    public function setObjectifTherapeutique(string $objectif_therapeutique): self
    {
        $this->objectif_therapeutique = $objectif_therapeutique;
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

    public function getPsychologue(): ?User
    {
        return $this->psychologue;
    }

    public function setPsychologue(?User $psychologue): self
    {
        // Vérifier que l'utilisateur est bien un psychologue
        if ($psychologue && !$psychologue->isPsychologue()) {
            throw new \InvalidArgumentException('L\'utilisateur doit être un psychologue');
        }

        $this->psychologue = $psychologue;
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
        // Débogage pour voir l'utilisateur problématique
        dump('Utilisateur non-étudiant détecté:', [
            'id' => $etudiant->getId(),
            'nom' => $etudiant->getNom(),
            'prenom' => $etudiant->getPrenom(),
            'email' => $etudiant->getEmail(),
            'role_enum' => $etudiant->getRole()->value,
            'roles' => $etudiant->getRoles(),
            'isEtudiant_method' => $etudiant->isEtudiant(),
        ]);
        
        throw new \InvalidArgumentException('L\'utilisateur doit être un étudiant. Role actuel: ' . $etudiant->getRole()->value);
    }

    $this->etudiant = $etudiant;
    return $this;
}

    // Méthodes pour les suivis
    public function getSuivis(): Collection
    {
        return $this->suivis;
    }

    public function addSuivi(SuiviTraitement $suivi): self
    {
        if (!$this->suivis->contains($suivi)) {
            $this->suivis[] = $suivi;
            $suivi->setTraitement($this);
        }

        return $this;
    }

    public function removeSuivi(SuiviTraitement $suivi): self
    {
        if ($this->suivis->removeElement($suivi)) {
            // set the owning side to null (unless already changed)
            if ($suivi->getTraitement() === $this) {
                $suivi->setTraitement(null);
            }
        }

        return $this;
    }

    // Méthodes utilitaires
    public function __toString(): string
    {
        return sprintf('%s - %s', $this->titre, $this->etudiant ? $this->etudiant->getFullName() : 'N/A');
    }

    #[ORM\PreUpdate]
    public function updateTimestamp(): void
    {
        $this->updated_at = new \DateTime();
    }

    // Méthodes pour vérifier le statut
    public function isEnCours(): bool
    {
        return $this->statut === StatutTraitement::EN_COURS->value;
    }

    public function isTermine(): bool
    {
        return $this->statut === StatutTraitement::TERMINE->value;
    }

    public function isSuspendu(): bool
    {
        return $this->statut === StatutTraitement::SUSPENDU->value;
    }

    // Méthodes pour vérifier la priorité
    public function isPrioriteBasse(): bool
    {
        return $this->priorite === PrioriteTraitement::BASSE->value;
    }

    public function isPrioriteMoyenne(): bool
    {
        return $this->priorite === PrioriteTraitement::MOYENNE->value;
    }

    public function isPrioriteHaute(): bool
    {
        return $this->priorite === PrioriteTraitement::HAUTE->value;
    }

    // Méthodes pour calculer la progression
    public function getJoursRestants(): ?int
    {
        if ($this->date_fin === null) {
            return $this->duree_jours;
        }

        $now = new \DateTime();
        if ($now > $this->date_fin) {
            return 0;
        }

        $interval = $now->diff($this->date_fin);
        return $interval->days;
    }

    public function getJoursEcoules(): int
    {
        $now = new \DateTime();
        if ($now < $this->date_debut) {
            return 0;
        }

        $interval = $this->date_debut->diff($now);
        return min($interval->days, $this->duree_jours);
    }

    public function getPourcentageCompletion(): float
    {
        if ($this->duree_jours === 0) {
            return 0;
        }

        $joursEcoules = $this->getJoursEcoules();
        $pourcentage = ($joursEcoules / $this->duree_jours) * 100;

        return min($pourcentage, 100);
    }

    // Méthode pour vérifier si le traitement est en retard
    public function isEnRetard(): bool
    {
        if ($this->isTermine() || $this->isSuspendu()) {
            return false;
        }

        $now = new \DateTime();
        if ($this->date_fin && $now > $this->date_fin) {
            return true;
        }

        return false;
    }

    // Méthode pour calculer la date de fin estimée
    public function getDateFinEstimee(): \DateTimeInterface
    {
        if ($this->date_fin) {
            return $this->date_fin;
        }

        $dateFin = \DateTime::createFromInterface($this->date_debut);
        $dateFin->modify("+{$this->duree_jours} days");

        return $dateFin;
    }

    // Méthode pour obtenir les statuts sous forme lisible
    public function getStatutLabel(): string
    {
        return $this->getStatutEnum()->getLabel();
    }

    public function getCategorieLabel(): string
    {
        return $this->getCategorieEnum()->getLabel();
    }

    public function getPrioriteLabel(): string
    {
        return $this->getPrioriteEnum()->getLabel();
    }

    // Méthode pour obtenir un résumé du traitement
    public function getResume(): string
    {
        return sprintf(
            "Traitement: %s\n" .
                "Patient: %s\n" .
                "Psychologue: %s\n" .
                "Catégorie: %s\n" .
                "Statut: %s\n" .
                "Priorité: %s\n" .
                "Durée: %d jours\n" .
                "Progression: %.1f%%\n" .
                "Date début: %s\n" .
                "Date fin estimée: %s",
            $this->titre,
            $this->etudiant ? $this->etudiant->getFullName() : 'Non défini',
            $this->psychologue ? $this->psychologue->getFullName() : 'Non défini',
            $this->getCategorieLabel(),
            $this->getStatutLabel(),
            $this->getPrioriteLabel(),
            $this->duree_jours,
            $this->getPourcentageCompletion(),
            $this->date_debut->format('d/m/Y'),
            $this->getDateFinEstimee()->format('d/m/Y')
        );
    }
}