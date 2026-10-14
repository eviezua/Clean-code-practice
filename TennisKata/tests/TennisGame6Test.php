<?php
declare(strict_types=1);

namespace App\Tests;


use App\TennisGame6;
use PHPUnit\Framework\Attributes\DataProvider;

class TennisGame6Test extends TestMaster {

    protected function setUp(): void
    {
        parent::setUp();
        $this->game = new TennisGame6('player1', 'player2');
    }

    #[DataProvider('data')]
    public function testScores(int $score1, int $score2, string $expectedResult): void
    {
        $this->seedScores($score1, $score2);
        $this->assertSame($expectedResult, $this->game->getScore());
    }
}