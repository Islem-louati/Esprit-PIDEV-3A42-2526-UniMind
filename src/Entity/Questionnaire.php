<?php

namespace App\Entity;

use App\Enum\TypeQuestionnaire;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

#[ORM\Entity]
#[ORM\Table(name: 'questionnaire')]
#[ORM\HasLifecycleCallbacks]
class Questionnaire
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $questionnaire_id = null;

    #[ORM\Column(type: 'string', length: 20, unique: true)]
    #[Assert\NotBlank(message: "Le code du questionnaire est obligatoire.")]
    private string $code = '';

    #[ORM\Column(type: 'string', length: 150)]
    #[Assert\NotBlank(message: "Le nom du questionnaire est obligatoire.")]
    private ?string $nom = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description = null;

    #[ORM\Column(type: 'string', length: 20)]
    #[Assert\NotBlank(message: "Le type de questionnaire est obligatoire.")]
    private string $type = 'stress';

    #[ORM\Column(type: 'datetime')]
    private \DateTimeInterface $created_at;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $updated_at = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $interpretat_legere = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $interpretat_modere = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $interpretat_severe = null;

    #[ORM\Column(type: 'integer')]
    #[Assert\NotBlank(message: "Le seuil léger est obligatoire.")]
    #[Assert\PositiveOrZero(message: "Le seuil léger doit être positif ou zéro.")]
    private int $seuil_leger = 1;

    #[ORM\Column(type: 'integer')]
    #[Assert\NotBlank(message: "Le seuil modéré est obligatoire.")]
    #[Assert\PositiveOrZero(message: "Le seuil modéré doit être positif ou zéro.")]
    private int $seuil_modere = 1;

    #[ORM\Column(type: 'integer')]
    #[Assert\NotBlank(message: "Le seuil sévère est obligatoire.")]
    #[Assert\PositiveOrZero(message: "Le seuil sévère doit être positif ou zéro.")]
    private int $seuil_severe = 1;

    #[ORM\Column(type: 'integer')]
    #[Assert\NotBlank(message: "Le nombre de questions est obligatoire.")]
    #[Assert\Positive(message: "Le nombre de questions doit être positif.")]
    private int $nbre_questions = 1;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'questionnairesAdmin')]
    #[ORM\JoinColumn(name: 'admin_id', referencedColumnName: 'user_id', nullable: true)]
    private ?User $admin = null;

    #[ORM\OneToMany(mappedBy: 'questionnaire', targetEntity: Question::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $questions;

    #[ORM\OneToMany(mappedBy: 'questionnaire', targetEntity: ReponseQuestionnaire::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $reponses;

    public function __construct()
    {
        $this->created_at = new \DateTime();
        $this->questions = new ArrayCollection();
        $this->reponses = new ArrayCollection();
        $this->code = 'QST-' . date('Ymd') . '-' . strtoupper(substr(bin2hex(random_bytes(3)), 0, 6));
    }

    public function getId(): ?int
    {
        return $this->questionnaire_id;
    }

    public function getQuestionnaireId(): ?int
    {
        return $this->questionnaire_id;
    }

    public function getCode(): string
    {
        return $this->code;
    }

    public function setCode(string $code): self
    {
        $this->code = $code;
        return $this;
    }

    public function getNom(): ?string
    {
        return $this->nom;
    }

    public function setNom(?string $nom): self
    {
        $this->nom = $nom;
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

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): self
    {
        $validTypes = ['stress', 'anxiete', 'depression', 'bienetre', 'sommeil'];
        if (!in_array($type, $validTypes, true)) {
            $type = 'stress';
        }
        $this->type = $type;
        return $this;
    }

    public function getTypeEnum(): ?TypeQuestionnaire
    {
        try {
            return TypeQuestionnaire::from($this->type);
        } catch (\ValueError $e) {
            return null;
        }
    }

    public function setTypeEnum(TypeQuestionnaire $type): self
    {
        $this->type = $type->value;
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

    public function getInterpretatLegere(): ?string
    {
        return $this->interpretat_legere;
    }

    public function setInterpretatLegere(?string $interpretat_legere): self
    {
        $this->interpretat_legere = $interpretat_legere;
        return $this;
    }

    public function getInterpretatModere(): ?string
    {
        return $this->interpretat_modere;
    }

    public function setInterpretatModere(?string $interpretat_modere): self
    {
        $this->interpretat_modere = $interpretat_modere;
        return $this;
    }

    public function getInterpretatSevere(): ?string
    {
        return $this->interpretat_severe;
    }

    public function setInterpretatSevere(?string $interpretat_severe): self
    {
        $this->interpretat_severe = $interpretat_severe;
        return $this;
    }

    public function getSeuilLeger(): int
    {
        return $this->seuil_leger;
    }

    public function setSeuilLeger(int $seuil_leger): self
    {
        $this->seuil_leger = $seuil_leger;
        return $this;
    }

    public function getSeuilModere(): int
    {
        return $this->seuil_modere;
    }

    public function setSeuilModere(int $seuil_modere): self
    {
        $this->seuil_modere = $seuil_modere;
        return $this;
    }

    public function getSeuilSevere(): int
    {
        return $this->seuil_severe;
    }

    public function setSeuilSevere(int $seuil_severe): self
    {
        $this->seuil_severe = $seuil_severe;
        return $this;
    }

    public function getNbreQuestions(): int
    {
        return $this->nbre_questions;
    }

    public function setNbreQuestions(int $nbre_questions): self
    {
        $this->nbre_questions = $nbre_questions;
        return $this;
    }

    public function getAdmin(): ?User
    {
        return $this->admin;
    }

    public function setAdmin(?User $admin): self
    {
        $this->admin = $admin;
        return $this;
    }

    public function getQuestions(): Collection
    {
        return $this->questions;
    }

    public function addQuestion(Question $question): self
    {
        if (!$this->questions->contains($question)) {
            $this->questions[] = $question;
            $question->setQuestionnaire($this);
        }
        return $this;
    }

    public function removeQuestion(Question $question): self
    {
        if ($this->questions->removeElement($question)) {
            if ($question->getQuestionnaire() === $this) {
                $question->setQuestionnaire(null);
            }
        }
        return $this;
    }

    public function getReponses(): Collection
    {
        return $this->reponses;
    }

    public function addReponse(ReponseQuestionnaire $reponse): self
    {
        if (!$this->reponses->contains($reponse)) {
            $this->reponses[] = $reponse;
            $reponse->setQuestionnaire($this);
        }
        return $this;
    }

    public function removeReponse(ReponseQuestionnaire $reponse): self
    {
        if ($this->reponses->removeElement($reponse)) {
            if ($reponse->getQuestionnaire() === $this) {
                $reponse->setQuestionnaire(null);
            }
        }
        return $this;
    }

    public function __toString(): string
    {
        return sprintf('%s (%s)', $this->nom ?? 'Sans nom', $this->code);
    }

    #[ORM\PreUpdate]
    public function updateTimestamp(): void
    {
        $this->updated_at = new \DateTime();
    }

    public function getInterpretationForScore(int $score): string
    {
        if ($score <= $this->seuil_leger) {
            return $this->interpretat_legere ?? '';
        } elseif ($score <= $this->seuil_modere) {
            return $this->interpretat_modere ?? '';
        } else {
            return $this->interpretat_severe ?? '';
        }
    }

    public function getNiveauForScore(int $score): string
    {
        if ($score <= $this->seuil_leger) {
            return 'léger';
        } elseif ($score <= $this->seuil_modere) {
            return 'modéré';
        } else {
            return 'sévère';
        }
    }

    public function isComplete(): bool
    {
        return $this->questions->count() === $this->nbre_questions;
    }

    public function getCompletionPercentage(): float
    {
        $questionCount = $this->questions->count();
        if ($this->nbre_questions === 0) {
            return $questionCount > 0 ? 100 : 0;
        }
        return min(($questionCount / $this->nbre_questions) * 100, 100);
    }

    public function getTypeLabel(): string
    {
        $typeEnum = $this->getTypeEnum();
        return $typeEnum ? $typeEnum->getLabel() : 'Non défini';
    }

    public function getNombreReponses(): int
    {
        return $this->reponses->count();
    }

    public function generateCode(): string
    {
        $prefix = strtoupper(substr($this->type, 0, 3));
        $timestamp = time();
        $random = bin2hex(random_bytes(2));
        return sprintf('%s-%s-%s', $prefix, $timestamp, $random);
    }

    // ✅ VALIDATION SIMPLIFIÉE - PLUS D'ERREUR DE QUESTIONS
    #[Assert\Callback]
    public function validate(ExecutionContextInterface $context, $payload): void
    {
        // Vérification des interprétations identiques
        $interpretations = [
            'Légère' => $this->interpretat_legere,
            'Modérée' => $this->interpretat_modere,
            'Sévère' => $this->interpretat_severe
        ];

        $filledInterpretations = [];
        foreach ($interpretations as $niveau => $text) {
            if (!empty($text)) {
                $filledInterpretations[$niveau] = $text;
            }
        }

        if (count($filledInterpretations) > 1) {
            $uniqueValues = array_unique($filledInterpretations);
            if (count($uniqueValues) !== count($filledInterpretations)) {
                $context->buildViolation('Les interprétations ne peuvent pas être identiques entre elles.')
                    ->atPath('interpretat_legere')
                    ->addViolation();
            }
        }

        // ✅ SUPPRESSION TOTALE DE LA VALIDATION DES QUESTIONS
        // On ne valide JAMAIS les questions à la création ou modification
        // La gestion des questions se fait dans l'interface dédiée
    }
    /**
 * Calcule le score maximum possible pour ce questionnaire
 * (nombre de questions * score maximum par question = 4)
 */
public function getScoreMaxPossible(): int
{
    return $this->getNbreQuestions() * 4;
}
}
// PAS DE TAG HTML APRÈS LA FERMETURE PHP