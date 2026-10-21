<?php

namespace App;

class TennisGame6 implements TennisGame {

    public function __construct(
        private readonly Player $player1,
        private readonly Player $player2
    ) {
    }

    public function wonPoint(Player $player): void
    {
        if ($player === $this->player1 || $player === $this->player2) {
            $player->addPoint();
        }
    }

    public function getScore(): string
    {
        $player1Score = $this->player1->getScore();
        $player2Score = $this->player2->getScore();

        if ($player1Score == $player2Score) {
            return $this->tieScore($player1Score);
        }

        if ($player1Score >= 4 || $player2Score >= 4) {
            return $this->endGameScore($player1Score, $player2Score);
        }

        return $this->regularScore($player1Score, $player2Score);
    }

    private function tieScore(int $score): string
    {
        if ($score <= 2) {
            return $this->getScoreLabel($score) . '-All';
        }

        return 'Deuce';
    }

    private function endGameScore(int $score1, int $score2): string
    {
        $scoreDifference = abs($score1 - $score2);

        if ($scoreDifference == 1) {
            return "Advantage " . $this->determineLeader($score1, $score2);
        }

        return "Win for " . $this->determineLeader($score1, $score2);
    }

    private function regularScore(int $score1, int $score2): string
    {
        return $this->getScoreLabel($score1) . "-" . $this->getScoreLabel($score2);
    }

    private function determineLeader(int $score1, int $score2): string
    {
        if ($score1 > $score2) {
            return $this->player1->getName();
        }

        return $this->player2->getName();
    }

    private function getScoreLabel(int $score): string
    {
        return ScoreNames::estimateLabelByScore($score);
    }
}
