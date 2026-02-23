<?php

namespace App\Enum;

enum TypeEvenement: string
{
    case ATELIER = 'atelier';
    case WEBINAIRE = 'formation';
    case GROUPE_PAROLE = 'groupe_parole';
    case CONFERENCE = 'conference';
    case JOURNEE_THEMATIQUE = 'journee_thematique';
    case ACTIVITE_SPORTIVE = 'activite_sportive';

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
            self::ATELIER => 'Atelier',
            self::WEBINAIRE => 'Formation',
            self::GROUPE_PAROLE => 'Groupe de parole',
            self::CONFERENCE => 'Conférence',
            self::JOURNEE_THEMATIQUE => 'Journée thématique',
            self::ACTIVITE_SPORTIVE => 'Activité sportive',
        };
    }
}