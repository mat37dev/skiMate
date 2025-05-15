<?php

namespace App\Entity;

enum SkiSlopeLevel: string
{
    case EASY = 'Vert';
    case INTERMEDIATE = 'Bleu';
    case ADVANCED = 'Rouge';
    case EXPERT = 'Noir';
    case FREERIDE = 'Freeride';
    case OTHER = 'Non défini';

    /**
     * Associer une valeur API à un niveau.
     */
    public static function fromApi(string $apiValue): self
    {
        return match (strtolower(trim($apiValue))) {
            'easy' => self::EASY,
            'intermediate' => self::INTERMEDIATE,
            'advanced' => self::ADVANCED,
            'expert' => self::EXPERT,
            'freeride' => self::FREERIDE,
            default => self::OTHER,
        };
    }

    /**
     * Récupérer les valeurs possibles pour les formulaires ou validations.
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
