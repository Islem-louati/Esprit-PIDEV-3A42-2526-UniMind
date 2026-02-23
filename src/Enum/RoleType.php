<?php

namespace App\Enum;

enum RoleType: string
{
    case ETUDIANT = 'Etudiant';
    case PSYCHOLOGUE = 'Psychologue';
    case RESPONSABLE_ETUDIANT = 'Responsable Etudiant';
    case ADMIN = 'Admin';

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
    
    public static function getFormChoices(): array
    {
        return self::getChoices();
    }
    
    public function getLabel(): string
    {
        return match($this) {
            self::ETUDIANT => 'Étudiant',
            self::PSYCHOLOGUE => 'Psychologue',
            self::RESPONSABLE_ETUDIANT => 'Responsable Étudiant',
            self::ADMIN => 'Administrateur',
        };
    }
}