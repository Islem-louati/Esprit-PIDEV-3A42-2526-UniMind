<?php

namespace App\Enum;

enum TypeSponsor: string
{
    case MUTUELLE = 'mutuelle';
    case ENTREPRISE = 'entreprise';
    case ASSOCIATION = 'association';
    case FONDATION = 'fondation';

    public function label(): string
    {
        return match ($this) {
            self::MUTUELLE => 'Mutuelle',
            self::ENTREPRISE => 'Entreprise',
            self::ASSOCIATION => 'Association',
            self::FONDATION => 'Fondation',
        };
    }

    public static function getValues(): array
    {
        return array_map(fn($case) => $case->value, self::cases());
    }
}
