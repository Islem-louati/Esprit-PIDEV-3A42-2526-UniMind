<?php

namespace App\Entity;

use App\Enum\TypeSponsor;
use App\Enum\StatutSponsor;
use App\Repository\SponsorRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

#[ORM\Entity(repositoryClass: SponsorRepository::class)]
#[ORM\Table(name: 'sponsor')]
#[UniqueEntity(
    fields: ['nomSponsor'],
    message: 'Ce nom de sponsor existe déjà'
)]
class Sponsor
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $sponsor_id = null;

    #[ORM\Column(type: Types::STRING, length: 150, unique: true)]
    #[Assert\NotBlank(message: "Le nom du sponsor est obligatoire")]
    #[Assert\Length(
        max: 150,
        maxMessage: "Le nom ne peut pas dépasser {{ limit }} caractères"
    )]
    private ?string $nomSponsor = null;

    #[ORM\Column(type: 'string', length: 20, enumType: TypeSponsor::class)]
    #[Assert\NotBlank(message: "Le type de sponsor est obligatoire")]
    private ?TypeSponsor $typeSponsor = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    #[Assert\Url(message: "L'URL du site web n'est pas valide")]
    private ?string $siteWeb = null;

    #[ORM\Column(type: Types::STRING, length: 150)]
    #[Assert\NotBlank(message: "L'email de contact est obligatoire")]
    #[Assert\Email(message: "L'email n'est pas valide")]
    private ?string $emailContact = null;

    #[ORM\Column(type: Types::STRING, length: 20, nullable: true)]
    #[Assert\Regex(
        pattern: '/^[0-9\s\+\-\(\)]+$/',
        message: "Le numéro de téléphone n'est pas valide"
    )]
    private ?string $telephone = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $adresse = null;

    #[ORM\Column(type: Types::STRING, length: 100, nullable: true)]
    private ?string $domaineActivite = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $logo = null;

    #[ORM\Column(type: 'string', length: 20, enumType: StatutSponsor::class)]
    private StatutSponsor $statut = StatutSponsor::EN_ATTENTE;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $created_at = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $updated_at = null;

    #[ORM\OneToMany(mappedBy: 'sponsor', targetEntity: EvenementSponsor::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $evenementSponsors;

    public function __construct()
    {
        $this->created_at = new \DateTime();
        $this->evenementSponsors = new ArrayCollection();
    }

    public function getSponsorId(): ?int
    {
        return $this->sponsor_id;
    }

    public function getNomSponsor(): ?string
    {
        return $this->nomSponsor;
    }

    public function setNomSponsor(string $nomSponsor): self
    {
        $this->nomSponsor = $nomSponsor;
        return $this;
    }

    public function getTypeSponsor(): ?TypeSponsor
    {
        return $this->typeSponsor;
    }

    public function setTypeSponsor(TypeSponsor $typeSponsor): self
    {
        $this->typeSponsor = $typeSponsor;
        return $this;
    }

    public function getSiteWeb(): ?string
    {
        return $this->siteWeb;
    }

    public function setSiteWeb(?string $siteWeb): self
    {
        $this->siteWeb = $siteWeb;
        return $this;
    }

    public function getEmailContact(): ?string
    {
        return $this->emailContact;
    }

    public function setEmailContact(string $emailContact): self
    {
        $this->emailContact = $emailContact;
        return $this;
    }

    public function getTelephone(): ?string
    {
        return $this->telephone;
    }

    public function setTelephone(?string $telephone): self
    {
        $this->telephone = $telephone;
        return $this;
    }

    public function getAdresse(): ?string
    {
        return $this->adresse;
    }

    public function setAdresse(?string $adresse): self
    {
        $this->adresse = $adresse;
        return $this;
    }

    public function getDomaineActivite(): ?string
    {
        return $this->domaineActivite;
    }

    public function setDomaineActivite(?string $domaineActivite): self
    {
        $this->domaineActivite = $domaineActivite;
        return $this;
    }

    public function getLogo(): ?string
    {
        return $this->logo;
    }

    public function setLogo(?string $logo): self
    {
        $this->logo = $logo;
        return $this;
    }

    public function getStatut(): StatutSponsor
    {
        return $this->statut;
    }

    public function setStatut(StatutSponsor $statut): self
    {
        $this->statut = $statut;
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

    public function getEvenementSponsors(): Collection
    {
        return $this->evenementSponsors;
    }

    public function addEvenementSponsor(EvenementSponsor $evenementSponsor): self
    {
        if (!$this->evenementSponsors->contains($evenementSponsor)) {
            $this->evenementSponsors->add($evenementSponsor);
            $evenementSponsor->setSponsor($this);
        }
        return $this;
    }

    public function removeEvenementSponsor(EvenementSponsor $evenementSponsor): self
    {
        if ($this->evenementSponsors->removeElement($evenementSponsor)) {
            if ($evenementSponsor->getSponsor() === $this) {
                $evenementSponsor->setSponsor(null);
            }
        }
        return $this;
    }

    // Méthodes utilitaires avec les nouveaux statuts

    public function isConfirme(): bool
    {
        return $this->statut === StatutSponsor::CONFIRME;
    }

    public function isEnAttente(): bool
    {
        return $this->statut === StatutSponsor::EN_ATTENTE;
    }

    public function isRefuse(): bool
    {
        return $this->statut === StatutSponsor::REFUSE;
    }

    public function isAnnule(): bool
    {
        return $this->statut === StatutSponsor::ANNULE;
    }

    public function getNombreEvenements(): int
    {
        return $this->evenementSponsors->count();
    }

    public function getMontantTotalContributions(): float
    {
        $total = 0;
        foreach ($this->evenementSponsors as $es) {
            $total += $es->getMontantContribution() ?? 0;
        }
        return $total;
    }

    public function getEvenements(): array
    {
        $evenements = [];
        foreach ($this->evenementSponsors as $evenementSponsor) {
            if ($evenement = $evenementSponsor->getEvenement()) {
                $evenements[] = $evenement;
            }
        }
        return $evenements;
    }

    public function sponsoriseEvenement(Evenement $evenement): bool
    {
        foreach ($this->evenementSponsors as $evenementSponsor) {
            if ($evenementSponsor->getEvenement() === $evenement) {
                return true;
            }
        }
        return false;
    }

    public function getContributionPourEvenement(Evenement $evenement): ?float
    {
        foreach ($this->evenementSponsors as $evenementSponsor) {
            if ($evenementSponsor->getEvenement() === $evenement) {
                return $evenementSponsor->getMontantContribution();
            }
        }
        return null;
    }

    // Méthodes pour changer le statut

    public function confirmer(): self
    {
        $this->statut = StatutSponsor::CONFIRME;
        return $this;
    }

    public function mettreEnAttente(): self
    {
        $this->statut = StatutSponsor::EN_ATTENTE;
        return $this;
    }

    public function refuser(): self
    {
        $this->statut = StatutSponsor::REFUSE;
        return $this;
    }

    public function annuler(): self
    {
        $this->statut = StatutSponsor::ANNULE;
        return $this;
    }

    #[ORM\PreUpdate]
    public function updateTimestamp(): void
    {
        $this->updated_at = new \DateTime();
    }

    public function getTypeSponsorFormate(): string
    {
        return $this->typeSponsor ? $this->typeSponsor->label() : 'Non défini';
    }

    public function getStatutFormate(): string
    {
        return $this->statut->getLabel();
    }

    public static function create(
        string $nomSponsor,
        TypeSponsor $typeSponsor,
        string $emailContact,
        ?string $siteWeb = null,
        ?string $telephone = null,
        ?string $adresse = null,
        ?string $domaineActivite = null
    ): self {
        $sponsor = new self();
        $sponsor->setNomSponsor($nomSponsor);
        $sponsor->setTypeSponsor($typeSponsor);
        $sponsor->setEmailContact($emailContact);
        $sponsor->setSiteWeb($siteWeb);
        $sponsor->setTelephone($telephone);
        $sponsor->setAdresse($adresse);
        $sponsor->setDomaineActivite($domaineActivite);

        return $sponsor;
    }

    public function getResume(): string
    {
        return sprintf(
            "Sponsor: %s\n" .
                "Type: %s\n" .
                "Statut: %s\n" .
                "Événements sponsorisés: %d\n" .
                "Contribution totale: %.2f €\n" .
                "Contact: %s | %s",
            $this->nomSponsor,
            $this->getTypeSponsorFormate(),
            $this->getStatutFormate(),
            $this->getNombreEvenements(),
            $this->getMontantTotalContributions(),
            $this->emailContact,
            $this->telephone ?? 'N/A'
        );
    }

    public function __toString(): string
    {
        return $this->nomSponsor ?? 'Sponsor';
    }
}