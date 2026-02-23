<?php

namespace App\Enum;

enum SaisiPar: string
{
    case ETUDIANT = 'etudiant';
    case PSYCHOLOGUE = 'psychologue';
    case SYSTEME = 'systeme';

    public function getLabel(): string
    {
        return match($this) {
            self::ETUDIANT => 'Étudiant',
            self::PSYCHOLOGUE => 'Psychologue',
            self::SYSTEME => 'Système',
        };
    }

    public static function getValues(): array
    {
        return array_map(fn($case) => $case->value, self::cases());
    }

    public static function getChoices(): array
    {
        return array_combine(
            array_map(fn($case) => $case->getLabel(), self::cases()),
            self::cases()
        );
    }

    public static function getFormChoices(): array
    {
        $choices = [];
        foreach (self::cases() as $case) {
            $choices[$case->getLabel()] = $case->value;
        }
        return $choices;
    }
}
