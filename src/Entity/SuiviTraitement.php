<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Serializer\Annotation\Groups;
use App\Enum\Ressenti;
use App\Enum\SaisiPar;
use DateTime;
use DateTimeInterface;


#[ORM\Entity]
#[ORM\Table(name: 'suivi_traitement')]
#[ORM\HasLifecycleCallbacks]
class SuiviTraitement
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'suivitraitement_id', type: 'integer')]
    #[Groups(['suivi:read'])]
    private ?int $suivitraitement_id = null;

    #[ORM\ManyToOne(targetEntity: Traitement::class, inversedBy: 'suivis')]
    #[ORM\JoinColumn(name: 'Traitement_id', referencedColumnName: 'traitement_id', nullable: false)]
    #[Groups(['suivi:read', 'suivi:write', 'suivi:detail'])]
    #[Assert\NotBlank(message: 'Le traitement est obligatoire')]
    private ?Traitement $traitement = null;

    #[ORM\Column(name: 'dateSuivi', type: 'date')]
    #[Groups(['suivi:read', 'suivi:write'])]
    #[Assert\NotNull(message: 'La date du suivi est obligatoire')]
    #[Assert\Type(\DateTimeInterface::class, message: 'La date du suivi doit être valide')]
    private ?\DateTimeInterface $dateSuivi = null;

    #[ORM\Column(name: 'dateSaisie', type: 'datetime')]
    #[Groups(['suivi:read'])]
    #[Assert\NotNull(message: 'La date de saisie est obligatoire')]
    #[Assert\Type(\DateTimeInterface::class, message: 'La date de saisie doit être valide')]
    private ?\DateTimeInterface $dateSaisie = null;

    #[ORM\Column(name: 'effectue', type: 'boolean', options: ['default' => false])]
    #[Groups(['suivi:read', 'suivi:write'])]
    private ?bool $effectue = null;

    #[ORM\Column(name: 'heurePrevue', type: 'time', nullable: true)]
    #[Groups(['suivi:read', 'suivi:write'])]
    private ?\DateTimeInterface $heurePrevue = null;

    #[ORM\Column(name: 'heureEffective', type: 'time', nullable: true)]
    #[Groups(['suivi:read', 'suivi:write'])]
    private ?\DateTimeInterface $heureEffective = null;

    #[ORM\Column(name: 'observations', type: 'text', length: 1000, nullable: true)]
    #[Groups(['suivi:read', 'suivi:write'])]
    private ?string $observations = null;

    #[ORM\Column(name: 'observationsPsy', type: 'text', length: 1000, nullable: true)]
    #[Groups(['suivi:read', 'suivi:write'])]
    private ?string $observationsPsy = null;

    #[ORM\Column(name: 'evaluation', type: 'integer', nullable: true)]
    #[Groups(['suivi:read', 'suivi:write'])]
    #[Assert\Range(
        min: 1,
        max: 10,
        notInRangeMessage: 'L\'évaluation doit être comprise entre {{ min }} et {{ max }}'
    )]
    private ?int $evaluation = null;

    #[ORM\Column(name: 'ressenti', type: 'string', length: 50, nullable: true)]
    #[Groups(['suivi:read', 'suivi:write'])]
    private ?string $ressenti = null;

    #[ORM\Column(name: 'saisiPar', type: 'string', length: 50)]
    #[Groups(['suivi:read', 'suivi:write'])]
    private ?string $saisiPar = null;

    #[ORM\Column(name: 'valide', type: 'boolean', options: ['default' => false])]
    #[Groups(['suivi:read', 'suivi:write'])]
    private ?bool $valide = null;

    #[ORM\Column(name: 'createdAt', type: 'datetime')]
    #[Groups(['suivi:read'])]
    #[Assert\NotNull(message: 'La date de création est obligatoire')]
    #[Assert\Type(\DateTimeInterface::class, message: 'La date de création doit être valide')]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(name: 'updatedAt', type: 'datetime', nullable: true)]
    #[Groups(['suivi:read'])]
    private ?\DateTimeInterface $updatedAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
        $this->dateSaisie = new \DateTime();
        $this->effectue = false;
        $this->valide = false;
        $this->saisiPar = SaisiPar::ETUDIANT->value;
    }

    // Getters et setters
    public function getId(): ?int
    {
        return $this->suivitraitement_id;
    }

    public function getSuivitraitementId(): ?int
    {
        return $this->suivitraitement_id;
    }

    public function getTraitement(): ?Traitement
    {
        return $this->traitement;
    }

    public function setTraitement(?Traitement $traitement): self
    {
        $this->traitement = $traitement;
        return $this;
    }

    public function getDateSuivi(): ?\DateTimeInterface
    {
        return $this->dateSuivi;
    }

    public function setDateSuivi(?\DateTimeInterface $dateSuivi): self
    {
        $this->dateSuivi = $dateSuivi;
        return $this;
    }

    public function getDateSaisie(): ?\DateTimeInterface
    {
        return $this->dateSaisie;
    }

    public function setDateSaisie(?\DateTimeInterface $dateSaisie): self
    {
        $this->dateSaisie = $dateSaisie;
        return $this;
    }

    public function isEffectue(): bool
    {
        return $this->effectue;
    }

    public function setEffectue(bool $effectue): self
    {
        $this->effectue = $effectue;

        if ($effectue && $this->heureEffective === null) {
            $this->heureEffective = new \DateTime();
        }

        return $this;
    }

    public function getHeureEffective(): ?\DateTimeInterface
    {
        return $this->heureEffective;
    }

    public function setHeureEffective(?\DateTimeInterface $heureEffective): self
    {
        $this->heureEffective = $heureEffective;
        return $this;
    }

    public function getHeurePrevue(): ?\DateTimeInterface
    {
        return $this->heurePrevue;
    }

    public function setHeurePrevue(?\DateTimeInterface $heurePrevue): self
    {
        $this->heurePrevue = $heurePrevue;
        return $this;
    }

    public function getObservations(): ?string
    {
        return $this->observations;
    }

    public function setObservations(?string $observations): self
    {
        if (strlen($observations) > 1000) {
            throw new \InvalidArgumentException('Les observations ne peuvent pas dépasser 1000 caractères');
        }

        $this->observations = $observations;
        return $this;
    }

    public function getObservationsPsy(): ?string
    {
        return $this->observationsPsy;
    }

    public function setObservationsPsy(?string $observationsPsy): self
    {
        $this->observationsPsy = $observationsPsy;
        return $this;
    }

    public function getEvaluation(): ?int
    {
        return $this->evaluation;
    }

    public function setEvaluation(?int $evaluation): self
    {
        if ($evaluation !== null && ($evaluation < 1 || $evaluation > 10)) {
            throw new \InvalidArgumentException('L\'évaluation doit être entre 1 et 10');
        }

        $this->evaluation = $evaluation;
        return $this;
    }

    public function getRessenti(): ?string
    {
        return $this->ressenti;
    }

    public function setRessenti(?string $ressenti): self
    {
        if ($ressenti !== null && !in_array($ressenti, Ressenti::getValues(), true)) {
            throw new \InvalidArgumentException('Ressenti invalide');
        }

        $this->ressenti = $ressenti;
        return $this;
    }

    public function getRessentiEnum(): ?Ressenti
    {
        return $this->ressenti ? Ressenti::from($this->ressenti) : null;
    }

    public function setRessentiEnum(?Ressenti $ressenti): self
    {
        $this->ressenti = $ressenti?->value;
        return $this;
    }

    public function getSaisiPar(): string
    {
        return $this->saisiPar;
    }

    public function setSaisiPar(string $saisiPar): self
    {
        if (!in_array($saisiPar, SaisiPar::getValues(), true)) {
            throw new \InvalidArgumentException('Saisi par invalide');
        }

        $this->saisiPar = $saisiPar;
        return $this;
    }

    public function getSaisiParEnum(): SaisiPar
    {
        return SaisiPar::from($this->saisiPar);
    }

    public function setSaisiParEnum(SaisiPar $saisiPar): self
    {
        $this->saisiPar = $saisiPar->value;
        return $this;
    }

    public function isValide(): bool
    {
        return $this->valide;
    }

    public function setValide(bool $valide): self
    {
        $this->valide = $valide;
        return $this;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(?\DateTimeInterface $createdAt): self
    {
        $this->createdAt = $createdAt;
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

    // Méthodes utilitaires
    public function __toString(): string
    {
        return sprintf(
            'Suivi #%d - %s',
            $this->suivitraitement_id,
            $this->dateSuivi->format('d/m/Y')
        );
    }

    #[ORM\PreUpdate]
    public function updateTimestamp(): void
    {
        $this->updatedAt = new \DateTime();
    }

    public function marquerEffectue(): self
    {
        $this->effectue = true;
        $this->heureEffective = new \DateTime();
        $this->updateTimestamp();

        return $this;
    }

    public function valider(): self
    {
        if (!$this->effectue) {
            throw new \LogicException('Un suivi non effectué ne peut pas être validé');
        }

        $this->valide = true;
        $this->updateTimestamp();

        return $this;
    }

    public function getEvaluationEtoiles(): ?string
    {
        if ($this->evaluation === null) {
            return null;
        }

        return str_repeat('★', $this->evaluation) . str_repeat('☆', 10 - $this->evaluation);
    }

    public function isEnRetard(): bool
    {
        $aujourdhui = new \DateTime();
        return !$this->effectue && $this->dateSuivi < $aujourdhui;
    }

    public function isAVenir(): bool
    {
        $aujourdhui = new \DateTime();
        return !$this->effectue && $this->dateSuivi > $aujourdhui;
    }

    public function isAujourdhui(): bool
    {
        $aujourdhui = new \DateTime();
        return $this->dateSuivi->format('Y-m-d') === $aujourdhui->format('Y-m-d');
    }

    public function getStatutDetaille(): string
    {
        if ($this->valide) {
            return 'Validé';
        }

        if ($this->effectue) {
            return 'Effectué (en attente de validation)';
        }

        if ($this->isEnRetard()) {
            return 'En retard';
        }

        if ($this->isAujourdhui()) {
            return 'À faire aujourd\'hui';
        }

        if ($this->isAVenir()) {
            return 'À venir';
        }

        return 'Non effectué';
    }

    public function getDureeDepuisSuivi(): string
    {
        $aujourdhui = new \DateTime();
        $interval = $aujourdhui->diff($this->dateSuivi);
        
        if ($interval->days == 0) {
            return 'Aujourd\'hui';
        } elseif ($interval->days == 1) {
            return 'Hier';
        } elseif ($interval->days < 7) {
            return $interval->days . ' jours';
        } elseif ($interval->days < 30) {
            $semaines = floor($interval->days / 7);
            return $semaines . ' semaine' . ($semaines > 1 ? 's' : '');
        } else {
            $mois = floor($interval->days / 30);
            return $mois . ' mois';
        }
    }

    public function getCouleurStatut(): string
    {
        if ($this->valide) {
            return 'success';
        }

        if ($this->effectue) {
            return 'info';
        }

        if ($this->isEnRetard()) {
            return 'danger';
        }

        if ($this->isAujourdhui()) {
            return 'warning';
        }

        return 'secondary';
    }

    public function getJourSemaine(): string
    {
        $jours = [
            1 => 'Lundi',
            2 => 'Mardi',
            3 => 'Mercredi',
            4 => 'Jeudi',
            5 => 'Vendredi',
            6 => 'Samedi',
            7 => 'Dimanche'
        ];

        $numeroJour = (int)$this->dateSuivi->format('N');
        return $jours[$numeroJour] ?? 'Jour inconnu';
    }

    public static function create(
        Traitement $traitement,
        \DateTimeInterface $dateSuivi,
        bool $effectue = false,
        ?string $observations = null,
        ?int $evaluation = null,
        ?string $ressenti = null,
        string $saisiPar = SaisiPar::ETUDIANT->value
    ): self {
        $suivi = new self();
        $suivi->setTraitement($traitement);
        $suivi->setDateSuivi($dateSuivi);
        $suivi->setEffectue($effectue);
        $suivi->setObservations($observations);
        $suivi->setEvaluation($evaluation);
        $suivi->setRessenti($ressenti);
        $suivi->setSaisiPar($saisiPar);

        return $suivi;
    }

    #[Assert\Callback]
    public function validateObservations(ExecutionContextInterface $context): void
    {
        // Valider les observations de l'étudiant
        if ($this->observations !== null && trim($this->observations) !== '') {
            if (strlen($this->observations) < 10) {
                $context->buildViolation('Les observations doivent faire au moins 10 caractères')
                    ->atPath('observations')
                    ->addViolation();
            }
            if (strlen($this->observations) > 1000) {
                $context->buildViolation('Les observations ne peuvent pas dépasser 1000 caractères')
                    ->atPath('observations')
                    ->addViolation();
            }
        }

        // Valider les observations du psychologue
        if ($this->observationsPsy !== null && trim($this->observationsPsy) !== '') {
            if (strlen($this->observationsPsy) < 10) {
                $context->buildViolation('Les observations du psychologue doivent faire au moins 10 caractères')
                    ->atPath('observationsPsy')
                    ->addViolation();
            }
            if (strlen($this->observationsPsy) > 1000) {
                $context->buildViolation('Les observations du psychologue ne peuvent pas dépasser 1000 caractères')
                    ->atPath('observationsPsy')
                    ->addViolation();
            }
        }
    }
}
