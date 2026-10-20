<?php

declare(strict_types=1);

namespace App;

interface TennisGame
{
    public function wonPoint(Player $player): void;

    public function getScore(): string;
}