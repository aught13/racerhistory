<?php
declare(strict_types=1);

namespace App\Test\TestCase\Model\Table;

use Cake\TestSuite\TestCase;
use DateTime;

class GamesTableFutureValidationTest extends TestCase
{
    public array $fixtures = ['app.Games', 'app.TeamSeasons'];

    /**
     * Games table instance.
     *
     * @var \App\Model\Table\GamesTable
     */
    protected $Games;

    /**
     * Sets up the test case.
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->Games = $this->getTableLocator()->get('Games');
    }

    /**
     * Tears down the test case.
     */
    public function tearDown(): void
    {
        unset($this->Games);

        parent::tearDown();
    }

    /**
     * Tests future game cannot have scores or result.
     */
    public function testFutureGameCannotHaveScoresOrResult(): void
    {
        $futureDate = (new DateTime('tomorrow'))->format('Y-m-d');
        $data = [
            'team_season_id' => 1,
            'game_date' => $futureDate,
            'pts_mur' => 10,
            'pts_opp' => 8,
            'w' => 'W',
        ];

        $game = $this->Games->newEntity($data);
        $this->assertNotEmpty($game->getErrors(), 'Expected validation errors for future game with scores/result');
    }

    /**
     * Tests future game without scores is valid.
     */
    public function testFutureGameWithoutScoresIsValid(): void
    {
        $futureDate = (new DateTime('tomorrow'))->format('Y-m-d');
        $data = [
            'team_season_id' => 1,
            'game_date' => $futureDate,
        ];

        $game = $this->Games->newEntity($data);
        $this->assertEmpty($game->getErrors(), 'Expected no validation errors for future game without scores/result');
    }

    /**
     * Tests past game with scores is valid.
     */
    public function testPastGameWithScoresIsValid(): void
    {
        $pastDate = (new DateTime('yesterday'))->format('Y-m-d');
        $data = [
            'team_season_id' => 1,
            'game_date' => $pastDate,
            'pts_mur' => 20,
            'pts_opp' => 15,
            'w' => 'W',
        ];

        $game = $this->Games->newEntity($data);
        $this->assertEmpty($game->getErrors(), 'Expected no validation errors for past game with scores/result');
    }
}
