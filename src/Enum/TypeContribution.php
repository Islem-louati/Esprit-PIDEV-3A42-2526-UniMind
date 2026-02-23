<?php

namespace App\Enum;

enum TypeContribution: string
{
    case FINANCIER = 'financier';
    case MATERIEL = 'materiel';
    case LOGISTIQUE = 'logistique';
    case COMMUNICATION = 'communication';
    case AUTRE = 'autre';

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
            self::FINANCIER => 'Financière',
            self::MATERIEL => 'Matérielle',
            self::LOGISTIQUE => 'Logistique',
            self::COMMUNICATION => 'Communication',
            self::AUTRE => 'Autre',
        };
    }
}