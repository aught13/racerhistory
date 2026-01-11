<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller\Api\V1;

use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

class TeamSeasonsControllerTest extends TestCase
{
    use IntegrationTestTrait;

    protected array $fixtures = [
        'app.Sports',
        'app.Teams',
        'app.Seasons',
        'app.TeamSeasons',
    ];

    public function testIndexDefault(): void
    {
        $this->get('/api/v1/team-seasons?limit=10');
        $this->assertResponseOk();
        $this->assertHeaderContains('Content-Type', 'application/json');

        $payload = json_decode((string)$this->_response->getBody(), true);
        $this->assertIsArray($payload);
        $this->assertIsArray($payload['data'] ?? null);
        $this->assertNotEmpty($payload['data']);

        $first = $payload['data'][0] ?? [];
        $this->assertArrayHasKey('id', $first);
        $this->assertArrayHasKey('label', $first);
    }

    public function testIndexIncludeDetails(): void
    {
        $this->get('/api/v1/team-seasons?include=details&limit=10');
        $this->assertResponseOk();
        $this->assertHeaderContains('Content-Type', 'application/json');

        $payload = json_decode((string)$this->_response->getBody(), true);
        $this->assertIsArray($payload);
        $this->assertIsArray($payload['data'] ?? null);

        $first = $payload['data'][0] ?? [];
        $this->assertArrayHasKey('team', $first);
        $this->assertArrayHasKey('season', $first);
    }

    public function testView(): void
    {
        $this->get('/api/v1/team-seasons/1');
        $this->assertResponseOk();
        $this->assertHeaderContains('Content-Type', 'application/json');

        $payload = json_decode((string)$this->_response->getBody(), true);
        $this->assertIsArray($payload);
        $this->assertSame(1, $payload['data']['id'] ?? null);
        $this->assertSame(1, $payload['data']['team_id'] ?? null);
        $this->assertSame(1, $payload['data']['season_id'] ?? null);
        $this->assertIsArray($payload['data']['team'] ?? null);
    }

    public function testViewNotFound(): void
    {
        $this->get('/api/v1/team-seasons/999');
        $this->assertResponseCode(404);
    }
}
