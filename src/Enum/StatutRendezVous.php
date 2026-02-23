<?php

namespace App\Enum;

enum StatutRendezVous: string
{
    case DEMANDE = 'demande';
    case CONFIRME = 'confirme';
    case EN_COURS = 'en-cours';
    case TERMINE = 'terminé';
    case ANNULE = 'annulé';
    case ABSENT = 'absent';

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
            self::DEMANDE => 'Demande',
            self::CONFIRME => 'Confirmé',
            self::EN_COURS => 'En cours',
            self::TERMINE => 'Terminé',
            self::ANNULE => 'Annulé',
            self::ABSENT => 'Absent',
        };
    }
}