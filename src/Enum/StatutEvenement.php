<?php

namespace App\Enum;

enum StatutEvenement: string
{
    case A_VENIR = 'a_venir';
    case EN_COURS = 'en_cours';
    case TERMINE = 'termine';
    case ANNULE = 'annule';

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
            self::A_VENIR => 'À venir',
            self::EN_COURS => 'En cours',
            self::TERMINE => 'Terminé',
            self::ANNULE => 'Annulé',
        };
    }

    public function isAVenir(): bool
    {
        return $this === self::A_VENIR;
    }

    public function isEnCours(): bool
    {
        return $this === self::EN_COURS;
    }

    public function isTermine(): bool
    {
        return $this === self::TERMINE;
    }

    public function isAnnule(): bool
    {
        return $this === self::ANNULE;
    }
}