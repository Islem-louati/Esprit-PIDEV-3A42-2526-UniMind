<?php

namespace App\Service;

use App\Entity\Question;
use App\Entity\Questionnaire;
use GuzzleHttp\Client;
use Doctrine\ORM\EntityManagerInterface;

class IaQuestionGeneratorService
{
    private Client $client;
    private string $apiKey;
    private EntityManagerInterface $em;

    public function __construct(string $huggingfaceApiKey, EntityManagerInterface $em)
    {
        $this->client = new Client([
            'timeout' => 60,
            'verify'  => false
        ]);
        $this->apiKey = $huggingfaceApiKey;
        $this->em = $em;
    }

    public function genererQuestions(string $thematique, int $nombre = 10): array
    {
        $prompt = $this->construirePrompt($thematique, $nombre);

        try {
            $response = $this->client->post(
                'https://router.huggingface.co/v1/chat/completions',
                [
                    'headers' => [
                        'Authorization' => 'Bearer ' . $this->apiKey,
                        'Content-Type'  => 'application/json',
                    ],
                    'json' => [
                        'model'    => 'meta-llama/Llama-3.1-8B-Instruct:cerebras',
                        'messages' => [
                            [
                                'role'    => 'user',
                                'content' => $prompt
                            ]
                        ],
                        'max_tokens'  => 2000,
                        'temperature' => 0.7,
                    ]
                ]
            );

            $result      = json_decode($response->getBody(), true);
            $texteGenere = $result['choices'][0]['message']['content'] ?? '';

            return $this->extraireQuestions($texteGenere);

        } catch (\Exception $e) {
            throw new \RuntimeException('Erreur IA : ' . $e->getMessage());
        }
    }

    private function construirePrompt(string $thematique, int $nombre): string
    {
        return <<<PROMPT
Tu es un psychologue expert créant des questionnaires d'évaluation psychologique.

Thématique : $thematique
Nombre de questions : $nombre

Génère exactement $nombre questions pertinentes et professionnelles.

Format STRICT :
Q1. [Texte de la question]
Q2. [Texte de la question]

RÈGLES :
- Questions claires pour étudiants universitaires
- Variées couvrant différents aspects
- Sans jargon médical complexe
- Questions mesurables

Options : Jamais, Rarement, Parfois, Souvent, Toujours
Scores : 0, 1, 2, 3, 4

Exemple :
Q1. Avez-vous du mal à vous endormir le soir ?
Q2. Ressentez-vous de l'anxiété avant les examens ?

Commence :
PROMPT;
    }

    private function extraireQuestions(string $texte): array
    {
        $questions = [];

        preg_match_all('/(?:Q)?(\d+)[\.\:]\s*(.+?)(?=(?:Q)?\d+[\.\:]|$)/s', $texte, $matches, PREG_SET_ORDER);

        foreach ($matches as $match) {
            $texteQuestion = trim($match[2]);
            $texteQuestion = $this->nettoyerTexteQuestion($texteQuestion);

            if (!empty($texteQuestion) && strlen($texteQuestion) > 10 && strlen($texteQuestion) < 500) {
                $questions[] = [
                    'texte'         => $texteQuestion,
                    'type_question' => 'likert',
                    'options_quest' => ['Jamais', 'Rarement', 'Parfois', 'Souvent', 'Toujours'],
                    'score_options' => [0, 1, 2, 3, 4]
                ];
            }
        }

        return array_slice($questions, 0, 10);
    }

    private function nettoyerTexteQuestion(string $texte): string
    {
        $texte = preg_replace('/\n+/', ' ', $texte);
        $texte = preg_replace('/\s+/', ' ', $texte);
        $texte = preg_replace('/Options?\s*:.*$/i', '', $texte);
        $texte = preg_replace('/\(Jamais.*?\)/i', '', $texte);

        // ✅ Correction apostrophes : &#039; → '
        $texte = html_entity_decode($texte, ENT_QUOTES, 'UTF-8');

        $texte = trim($texte);
        if (!str_ends_with($texte, '?') && !str_ends_with($texte, '.')) {
            $texte .= ' ?';
        }

        return $texte;
    }

    public function creerQuestionsDepuisIA(array $questionsIA, Questionnaire $questionnaire): array
    {
        $questionsCreees = [];

        foreach ($questionsIA as $qData) {
            $question = new Question();
            $question->setTexte($qData['texte']);
            $question->setTypeQuestion($qData['type_question']);
            $question->setOptionsQuest($qData['options_quest']);
            $question->setScoreOptions($qData['score_options']);
            $question->setQuestionnaire($questionnaire);

            $this->em->persist($question);
            $questionsCreees[] = $question;
        }

        $this->em->flush();

        return $questionsCreees;
    }

    public function validerQuestions(array $questions): array
    {
        $valides = [];

        foreach ($questions as $question) {
            $texte = $question['texte'];

            $longueurOk   = strlen($texte) >= 15 && strlen($texte) <= 300;
            $pasTropCourt = str_word_count($texte) >= 5;

            if ($longueurOk && $pasTropCourt) {
                $valides[] = $question;
            }
        }

        return $valides;
    }
}