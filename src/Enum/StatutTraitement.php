<?php

namespace App\Enum;

enum StatutTraitement: string
{
    case EN_COURS = 'en cours';
    case TERMINE = 'termine';
    case SUSPENDU = 'suspendu';

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
            self::EN_COURS => 'En cours',
            self::TERMINE => 'Terminé',
            self::SUSPENDU => 'Suspendu',
        };
    }
}