<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class TraductionService
{
    public function __construct(
        private HttpClientInterface $httpClient
    ) {}

    public function traduire(string $texte, string $langueSource = 'fr', string $langueCible = 'ar'): string
    {
        try {
            $response = $this->httpClient->request('GET', 'https://api.mymemory.translated.net/get', [
                'query' => [
                    'q' => $texte,
                    'langpair' => $langueSource . '|' . $langueCible,
                ]
            ]);

            $data = $response->toArray();

            if (isset($data['responseData']['translatedText'])) {
                return $data['responseData']['translatedText'];
            }

            return $texte;

        } catch (\Exception $e) {
            return $texte;
        }
    }

  public function traduireQuestionnaire(array $questions, string $langueCible = 'ar'): array
{
    $traductions = [];
    foreach ($questions as $question) {
        $texte = html_entity_decode($question->getTexte(), ENT_QUOTES, 'UTF-8');
        $traductions[] = [
            'original' => $texte,
            'traduit' => $this->traduire($texte, 'fr', $langueCible),
        ];
    }
    return $traductions;
}
}