<?php
declare(strict_types=1);

namespace App\Test\TestCase\Model\Table;

use Cake\TestSuite\TestCase;

class GamesTableSeasonAndCumulativeTest extends TestCase
{
    public array $fixtures = ['app.Games', 'app.TeamSeasons', 'app.Seasons', 'app.GameEav'];

    protected $Games;

    public function setUp(): void
    {
        parent::setUp();
        $this->Games = $this->getTableLocator()->get('Games');
    }

    public function tearDown(): void
    {
        unset($this->Games);
        parent::tearDown();
    }

    public function testGameDateOutsideSeasonIsInvalid(): void
    {
        // TeamSeason 1 uses Season 1 from fixture; set a date outside its range
        $data = [
            'team_season_id' => 1,
            'game_date' => '2099-01-01',
        ];

        $game = $this->Games->newEntity($data);
        $this->assertNotEmpty($game->getErrors(), 'Expected validation error for date outside season');
    }

    public function testCumulativePeriodSumsMustMatchTotals(): void
    {
        $data = [
            'team_season_id' => 1,
            // Use a date inside Season 1 (2023-2024) from the fixture
            'game_date' => '2023-10-01',
            'pts_mur' => 50,
            'pts_opp' => 45,
            // EAV style fields
            'period_1_team' => 20,
            'period_2_team' => 25,
            'period_1_opponent' => 20,
            'period_2_opponent' => 25,
        ];

        // Totals do not match (team 45 vs pts_mur 50)
        $game = $this->Games->newEntity($data);
        $this->assertNotEmpty($game->getErrors(), 'Expected validation error for mismatched cumulative totals');

    // Fix totals to match
        $data['period_2_team'] = 30; // now 20+30=50
        $game = $this->Games->newEntity($data);
        $this->assertEmpty($game->getErrors(), 'Expected no validation errors when cumulative sums match');
    }
}
