<?php

namespace App\Enum;

enum PrioriteTraitement: string
{
    case BASSE = 'basse';
    case MOYENNE = 'moyenne';
    case HAUTE = 'haute';

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
            self::BASSE => 'Basse',
            self::MOYENNE => 'Moyenne',
            self::HAUTE => 'Haute',
        };
    }
}