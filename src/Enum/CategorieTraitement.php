<?php

namespace App\Enum;

enum CategorieTraitement: string
{
    case RELAXATION = 'relaxation';
    case COGNITIF = 'cognitif';
    case EMOTIONNEL = 'emotionnel';
    case COMPORTEMENTAL = 'comportemental';

    public static function getValues(): array
    {
        return array_column(self::cases(), 'value');
    }
    
    public static function getChoices(): array
    {
        $choices = [];
        foreach (self::cases() as $case) {
            $choices[$case->value] = $case->value;
        }
        return $choices;
    }
    
    public function getLabel(): string
    {
        return match($this) {
            self::RELAXATION => 'Relaxation',
            self::COGNITIF => 'Cognitif',
            self::EMOTIONNEL => 'Émotionnel',
            self::COMPORTEMENTAL => 'Comportemental',
        };
    }
}