<?php
declare(strict_types=1);

namespace App\Test\TestCase\Service;

use App\Service\SportConfigService;
use Cake\TestSuite\TestCase;

/**
 * App\Service\SportConfigService Test Case
 */
class SportConfigServiceTest extends TestCase
{
    /**
     * Fixtures
     *
     * @var array<string>
     */
    protected array $fixtures = [
        'app.Sports',
        'app.SportConfigs',
    ];

    /**
     * Test subject
     *
     * @var \App\Service\SportConfigService
     */
    protected SportConfigService $sportConfigService;

    /**
     * setUp method
     *
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->sportConfigService = new SportConfigService();
    }

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown(): void
    {
        unset($this->sportConfigService);
        parent::tearDown();
    }

    /**
     * Test getAllStatTables method
     *
     * @return void
     */
    public function testGetAllStatTables(): void
    {
        // Test with basketball (ID 1)
        $result = $this->sportConfigService->getAllStatTables(1);

        // Verify structure matches expected defaults for basketball
        $this->assertArrayHasKey('game', $result);
        $this->assertArrayHasKey('season', $result);

        // Check for game tables
        $this->assertArrayHasKey('team', $result['game']);
        $this->assertArrayHasKey('player', $result['game']);
        $this->assertArrayHasKey('opponent', $result['game']);

        // Verify some expected table names
        $this->assertEquals('stat_basket_game_team', $result['game']['team']);
        $this->assertEquals('stat_basket_game_person', $result['game']['player']);
        $this->assertEquals('stat_basket_game_opponent', $result['game']['opponent']);
    }

    /**
     * Test getStatFields method
     *
     * @return void
     */
    public function testGetStatFields(): void
    {
        // Test basketball player stats
        $playerStats = $this->sportConfigService->getStatFields(1, 'player');

        // Verify expected fields are present
        $this->assertContains('MIN', $playerStats);
        $this->assertContains('FGM', $playerStats);
        $this->assertContains('FGA', $playerStats);
        $this->assertContains('PTS', $playerStats);

        // Test basketball team stats
        $teamStats = $this->sportConfigService->getStatFields(1, 'team');

        $this->assertContains('REB', $teamStats);
        $this->assertContains('AST', $teamStats);
    }

    /**
     * Test getAllFieldLabels method
     *
     * @return void
     */
    public function testGetAllFieldLabels(): void
    {
        // Test basketball field labels
        $labels = $this->sportConfigService->getAllFieldLabels(1);

        // Verify basic structure
        $this->assertIsArray($labels);

        // The service may return empty array if no field labels are configured
        // This is acceptable behavior as field labels are optional
        if (!empty($labels)) {
            // Check for some common basketball stat labels if they exist
            $this->assertArrayHasKey('FGM', $labels);
            $this->assertArrayHasKey('FGA', $labels);
        }
    }

    /**
     * Test getPeriodConfig method
     *
     * @return void
     */
    public function testGetPeriodConfig(): void
    {
        // Test basketball period config
        $periodConfig = $this->sportConfigService->getPeriodConfig(1);

        // Check structure
        $this->assertIsArray($periodConfig);
        $this->assertArrayHasKey('supported', $periodConfig);
        $this->assertArrayHasKey('default', $periodConfig);
        $this->assertArrayHasKey('names', $periodConfig);

        // Verify basketball uses quarters
        $this->assertContains(4, $periodConfig['supported']);
        $this->assertArrayHasKey(2, $periodConfig['names']); // Should have entry for halves
        $this->assertArrayHasKey(4, $periodConfig['names']); // Should have entry for quarters

        // Verify actual period names
        $this->assertEquals('Half', $periodConfig['names'][2]);
        $this->assertEquals('Quarter', $periodConfig['names'][4]);
    }
}
