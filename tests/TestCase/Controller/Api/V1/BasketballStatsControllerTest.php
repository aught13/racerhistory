<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller\Api\V1;

use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

class BasketballStatsControllerTest extends TestCase
{
    use IntegrationTestTrait;

    protected array $fixtures = [
        'app.Sports',
        'app.Teams',
        'app.Seasons',
        'app.TeamSeasons',
        'app.Opponents',
        'app.GameTypes',
        'app.Places',
        'app.Sites',
        'app.Games',
        'app.TeamSeasonRosters',
        'app.Persons',
        'app.StatBasketGamePerson',
        'app.StatBasketGameTeam',
        'app.StatBasketGameOpponent',
        'app.StatBasketGameBox',
        'app.StatBasketSeasonPerson',
        'app.StatBasketSeasonTeam',
        'app.StatBasketSeasonOpponent',
    ];

    public function testGameStats(): void
    {
        $this->get('/api/v1/basketball-stats/games/1');
        $this->assertResponseOk();
        $this->assertHeaderContains('Content-Type', 'application/json');

        $payload = json_decode((string)$this->_response->getBody(), true);
        $this->assertIsArray($payload);
        $this->assertIsArray($payload['data'] ?? null);
        $this->assertArrayHasKey('teamBoxStats', $payload['data']);
        $this->assertArrayHasKey('playerStats', $payload['data']);
    }

    public function testGameStatsNotFound(): void
    {
        $this->get('/api/v1/basketball-stats/games/999');
        $this->assertResponseCode(404);
    }

    public function testSeasonStats(): void
    {
        $this->get('/api/v1/basketball-stats/team-seasons/1');
        $this->assertResponseOk();
        $this->assertHeaderContains('Content-Type', 'application/json');

        $payload = json_decode((string)$this->_response->getBody(), true);
        $this->assertIsArray($payload);
        $this->assertIsArray($payload['data'] ?? null);
        $this->assertArrayHasKey('playerStats', $payload['data']);
        $this->assertArrayHasKey('teamStats', $payload['data']);
        $this->assertArrayHasKey('opponentStats', $payload['data']);
    }

    public function testSeasonStatsNotFound(): void
    {
        $this->get('/api/v1/basketball-stats/team-seasons/999');
        $this->assertResponseCode(404);
    }
}
