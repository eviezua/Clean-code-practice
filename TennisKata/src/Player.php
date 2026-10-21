<?php

namespace App;

class Player
{
    public function __construct(
        private readonly string $name,
        private int $score = 0
    ) {
    }

    public function addPoint(): void
    {
        $this->score++;
    }

    public function getScore(): int
    {
        return $this->score;
    }

    public function getName(): string
    {
        return $this->name;
    }
}