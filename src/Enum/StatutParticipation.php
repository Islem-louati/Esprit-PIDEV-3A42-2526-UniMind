<?php

namespace App\Enum;

enum StatutParticipation: string
{
    case CONFIRME = 'confirme';
    case ATTENTE = 'attente';
    case ANNULE = 'annule';

    public function label(): string
    {
        return match ($this) {
            self::CONFIRME => 'Confirmé',
            self::ATTENTE => 'En Attente',
            self::ANNULE => 'Annulé',
        };
    }

    public function getLabel(): string
    {
        return $this->label();
    }

    public static function getValues(): array
    {
        return array_map(fn($case) => $case->value, self::cases());
    }
}
