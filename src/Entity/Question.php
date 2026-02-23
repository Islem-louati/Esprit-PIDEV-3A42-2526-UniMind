<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Doctrine\DBAL\Types\Types;

#[ORM\Entity]
#[ORM\Table(name: 'question')]
#[ORM\HasLifecycleCallbacks]
class Question
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $question_id = null;

    #[ORM\Column(type: Types::TEXT)]
    #[Assert\NotBlank(
        message: "Le texte de la question est obligatoire."
    )]
    #[Assert\Length(
        min: 5,
        max: 2000,
        minMessage: "La question doit contenir au moins {{ limit }} caractères.",
        maxMessage: "La question ne peut pas dépasser {{ limit }} caractères."
    )]
    #[Assert\Regex(
        pattern: '/^[a-zA-Z0-9\s\p{P}À-ÿ]+$/u',
        message: "Le texte contient des caractères non autorisés."
    )]
    private string $texte;

    #[ORM\Column(type: Types::STRING, length: 50, nullable: true)]
    #[Assert\Choice(
        choices: ['likert', 'choix_multiple', 'texte_libre', 'oui_non', 'echelle'],
        message: "Le type de question '{{ value }}' n'est pas valide. Choisissez parmi : likert, choix_multiple, texte_libre, oui_non, echelle."
    )]
    private ?string $type_question = 'likert';

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private \DateTimeInterface $created_at;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $updated_at = null;

    #[ORM\Column(type: Types::JSON)]
    #[Assert\NotNull(message: "Les options ne peuvent pas être nulles.")]
    #[Assert\Count(
        min: 2,
        minMessage: "Une question doit avoir au moins {{ limit }} options."
    )]
    #[Assert\All([
        new Assert\NotBlank(message: "Une option ne peut pas être vide."),
        new Assert\Length(
            max: 255,
            maxMessage: "Une option ne peut pas dépasser {{ limit }} caractères."
        )
    ])]
    private array $options_quest = [];

    #[ORM\Column(type: Types::JSON)]
    #[Assert\NotNull(message: "Les scores ne peuvent pas être nuls.")]
    #[Assert\Count(
        min: 2,
        minMessage: "Les scores doivent correspondre au nombre d'options."
    )]
    #[Assert\All([
        new Assert\Type(
            type: 'integer',
            message: "Le score doit être un nombre entier."
        ),
        new Assert\Range(
            min: -100,
            max: 100,
            notInRangeMessage: "Le score doit être compris entre {{ min }} et {{ max }}."
        )
    ])]
    private array $score_options = [];

    #[ORM\ManyToOne(targetEntity: Questionnaire::class, inversedBy: 'questions')]
    #[ORM\JoinColumn(name: 'questionnaire_id', referencedColumnName: 'questionnaire_id', nullable: false)]
    #[Assert\NotNull(message: "La question doit être associée à un questionnaire.")]
    private ?Questionnaire $questionnaire = null;

    public function __construct()
    {
        $this->created_at = new \DateTime();
        $this->options_quest = [];
        $this->score_options = [];
    }

    // Getters et Setters avec validation

    public function getId(): ?int
    {
        return $this->question_id;
    }

    public function getQuestionId(): ?int
    {
        return $this->question_id;
    }

    public function getTexte(): string
    {
        return $this->texte;
    }

    #[Assert\Callback]
    public function validateTexte(ExecutionContextInterface $context, $payload): void
    {
        // Vérification des mots interdits
        $motsInterdits = ['spam', 'publicité', 'arnaque', 'haine', 'violence'];
        foreach ($motsInterdits as $mot) {
            if (stripos($this->texte ?? '', $mot) !== false) {
                $context->buildViolation("Le texte contient le mot interdit : '{$mot}'")
                    ->atPath('texte')
                    ->addViolation();
            }
        }
    }

    public function setTexte(string $texte): self
    {
        // Nettoyage basique
        $texte = trim($texte);
        $texte = htmlspecialchars($texte, ENT_QUOTES, 'UTF-8');
        
        $this->texte = $texte;
        return $this;
    }

    public function getTypeQuestion(): ?string
    {
        return $this->type_question;
    }

    public function setTypeQuestion(?string $type_question): self
    {
        $typesValides = ['likert', 'choix_multiple', 'texte_libre', 'oui_non', 'echelle'];
        
        if ($type_question !== null && !in_array($type_question, $typesValides)) {
            throw new \InvalidArgumentException("Type de question invalide. Types acceptés : " . implode(', ', $typesValides));
        }
        
        $this->type_question = $type_question;
        return $this;
    }

    public function getCreatedAt(): \DateTimeInterface
    {
        return $this->created_at;
    }

    public function setCreatedAt(\DateTimeInterface $created_at): self
    {
        // Vérification que la date n'est pas dans le futur
        if ($created_at > new \DateTime()) {
            throw new \InvalidArgumentException("La date de création ne peut pas être dans le futur.");
        }
        
        $this->created_at = $created_at;
        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeInterface
    {
        return $this->updated_at;
    }

    public function setUpdatedAt(?\DateTimeInterface $updated_at): self
    {
        if ($updated_at !== null && $updated_at < $this->created_at) {
            throw new \InvalidArgumentException("La date de mise à jour ne peut pas être antérieure à la date de création.");
        }
        
        $this->updated_at = $updated_at;
        return $this;
    }

    public function getOptionsQuest(): array
    {
        return $this->options_quest;
    }

    public function setOptionsQuest(array $options_quest): self
    {
        // Nettoyage des options
        $options_quest = array_map(function($option) {
            $option = trim($option);
            $option = htmlspecialchars($option, ENT_QUOTES, 'UTF-8');
            return $option;
        }, $options_quest);
        
        // Suppression des doublons
        $options_quest = array_unique($options_quest);
        
        // Réindexation du tableau
        $options_quest = array_values($options_quest);
        
        $this->options_quest = $options_quest;
        return $this;
    }

    public function getScoreOptions(): array
    {
        return $this->score_options;
    }

    public function setScoreOptions(array $score_options): self
    {
        // Validation et nettoyage des scores
        $score_options = array_map(function($score) {
            if (!is_numeric($score)) {
                throw new \InvalidArgumentException("Tous les scores doivent être numériques.");
            }
            return (int) $score;
        }, $score_options);
        
        $this->score_options = $score_options;
        return $this;
    }

    public function getQuestionnaire(): ?Questionnaire
    {
        return $this->questionnaire;
    }

    public function setQuestionnaire(?Questionnaire $questionnaire): self
    {
        if ($questionnaire === null) {
            throw new \InvalidArgumentException("Une question doit être associée à un questionnaire.");
        }
        
        $this->questionnaire = $questionnaire;
        return $this;
    }

    public function __toString(): string
    {
        return sprintf('Question #%d', $this->question_id);
    }

    #[ORM\PreUpdate]
    public function updateTimestamp(): void
    {
        $this->updated_at = new \DateTime();
    }

    #[ORM\PrePersist]
    #[ORM\PreUpdate]
    public function validateEntity(): void
    {
        $this->validateOptionsAndScores();
        
        // Validation spécifique au type
        if ($this->type_question === 'likert' && count($this->options_quest) < 3) {
            throw new \InvalidArgumentException("Une question de type Likert nécessite au moins 3 options.");
        }
        
        if ($this->type_question === 'oui_non' && count($this->options_quest) !== 2) {
            throw new \InvalidArgumentException("Une question Oui/Non doit avoir exactement 2 options.");
        }
        
        if (empty($this->texte)) {
            throw new \InvalidArgumentException("Le texte de la question est obligatoire.");
        }
    }

    public function addOption(string $option, int $score): self
    {
        // Validation de l'option
        $option = trim($option);
        if (empty($option)) {
            throw new \InvalidArgumentException("L'option ne peut pas être vide.");
        }
        
        if (strlen($option) > 255) {
            throw new \InvalidArgumentException("L'option ne peut pas dépasser 255 caractères.");
        }
        
        // Validation du score
        if ($score < -100 || $score > 100) {
            throw new \InvalidArgumentException("Le score doit être compris entre -100 et 100.");
        }
        
        // Vérification des doublons
        if (in_array($option, $this->options_quest, true)) {
            throw new \InvalidArgumentException("Cette option existe déjà.");
        }
        
        $this->options_quest[] = $option;
        $this->score_options[] = $score;
        
        return $this;
    }

    public function removeOption(int $index): self
    {
        if (!isset($this->options_quest[$index])) {
            throw new \InvalidArgumentException("Index d'option invalide.");
        }
        
        unset($this->options_quest[$index]);
        unset($this->score_options[$index]);
        
        // Réindexation
        $this->options_quest = array_values($this->options_quest);
        $this->score_options = array_values($this->score_options);
        
        return $this;
    }

    public function getOptionCount(): int
    {
        return count($this->options_quest);
    }

    public function getOptionsWithScores(): array
    {
        $optionsWithScores = [];
        for ($i = 0; $i < count($this->options_quest); $i++) {
            $optionsWithScores[] = [
                'option' => $this->options_quest[$i] ?? '',
                'score'  => $this->score_options[$i] ?? 0
            ];
        }
        return $optionsWithScores;
    }

    public function getScoreForOption(string $option): ?int
    {
        $option = trim($option);
        $index = array_search($option, $this->options_quest, true);
        
        if ($index !== false && isset($this->score_options[$index])) {
            return $this->score_options[$index];
        }
        
        return null;
    }

    public function getScoreForOptionIndex(int $index): ?int
    {
        if (!is_int($index) || $index < 0) {
            throw new \InvalidArgumentException("L'index doit être un entier positif.");
        }
        
        return $this->score_options[$index] ?? null;
    }

    public function getOptionByIndex(int $index): ?string
    {
        if (!is_int($index) || $index < 0) {
            throw new \InvalidArgumentException("L'index doit être un entier positif.");
        }
        
        return $this->options_quest[$index] ?? null;
    }

    public function validateOptionsAndScores(): bool
    {
        $errors = [];
        
        // Vérification du nombre d'options
        if (count($this->options_quest) !== count($this->score_options)) {
            throw new \InvalidArgumentException("Le nombre d'options doit correspondre au nombre de scores.");
        }
        
        // Minimum 2 options
        if (count($this->options_quest) < 2) {
            throw new \InvalidArgumentException("Une question doit avoir au moins 2 options.");
        }
        
        // Maximum 10 options
        if (count($this->options_quest) > 10) {
            throw new \InvalidArgumentException("Une question ne peut pas avoir plus de 10 options.");
        }
        
        // Validation des options
        foreach ($this->options_quest as $index => $option) {
            if (!is_string($option)) {
                throw new \InvalidArgumentException("L'option à l'index {$index} doit être une chaîne de caractères.");
            }
            
            if (trim($option) === '') {
                throw new \InvalidArgumentException("L'option à l'index {$index} ne peut pas être vide.");
            }
            
            if (strlen($option) > 255) {
                throw new \InvalidArgumentException("L'option à l'index {$index} ne peut pas dépasser 255 caractères.");
            }
        }
        
        // Validation des scores
        foreach ($this->score_options as $index => $score) {
            if (!is_int($score)) {
                throw new \InvalidArgumentException("Le score à l'index {$index} doit être un entier.");
            }
            
            if ($score < -100 || $score > 100) {
                throw new \InvalidArgumentException("Le score à l'index {$index} doit être compris entre -100 et 100.");
            }
        }
        
        // Vérification des doublons d'options
        $uniqueOptions = array_unique($this->options_quest);
        if (count($uniqueOptions) !== count($this->options_quest)) {
            throw new \InvalidArgumentException("Il y a des options en double.");
        }
        
        return true;
    }

    public function getFormChoices(): array
    {
        $choices = [];
        foreach ($this->options_quest as $index => $option) {
            $choices[$option] = $index;
        }
        return $choices;
    }

    public function getMaxScore(): int
    {
        if (empty($this->score_options)) {
            return 0;
        }
        return max($this->score_options);
    }

    public function getMinScore(): int
    {
        if (empty($this->score_options)) {
            return 0;
        }
        return min($this->score_options);
    }

    public function calculateScoreForResponse($response): ?int
    {
        if ($response === null) {
            return null;
        }
        
        if (is_int($response)) {
            if ($response < 0 || $response >= count($this->score_options)) {
                throw new \InvalidArgumentException("Index de réponse invalide.");
            }
            
            return $this->score_options[$response] ?? null;
        } 
        
        if (is_string($response)) {
            return $this->getScoreForOption($response);
        }
        
        throw new \InvalidArgumentException("Type de réponse non supporté.");
    }

    public function getResume(): string
    {
        $optionsText = '';
        for ($i = 0; $i < count($this->options_quest); $i++) {
            $optionsText .= sprintf(
                "\n  %d. %s (score: %d)",
                $i + 1,
                $this->options_quest[$i] ?? '',
                $this->score_options[$i] ?? 0
            );
        }
        
        return sprintf(
            "Question #%d\nTexte: %s\nType: %s\nOptions:%s\nQuestionnaire: %s",
            $this->question_id ?? 'Nouvelle',
            substr($this->texte, 0, 100) . (strlen($this->texte) > 100 ? '...' : ''),
            $this->type_question ?? 'Non défini',
            $optionsText,
            $this->questionnaire ? $this->questionnaire->getNom() : 'Non assigné'
        );
    }

    public static function createFromArray(array $data, Questionnaire $questionnaire): self
    {
        // Validation des données d'entrée
        if (!isset($data['texte'])) {
            throw new \InvalidArgumentException("Le champ 'texte' est obligatoire.");
        }
        
        if (!isset($data['options']) || !is_array($data['options'])) {
            throw new \InvalidArgumentException("Les options sont obligatoires et doivent être un tableau.");
        }
        
        if (!isset($data['scores']) || !is_array($data['scores'])) {
            throw new \InvalidArgumentException("Les scores sont obligatoires et doivent être un tableau.");
        }
        
        $question = new self();
        $question->setTexte($data['texte']);
        $question->setTypeQuestion($data['type_question'] ?? 'likert');
        $question->setOptionsQuest($data['options']);
        $question->setScoreOptions($data['scores']);
        $question->setQuestionnaire($questionnaire);
        
        // Validation finale
        $question->validateEntity();
        
        return $question;
    }

    /**
     * Sanitize all data before persistence
     */
    public function sanitize(): void
    {
        $this->texte = strip_tags($this->texte);
        $this->type_question = strip_tags($this->type_question ?? '');
        
        $this->options_quest = array_map(function($option) {
            return strip_tags($option);
        }, $this->options_quest);
    }
}