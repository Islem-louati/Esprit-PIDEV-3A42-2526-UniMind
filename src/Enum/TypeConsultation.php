<?php

namespace App\Enum;

enum TypeConsultation: string
{
    case PRESENTIEL = 'présentiel';
    case EN_LIGNE = 'en ligne';

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
            self::PRESENTIEL => 'Présentiel',
            self::EN_LIGNE => 'En ligne',
        };
    }
}