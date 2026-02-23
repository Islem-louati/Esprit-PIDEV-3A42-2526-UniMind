<?php

namespace App\Entity;


use App\Enum\StatutSponsor;
use App\Repository\EvenementSponsorRepository;
use App\Enum\TypeContribution;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

#[ORM\Entity(repositoryClass: EvenementSponsorRepository::class)]
#[ORM\Table(name: 'evenement_sponsor')]
#[ORM\UniqueConstraint(name: 'unique_sponsoring', columns: ['evenement_id', 'sponsor_id'])]
#[UniqueEntity(
    fields: ['evenement', 'sponsor'],
    message: 'Ce sponsor est déjà associé à cet événement'
)]
class EvenementSponsor
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'evenementSponsor_id', type: Types::INTEGER)]
    private ?int $evenementSponsor_id = null;

    #[ORM\ManyToOne(targetEntity: Evenement::class, inversedBy: 'evenementSponsors')]
    #[ORM\JoinColumn(name: 'evenement_id', referencedColumnName: 'evenement_id', nullable: false, onDelete: 'CASCADE')]
    #[Assert\NotNull(message: "L'événement est obligatoire")]
    private ?Evenement $evenement = null;

    #[ORM\ManyToOne(targetEntity: Sponsor::class, inversedBy: 'evenementSponsors')]
    #[ORM\JoinColumn(name: 'sponsor_id', referencedColumnName: 'sponsor_id', nullable: false, onDelete: 'CASCADE')]
    #[Assert\NotNull(message: "Le sponsor est obligatoire")]
    private ?Sponsor $sponsor = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    #[Assert\PositiveOrZero(message: "Le montant doit être positif ou nul")]
    private string $montantContribution = '0.00';

    #[ORM\Column(type: Types::STRING, length: 50)]
    #[Assert\Choice(
        callback: [TypeContribution::class, 'getValues'],
        message: "Type de contribution invalide"
    )]
    private string $typeContribution = TypeContribution::FINANCIER->value;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $descriptionContribution = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private \DateTimeInterface $dateContribution;

    #[ORM\Column(type: Types::STRING, length: 20)]
    #[Assert\Choice(
        callback: [StatutSponsor::class, 'getValues'],
        message: "Statut invalide"
    )]
    private string $statut = StatutSponsor::EN_ATTENTE->value;

    public function __construct()
    {
        $this->dateContribution = new \DateTime();
    }

    // Getters et Setters
    public function getEvenementSponsorId(): ?int
    {
        return $this->evenementSponsor_id;
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

    public function getSponsor(): ?Sponsor
    {
        return $this->sponsor;
    }

    public function setSponsor(?Sponsor $sponsor): self
    {
        $this->sponsor = $sponsor;
        return $this;
    }

    public function getMontantContribution(): string
    {
        return $this->montantContribution;
    }

    public function setMontantContribution(string $montantContribution): self
    {
        $this->montantContribution = $montantContribution;
        return $this;
    }

    public function getTypeContribution(): TypeContribution
    {
        return TypeContribution::from($this->typeContribution);
    }

    public function setTypeContribution(TypeContribution $typeContribution): self
    {
        $this->typeContribution = $typeContribution->value;
        return $this;
    }

    public function getDescriptionContribution(): ?string
    {
        return $this->descriptionContribution;
    }

    public function setDescriptionContribution(?string $descriptionContribution): self
    {
        $this->descriptionContribution = $descriptionContribution;
        return $this;
    }

    public function getDateContribution(): \DateTimeInterface
    {
        return $this->dateContribution;
    }

    public function setDateContribution(\DateTimeInterface $dateContribution): self
    {
        $this->dateContribution = $dateContribution;
        return $this;
    }

    public function getStatut(): StatutSponsor
    {
        return StatutSponsor::from($this->statut);
    }

    public function setStatut(StatutSponsor $statut): self
    {
        $this->statut = $statut->value;
        return $this;
    }

    // Méthodes utilitaires
    public function isConfirme(): bool
    {
        return $this->getStatut() === StatutSponsor::CONFIRME;
    }

    public function isEnAttente(): bool
    {
        return $this->getStatut() === StatutSponsor::EN_ATTENTE;
    }

    public function isRefuse(): bool
    {
        return $this->getStatut() === StatutSponsor::REFUSE;
    }

    public function isAnnule(): bool
    {
        return $this->getStatut() === StatutSponsor::ANNULE;
    }

    public function confirmer(): self
    {
        $this->setStatut(StatutSponsor::CONFIRME);
        return $this;
    }

    public function refuser(): self
    {
        $this->setStatut(StatutSponsor::REFUSE);
        return $this;
    }

    public function annuler(): self
    {
        $this->setStatut(StatutSponsor::ANNULE);
        return $this;
    }

    public function mettreEnAttente(): self
    {
        $this->setStatut(StatutSponsor::EN_ATTENTE);
        return $this;
    }

    // Méthode pour obtenir le montant comme float
    public function getMontantContributionFloat(): float
    {
        return (float) $this->montantContribution;
    }

    // Méthode pour obtenir le type de contribution formaté
    public function getTypeContributionFormate(): string
    {
        return $this->getTypeContribution()->getLabel();
    }

    // Méthode pour obtenir le statut formaté
    public function getStatutFormate(): string
    {
        return $this->getStatut()->getLabel();
    }

    // Méthode pour obtenir la date formatée
    public function getDateContributionFormatee(): string
    {
        return $this->dateContribution->format('d/m/Y H:i');
    }

    // Méthode pour créer une relation événement-sponsor
    public static function create(
        Evenement $evenement,
        Sponsor $sponsor,
        string $montantContribution = '0.00',
        TypeContribution $typeContribution = TypeContribution::FINANCIER,
        ?string $descriptionContribution = null,
        ?\DateTimeInterface $dateContribution = null,
        StatutSponsor $statut = StatutSponsor::EN_ATTENTE
    ): self {
        $evenementSponsor = new self();
        $evenementSponsor->setEvenement($evenement);
        $evenementSponsor->setSponsor($sponsor);
        $evenementSponsor->setMontantContribution($montantContribution);
        $evenementSponsor->setTypeContribution($typeContribution);
        $evenementSponsor->setDescriptionContribution($descriptionContribution);
        $evenementSponsor->setStatut($statut);

        if ($dateContribution) {
            $evenementSponsor->setDateContribution($dateContribution);
        }

        return $evenementSponsor;
    }

    // Méthode pour obtenir un résumé
    public function getResume(): string
    {
        return sprintf(
            "Sponsoring ID: %d\n" .
                "Événement: %s\n" .
                "Sponsor: %s\n" .
                "Montant: %s TND\n" .
                "Type: %s\n" .
                "Statut: %s\n" .
                "Date: %s",
            $this->evenementSponsor_id,
            $this->evenement ? $this->evenement->getTitre() : 'Événement',
            $this->sponsor ? $this->sponsor->getNomSponsor() : 'Sponsor',
            $this->montantContribution,
            $this->getTypeContributionFormate(),
            $this->getStatutFormate(),
            $this->getDateContributionFormatee()
        );
    }

    public function __toString(): string
    {
        return sprintf(
            'Sponsoring #%d: %s → %s (%s TND)',
            $this->evenementSponsor_id,
            $this->sponsor?->getNomSponsor() ?? 'Sponsor',
            $this->evenement?->getTitre() ?? 'Événement',
            $this->montantContribution
        );
    }

    // Pour la compatibilité avec certains formulaires
    public function getId(): ?int
    {
        return $this->evenementSponsor_id;
    }
}
