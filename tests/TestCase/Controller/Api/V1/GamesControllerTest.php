<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller\Api\V1;

use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

class GamesControllerTest extends TestCase
{
    use IntegrationTestTrait;

    protected array $fixtures = [
        'app.Teams',
        'app.Seasons',
        'app.TeamSeasons',
        'app.Sports',
        'app.Places',
        'app.Sites',
        'app.Opponents',
        'app.GameTypes',
        'app.Games',
        'app.GameEav',
        'app.TeamSeasonRosters',
        'app.Persons',
        'app.StatBasketGamePerson',
        'app.StatBasketGameTeam',
        'app.StatBasketGameOpponent',
        'app.StatBasketGameBox',
    ];

    public function testIndexRecent(): void
    {
        $this->get('/api/v1/games?limit=2');
        $this->assertResponseOk();
        $this->assertHeaderContains('Content-Type', 'application/json');

        $payload = json_decode((string)$this->_response->getBody(), true);
        $this->assertIsArray($payload);
        $this->assertIsArray($payload['data'] ?? null);
        $this->assertCount(2, $payload['data']);

        $first = $payload['data'][0] ?? [];
        $this->assertArrayHasKey('id', $first);
        $this->assertArrayHasKey('label', $first);
    }

    public function testIndexSearchByTeamSeasonAndOpponent(): void
    {
        $this->get('/api/v1/games?team_season_id=1&q=Belmont&limit=10');
        $this->assertResponseOk();
        $this->assertHeaderContains('Content-Type', 'application/json');

        $payload = json_decode((string)$this->_response->getBody(), true);
        $this->assertIsArray($payload);
        $this->assertIsArray($payload['data'] ?? null);

        $labels = array_map(fn($g) => (string)($g['label'] ?? ''), $payload['data']);
        $this->assertNotEmpty($labels);
        $this->assertStringContainsString('Belmont', $labels[0]);
    }

    public function testView(): void
    {
        $this->get('/api/v1/games/1');
        $this->assertResponseOk();
        $this->assertHeaderContains('Content-Type', 'application/json');

        $payload = json_decode((string)$this->_response->getBody(), true);
        $this->assertIsArray($payload);
        $this->assertSame(1, $payload['data']['id'] ?? null);
        $this->assertIsArray($payload['data']['eav'] ?? null);
        $this->assertSame('35', $payload['data']['eav']['period_1_team'] ?? null);

        // Basketball stats should be present for the fixture sport
        $this->assertArrayHasKey('basketball_stats', $payload['data']);
        $this->assertIsArray($payload['data']['basketball_stats']);
    }

    public function testViewNotFound(): void
    {
        $this->get('/api/v1/games/999');
        $this->assertResponseCode(404);
    }
}
