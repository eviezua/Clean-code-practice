<?php

namespace App;

enum ScoreNames: string
{
    case Love = 'Love';
    case Fifteen = 'Fifteen';
    case Thirty = 'Thirty';
    case Forty = 'Forty';

    public static function estimateLabelByScore(int $score): string
    {
        return match ($score) {
            0 => ScoreNames::Love->value,
            1 => ScoreNames::Fifteen->value,
            2 => ScoreNames::Thirty->value,
            default => ScoreNames::Forty->value
        };
    }
}