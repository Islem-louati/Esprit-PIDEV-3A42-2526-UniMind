<?php

namespace App\Enum;

enum StatutDisponibilite: string
{
    case DISPONIBLE = 'disponible';
    case RESERVE = 'réservé';
    case ANNULE = 'annulé';

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
            self::DISPONIBLE => 'Disponible',
            self::RESERVE => 'Réservé',
            self::ANNULE => 'Annulé',
        };
    }
}