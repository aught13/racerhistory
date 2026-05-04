<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller\Admin;

use App\Test\TestCase\Support\AuthTestTrait;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

/**
 * @link \App\Controller\Admin\TagLookupsController
 */
class TagLookupsControllerTest extends TestCase
{
    use IntegrationTestTrait;
    use AuthTestTrait;

    protected array $fixtures = [
        'app.Users',
        'app.Persons',
        'app.TeamSeasonRosters',
        'app.TeamSeasons',
        'app.Teams',
        'app.Seasons',
        'app.Games',
        'app.Opponents',
    ];

    /**
     * Tests persons requires auth.
     */
    public function testPersonsRequiresAuth(): void
    {
        $this->get('/admin/tag-lookups/persons?q=John');
        $this->assertRedirectContains('/users/login');
    }

    /**
     * Tests persons returns json results.
     */
    public function testPersonsReturnsJsonResults(): void
    {
        $this->mockIdentity();

        $this->get('/admin/tag-lookups/persons?q=John');
        $this->assertResponseOk();
        $this->assertHeaderContains('Content-Type', 'application/json');

        $payload = json_decode((string)$this->_response->getBody(), true);
        $this->assertIsArray($payload);
        $this->assertTrue((bool)($payload['success'] ?? false));
        $this->assertIsArray($payload['persons'] ?? null);

        $labels = array_map(fn($p) => (string)($p['label'] ?? ''), $payload['persons']);
        $this->assertContains('John Doe', $labels);
    }

    /**
     * Tests rosters returns json results.
     */
    public function testRostersReturnsJsonResults(): void
    {
        $this->mockIdentity();

        $this->get('/admin/tag-lookups/rosters?person_id=1');
        $this->assertResponseOk();
        $this->assertHeaderContains('Content-Type', 'application/json');

        $payload = json_decode((string)$this->_response->getBody(), true);
        $this->assertIsArray($payload);
        $this->assertTrue((bool)($payload['success'] ?? false));
        $this->assertIsArray($payload['rosters'] ?? null);
        $this->assertNotEmpty($payload['rosters']);

        $first = $payload['rosters'][0] ?? [];
        $this->assertArrayHasKey('id', $first);
        $this->assertArrayHasKey('label', $first);
    }

    /**
     * Tests games label uses hrn punctuation.
     */
    public function testGamesLabelUsesHrnPunctuation(): void
    {
        $this->mockIdentity();

        $this->get('/admin/tag-lookups/games?teamseason_id=1&q=Belmont');
        $this->assertResponseOk();
        $this->assertHeaderContains('Content-Type', 'application/json');

        $payload = json_decode((string)$this->_response->getBody(), true);
        $this->assertIsArray($payload);
        $this->assertTrue((bool)($payload['success'] ?? false));
        $this->assertIsArray($payload['games'] ?? null);

        $labels = array_map(fn($g) => (string)($g['label'] ?? ''), $payload['games']);

        $this->assertContains('Los Angeles Lakers Vs Belmont (2025-01-15) 75-68', $labels);
        $this->assertContains('Los Angeles Lakers @ Belmont (2025-01-16) 60-61', $labels);
        $this->assertContains('Los Angeles Lakers vs Belmont (2025-01-17) 80-70', $labels);
    }
}
