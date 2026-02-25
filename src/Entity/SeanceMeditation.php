<?php

namespace App\Entity;

use App\Entity\Enum\TypeFichier;
use App\Entity\Enum\TypeNiveau;
use App\Repository\SeanceMeditationRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: SeanceMeditationRepository::class)]
class SeanceMeditation
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $seance_id = null;

    #[ORM\Column(length: 150)]
    #[Assert\NotBlank(message: "Le titre est obligatoire")]
    #[Assert\Length(
        min: 5,
        max: 150,
        minMessage: "Le titre doit faire au moins {{ limit }} caractères",
        maxMessage: "Le titre ne peut pas dépasser {{ limit }} caractères"
    )]
    private ?string $titre = null;

    #[ORM\Column(type: 'text')]
    #[Assert\NotBlank(message: "La description est obligatoire")]
    #[Assert\Length(min: 20, minMessage: "La description doit faire au moins {{ limit }} caractères")]
    private ?string $description = null;

    #[ORM\Column(length: 255, nullable: true)] 
    private ?string $fichier = null;

    #[ORM\Column(length: 10)]
    private ?string $typeFichier = null;

    #[ORM\Column]
    #[Assert\NotBlank(message: "La durée est obligatoire")]
    #[Assert\Positive(message: "La durée doit être positive")]
    #[Assert\LessThanOrEqual(
        value: 3600,
        message: "La durée ne peut pas dépasser {{ compared_value }} secondes"
    )]
    private ?int $duree = null; // en secondes

    #[ORM\Column]
    private ?bool $is_active = true;

    #[ORM\Column(length: 20)]
    private ?string $niveau = null;

    #[ORM\ManyToOne(inversedBy: 'seanceMeditations')]
    #[ORM\JoinColumn(name: 'categorie_id', referencedColumnName: 'categorie_id', nullable: false)]
    #[Assert\NotNull(message: "La catégorie est obligatoire")]
    private ?CategorieMeditation $categorie = null;

  #[ORM\Column(type: 'datetime')]
    private ?\DateTime $created_at = null;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTime $updated_at = null;

    public function __construct()
    {
        $this->created_at = new \DateTime();
        $this->is_active = true;
    }

    // Getters et Setters
    public function getSeanceId(): ?int
    {
        return $this->seance_id;
    }

    public function getTitre(): ?string
    {
        return $this->titre;
    }

    public function setTitre(string $titre): static
    {
        $this->titre = $titre;
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): static
    {
        $this->description = $description;
        return $this;
    }

    public function getFichier(): ?string
    {
        return $this->fichier;
    }

    public function setFichier(string $fichier): static
    {
        $this->fichier = $fichier;
        return $this;
    }

    public function getTypeFichier(): ?string
    {
        return $this->typeFichier;
    }

    public function setTypeFichier(string $typeFichier): static
    {
        $this->typeFichier = $typeFichier;
        return $this;
    }

    public function getDuree(): ?int
    {
        return $this->duree;
    }

    public function setDuree(int $duree): static
    {
        $this->duree = $duree;
        return $this;
    }

    public function isIsActif(): ?bool
    {
        return $this->is_active;
    }

    public function isActif(): bool
    {
        return $this->is_active ?? true;
    }

    public function isActive(): bool
{
    return $this->is_active ?? true;
}

    public function setIsActif(bool $is_active): static
    {
        $this->is_active = $is_active;
        return $this;
    }

    public function getNiveau(): ?string
    {
        return $this->niveau;
    }

    public function setNiveau(string $niveau): static
    {
        $this->niveau = $niveau;
        return $this;
    }

    public function getCategorie(): ?CategorieMeditation
    {
        return $this->categorie;
    }

    public function setCategorie(?CategorieMeditation $categorie): static
    {
        $this->categorie = $categorie;
        return $this;
    }

    public function getCreatedAt(): ?\DateTime
    {
        return $this->created_at;
    }

    public function setCreatedAt(\DateTime $created_at): static
    {
        $this->created_at = $created_at;
        return $this;
    }

    public function getUpdatedAt(): ?\DateTime
    {
        return $this->updated_at;
    }

    public function setUpdatedAt(?\DateTime $updated_at): static
    {
        $this->updated_at = $updated_at;
        return $this;
    }

    public function getDureeFormatee(): string
    {
        $minutes = floor($this->duree / 60);
        $secondes = $this->duree % 60;
        return sprintf('%02d:%02d', $minutes, $secondes);
    }


    public function __toString(): string
    {
        return $this->titre ?? '';
    }
}
