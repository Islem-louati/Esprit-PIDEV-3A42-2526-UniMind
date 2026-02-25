<?php

namespace App\Enum;

enum Ressenti: string
{
    case TRES_BIEN = 'très_bien';
    case BIEN = 'bien';
    case NEUTRE = 'neutre';
    case DIFFICILE = 'difficile';
    case TRES_DIFFICILE = 'très_difficile';

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
            self::TRES_BIEN => 'Très bien',
            self::BIEN => 'Bien',
            self::NEUTRE => 'Neutre',
            self::DIFFICILE => 'Difficile',
            self::TRES_DIFFICILE => 'Très difficile',
        };
    }
}
