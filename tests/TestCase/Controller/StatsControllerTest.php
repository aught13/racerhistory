<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller;

use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

/**
 * App\Controller\StatsController Test Case
 */
class StatsControllerTest extends TestCase
{
    use IntegrationTestTrait;

    protected array $fixtures = [
        'app.Sports',
        'app.Teams',
        'app.Seasons',
        'app.TeamSeasons',
        'app.StatBasketSeasonPerson',
        'app.TeamSeasonRosters',
        'app.Persons',
    ];

    public function testIndex(): void
    {
        $this->get('/stats');
        $this->assertResponseOk();
        $this->assertResponseContains('Statistics');
        $this->assertResponseContains('Men\'s Basketball statistics by season');
    }

    public function testIndexDisplaysTeamSeasons(): void
    {
        $this->get('/stats');
        $this->assertResponseOk();

        $teamSeasons = $this->viewVariable('teamSeasons');
        $this->assertIsArray($teamSeasons);
    }

    public function testSeason(): void
    {
        $this->get('/stats/season/1');
        $this->assertResponseOk();
        $this->assertResponseContains('Stats');
    }

    public function testSeasonWithInvalidId(): void
    {
        $this->get('/stats/season/9999');
        $this->assertRedirect();
    }

    public function testSeasonSetsVariables(): void
    {
        $this->get('/stats/season/1');
        $this->assertResponseOk();

        $teamSeason = $this->viewVariable('teamSeason');
        $this->assertNotNull($teamSeason);

        $playerStats = $this->viewVariable('playerStats');
        $this->assertIsArray($playerStats);
    }

    public function testAuthorizationSkipped(): void
    {
        // Public pages should not require authentication
        $this->get('/stats');
        $this->assertResponseOk();

        $this->get('/stats/season/1');
        $this->assertResponseOk();
    }
}
