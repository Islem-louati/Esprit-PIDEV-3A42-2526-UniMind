<?php

namespace App\Enum;

enum StatutSponsor: string
{
    case CONFIRME = 'confirme';
    case EN_ATTENTE = 'en_attente';
    case REFUSE = 'refuse';
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
            self::CONFIRME => 'Confirmé',
            self::EN_ATTENTE => 'En attente',
            self::REFUSE => 'Refusé',
            self::ANNULE => 'Annulé',
        };
    }
}