<?php

namespace App\Enum;

enum TypeQuestionnaire: string
{
    case STRESS = 'stress';
    case ANXIETE = 'anxiete';
    case DEPRESSION = 'depression';
    case BIEN_ETRE = 'bien_etre';
    case SOMMEIL = 'sommeil';

    public function getLabel(): string
    {
        return match($this) {
            self::STRESS => 'Stress',
            self::ANXIETE => 'Anxiété',
            self::DEPRESSION => 'Dépression',
            self::BIEN_ETRE => 'Bien-être',
            self::SOMMEIL => 'Sommeil',
        };
    }

    public static function getValues(): array
    {
        return array_column(self::cases(), 'value');
    }
}