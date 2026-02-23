<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'reponse_questionnaire')]
#[ORM\HasLifecycleCallbacks]
class ReponseQuestionnaire
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $reponse_questionnaire_id = null;

    #[ORM\Column(type: 'float')]
    private float $score_totale;

    #[ORM\Column(type: 'json')]
    private array $reponse_quest = [];

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $interpretation = null;

    #[ORM\Column(type: 'datetime')]
    private \DateTimeInterface $created_at;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $updated_at = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $duree_passage = null;

    #[ORM\Column(type: 'string', length: 20)]
    private string $niveau;

    #[ORM\Column(type: 'boolean')]
    private bool $a_besoin_psy;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $commentaire = null;

    #[ORM\ManyToOne(targetEntity: Questionnaire::class, inversedBy: 'reponses')]
    #[ORM\JoinColumn(name: 'questionnaire_id', referencedColumnName: 'questionnaire_id', nullable: false)]
    private ?Questionnaire $questionnaire = null;

    // ✅ MODIFICATION 1: Rendre l'étudiant OPTIONNEL
    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'reponsesQuestionnaires')]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'user_id', nullable: true)] // nullable: true
    private ?User $etudiant = null;

    public function __construct()
    {
        $this->created_at = new \DateTime();
        $this->a_besoin_psy = false;
    }

    // Getters et setters
    public function getReponseQuestionnaireId(): ?int
    {
        return $this->reponse_questionnaire_id;
    }

    public function getScoreTotale(): float
    {
        return $this->score_totale;
    }

    public function setScoreTotale(float $score_totale): self
    {
        $this->score_totale = $score_totale;
        return $this;
    }

    public function getReponseQuest(): array
    {
        return $this->reponse_quest;
    }

    public function setReponseQuest(array $reponse_quest): self
    {
        $this->reponse_quest = $reponse_quest;
        return $this;
    }

    public function getInterpretation(): ?string
    {
        return $this->interpretation;
    }

    public function setInterpretation(?string $interpretation): self
    {
        $this->interpretation = $interpretation;
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

    public function getDureePassage(): ?int
    {
        return $this->duree_passage;
    }

    public function setDureePassage(?int $duree_passage): self
    {
        $this->duree_passage = $duree_passage;
        return $this;
    }

    public function getNiveau(): string
    {
        return $this->niveau;
    }

    public function setNiveau(string $niveau): self
    {
        $this->niveau = $niveau;
        return $this;
    }

    public function isABesoinPsy(): bool
    {
        return $this->a_besoin_psy;
    }

    public function setABesoinPsy(bool $a_besoin_psy): self
    {
        $this->a_besoin_psy = $a_besoin_psy;
        return $this;
    }

    public function getCommentaire(): ?string
    {
        return $this->commentaire;
    }

    public function setCommentaire(?string $commentaire): self
    {
        $this->commentaire = $commentaire;
        return $this;
    }

    public function getQuestionnaire(): ?Questionnaire
    {
        return $this->questionnaire;
    }

    public function setQuestionnaire(?Questionnaire $questionnaire): self
    {
        $this->questionnaire = $questionnaire;
        return $this;
    }

    public function getEtudiant(): ?User
    {
        return $this->etudiant;
    }

    // ✅ MODIFICATION 2: Supprimer la vérification stricte
    public function setEtudiant(?User $etudiant): self
    {
        $this->etudiant = $etudiant;
        return $this;
    }

    // Méthodes utilitaires
    public function __toString(): string
    {
        return sprintf(
            'Réponse #%d - Score: %.1f',
            $this->reponse_questionnaire_id,
            $this->score_totale
        );
    }

    #[ORM\PreUpdate]
    public function updateTimestamp(): void
    {
        $this->updated_at = new \DateTime();
    }

    // Méthodes pour gérer les réponses JSON
    public function addReponse(int $questionId, $reponse): self
    {
        $this->reponse_quest[$questionId] = $reponse;
        return $this;
    }

    public function getReponseForQuestion(int $questionId)
    {
        return $this->reponse_quest[$questionId] ?? null;
    }

    public function removeReponse(int $questionId): self
    {
        unset($this->reponse_quest[$questionId]);
        return $this;
    }

    public function getReponsesCount(): int
    {
        return count($this->reponse_quest);
    }

    // Méthode pour calculer le score à partir des réponses
    public function calculateScoreFromResponses(): float
    {
        if (!$this->questionnaire) {
            return 0;
        }
        
        $totalScore = 0;
        
        foreach ($this->reponse_quest as $questionId => $reponse) {
            // Trouver la question dans le questionnaire
            $question = $this->findQuestionInQuestionnaire($questionId);
            if ($question) {
                $score = $question->calculateScoreForResponse($reponse);
                if ($score !== null) {
                    $totalScore += $score;
                }
            }
        }
        
        return $totalScore;
    }

    private function findQuestionInQuestionnaire(int $questionId): ?Question
    {
        if (!$this->questionnaire) {
            return null;
        }
        
        foreach ($this->questionnaire->getQuestions() as $question) {
            if ($question->getQuestionId() === $questionId) {
                return $question;
            }
        }
        
        return null;
    }

    // Méthode pour déterminer le niveau en fonction du score
    public function determineNiveau(): string
    {
        if (!$this->questionnaire) {
            return 'indéterminé';
        }
        
        if ($this->score_totale <= $this->questionnaire->getSeuilLeger()) {
            return 'léger';
        } elseif ($this->score_totale <= $this->questionnaire->getSeuilModere()) {
            return 'modéré';
        } else {
            return 'sévère';
        }
    }

    // Méthode pour déterminer si besoin d'un psy
    public function determineBesoinPsy(): bool
    {
        return in_array($this->niveau, ['modéré', 'sévère'], true);
    }

    // Méthode pour obtenir l'interprétation automatique
    public function getInterpretationAutomatique(): string
    {
        if (!$this->questionnaire) {
            return 'Questionnaire non trouvé';
        }
        
        return $this->questionnaire->getInterpretationForScore((int)$this->score_totale);
    }

    // Méthode pour traiter et valider les réponses
    public function processResponses(): self
    {
        // Calculer le score total
        $this->score_totale = $this->calculateScoreFromResponses();
        
        // Déterminer le niveau
        $this->niveau = $this->determineNiveau();
        
        // Déterminer si besoin d'un psychologue
        $this->a_besoin_psy = $this->determineBesoinPsy();
        
        // Générer l'interprétation automatique si pas déjà fournie
        if (empty($this->interpretation)) {
            $this->interpretation = $this->getInterpretationAutomatique();
        }
        
        return $this;
    }

    // Méthode pour obtenir la durée formatée
    public function getDureeFormatee(): ?string
    {
        if ($this->duree_passage === null) {
            return null;
        }
        
        $minutes = floor($this->duree_passage / 60);
        $secondes = $this->duree_passage % 60;
        
        if ($minutes > 0) {
            return sprintf('%d min %d sec', $minutes, $secondes);
        }
        
        return sprintf('%d sec', $secondes);
    }

    // Méthode pour vérifier si la réponse est complète
    public function isComplete(): bool
    {
        if (!$this->questionnaire) {
            return false;
        }
        
        return $this->getReponsesCount() >= $this->questionnaire->getNbreQuestions();
    }

    // Méthode pour obtenir le pourcentage de complétion
    public function getCompletionPercentage(): float
    {
        if (!$this->questionnaire || $this->questionnaire->getNbreQuestions() === 0) {
            return 0;
        }
        
        $percentage = ($this->getReponsesCount() / $this->questionnaire->getNbreQuestions()) * 100;
        
        return min($percentage, 100);
    }

    // Méthode pour obtenir les réponses détaillées
    public function getReponsesDetaillees(): array
    {
        $details = [];
        
        foreach ($this->reponse_quest as $questionId => $reponse) {
            $question = $this->findQuestionInQuestionnaire($questionId);
            if ($question) {
                $score = $question->calculateScoreForResponse($reponse);
                $details[] = [
                    'question_id' => $questionId,
                    'question_texte' => $question->getTexte(),
                    'reponse' => $reponse,
                    'score' => $score,
                    'question' => $question
                ];
            }
        }
        
        return $details;
    }

    // Méthode pour obtenir un résumé
    public function getResume(): string
    {
        return sprintf(
            "Réponse #%d\n" .
            "Questionnaire: %s\n" .
            "Score: %.1f/%.1f\n" .
            "Niveau: %s\n" .
            "Besoin psy: %s\n" .
            "Date: %s\n" .
            "Durée: %s\n" .
            "Complétion: %.1f%%",
            $this->reponse_questionnaire_id,
            $this->questionnaire ? $this->questionnaire->getNom() : 'Non défini',
            $this->score_totale,
            $this->questionnaire ? $this->getScoreMaxPossible() : 0,
            $this->niveau,
            $this->a_besoin_psy ? 'Oui' : 'Non',
            $this->created_at->format('d/m/Y H:i'),
            $this->getDureeFormatee() ?? 'Non enregistrée',
            $this->getCompletionPercentage()
        );
    }

    // Méthode pour obtenir le score maximum possible
    public function getScoreMaxPossible(): float
    {
        if (!$this->questionnaire) {
            return 0;
        }
        
        $maxScore = 0;
        foreach ($this->questionnaire->getQuestions() as $question) {
            $maxScore += $question->getMaxScore();
        }
        
        return $maxScore;
    }

    // ✅ MODIFICATION 3: Rendre l'étudiant optionnel dans createFromResponses
    public static function createFromResponses(
        Questionnaire $questionnaire,
        ?User $etudiant = null, // Optionnel avec valeur par défaut null
        array $reponses = [],
        ?int $dureePassage = null,
        ?string $commentaire = null
    ): self {
        $reponseQuestionnaire = new self();
        $reponseQuestionnaire->setQuestionnaire($questionnaire);
        $reponseQuestionnaire->setEtudiant($etudiant); // Peut être null
        $reponseQuestionnaire->setReponseQuest($reponses);
        $reponseQuestionnaire->setDureePassage($dureePassage);
        $reponseQuestionnaire->setCommentaire($commentaire);
        $reponseQuestionnaire->processResponses();
        
        return $reponseQuestionnaire;
    }
}