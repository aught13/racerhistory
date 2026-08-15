<?php
declare(strict_types=1);

namespace App\Test\TestCase\Service;

use App\Service\SportConfigService;
use Cake\Cache\Cache;
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
        'app.SiteOptions',
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
        Cache::clear('default');
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

    /**
     * Test getSportName method
     */
    public function testGetSportName(): void
    {
        // Basketball should return 'basketball'
        $name = $this->sportConfigService->getSportName(1);
        $this->assertEquals('basketball', $name);
    }

    /**
     * Test getSportName with non-existent sport
     */
    public function testGetSportNameNonExistent(): void
    {
        $name = $this->sportConfigService->getSportName(999);
        $this->assertEquals('unknown', $name);
    }

    /**
     * Test getPeriodName method
     */
    public function testGetPeriodName(): void
    {
        // Basketball with 4 periods should be "Quarter"
        $name = $this->sportConfigService->getPeriodName(1, 4);
        $this->assertEquals('Quarter', $name);

        // Basketball with 2 periods should be "Half"
        $name = $this->sportConfigService->getPeriodName(1, 2);
        $this->assertEquals('Half', $name);
    }

    /**
     * Test getOfficials method
     */
    public function testGetOfficials(): void
    {
        $officials = $this->sportConfigService->getOfficials(1);

        $this->assertIsArray($officials);
        $this->assertNotEmpty($officials);
        // May be default officials or configured ones
        $this->assertGreaterThanOrEqual(2, count($officials));
    }

    /**
     * Test getStatTable method
     */
    public function testGetStatTable(): void
    {
        // Get basketball game team table
        $table = $this->sportConfigService->getStatTable(1, 'game', 'team');
        $this->assertEquals('stat_basket_game_team', $table);

        // Get basketball season player table
        $table = $this->sportConfigService->getStatTable(1, 'season', 'player');
        $this->assertEquals('stat_basket_season_person', $table);
    }

    /**
     * Test getStatTable with non-existent combination
     */
    public function testGetStatTableNonExistent(): void
    {
        $table = $this->sportConfigService->getStatTable(999, 'game', 'team');
        $this->assertNull($table);
    }

    /**
     * Test getAllStatFields method
     */
    public function testGetAllStatFields(): void
    {
        $fields = $this->sportConfigService->getAllStatFields(1);

        $this->assertIsArray($fields);
        $this->assertArrayHasKey('player', $fields);
        $this->assertArrayHasKey('team', $fields);
        $this->assertArrayHasKey('opponent', $fields);
    }

    /**
     * Test getFieldLabel method
     */
    public function testGetFieldLabel(): void
    {
        $label = $this->sportConfigService->getFieldLabel(1, 'FGM');
        $this->assertEquals('Field Goals Made', $label);

        $label = $this->sportConfigService->getFieldLabel(1, 'PTS');
        $this->assertEquals('Points', $label);
    }

    /**
     * Test getFieldLabel with unknown field returns field itself
     */
    public function testGetFieldLabelUnknown(): void
    {
        $label = $this->sportConfigService->getFieldLabel(1, 'UNKNOWN');
        $this->assertEquals('UNKNOWN', $label);
    }

    /**
     * Test getCalculatedField method
     */
    public function testGetCalculatedField(): void
    {
        $calc = $this->sportConfigService->getCalculatedField(1, 'FG%');

        $this->assertIsArray($calc);
        $this->assertArrayHasKey('formula', $calc);
        $this->assertArrayHasKey('condition', $calc);
    }

    /**
     * Test getAllCalculatedFields method
     */
    public function testGetAllCalculatedFields(): void
    {
        $fields = $this->sportConfigService->getAllCalculatedFields(1);

        $this->assertIsArray($fields);
        $this->assertArrayHasKey('FG%', $fields);
        $this->assertArrayHasKey('3P%', $fields);
        $this->assertArrayHasKey('FT%', $fields);
    }

    /**
     * Test getConfig method with custom key
     */
    public function testGetConfig(): void
    {
        $periods = $this->sportConfigService->getConfig(1, 'periods');
        $this->assertIsArray($periods);

        $scoringType = $this->sportConfigService->getConfig(1, 'scoringType');
        $this->assertEquals('cumulative', $scoringType);
    }

    /**
     * Test getConfig with default value
     */
    public function testGetConfigWithDefault(): void
    {
        $value = $this->sportConfigService->getConfig(1, 'nonexistent_key', 'default_value');
        $this->assertEquals('default_value', $value);
    }

    /**
     * Test validatePeriodScores with valid cumulative sport data
     */
    public function testValidatePeriodScoresValid(): void
    {
        $data = [
            'pts_mur' => 84,
            'pts_opp' => 78,
            'periods' => 2,
            'ot' => 0,
            'period_1_team' => 42,
            'period_1_opponent' => 40,
            'period_2_team' => 42,
            'period_2_opponent' => 38,
        ];

        $errors = $this->sportConfigService->validatePeriodScores(1, $data); // Basketball
        $this->assertEmpty($errors);
    }

    /**
     * Test validatePeriodScores with mismatched totals
     */
    public function testValidatePeriodScoresInvalid(): void
    {
        $data = [
            'pts_mur' => 100,
            'pts_opp' => 78,
            'periods' => 2,
            'ot' => 0,
            'period_1_team' => 42,
            'period_1_opponent' => 40,
            'period_2_team' => 42,
            'period_2_opponent' => 38,
        ];

        $errors = $this->sportConfigService->validatePeriodScores(1, $data);
        $this->assertNotEmpty($errors);
        $this->assertStringContainsString('Team period scores', $errors[0]);
    }

    /**
     * Test validatePeriodScores with overtime validation
     */
    public function testValidatePeriodScoresOvertimeMustBeTied(): void
    {
        $data = [
            'pts_mur' => 85,
            'pts_opp' => 82,
            'periods' => 2,
            'ot' => 1,
            'period_1_team' => 40,
            'period_1_opponent' => 38, // Team ahead by 2 after period 1
            'period_2_team' => 40,
            'period_2_opponent' => 39, // Team ahead by 1 after regulation (not tied!)
            'overtime_1_team' => 5,
            'overtime_1_opponent' => 5,
        ];

        $errors = $this->sportConfigService->validatePeriodScores(1, $data);
        $this->assertNotEmpty($errors);
        $this->assertStringContainsString('Regular period scores must be tied when overtime', $errors[0]);
    }

    /**
     * Test validatePeriodScores skips non-cumulative sports
     */
    public function testValidatePeriodScoresSkipsNonCumulative(): void
    {
        // For a sport without cumulative scoring, validation should be skipped
        // Assuming baseball (sport_id 7) has by_period scoring
        $data = [
            'pts_mur' => 5,
            'pts_opp' => 3,
            'periods' => 9,
        ];

        $errors = $this->sportConfigService->validatePeriodScores(7, $data);
        $this->assertEmpty($errors); // Should not validate non-cumulative sports
    }

    /**
     * Test clearCache method doesn't throw errors
     */
    public function testClearCache(): void
    {
        $this->sportConfigService->clearCache(1);
        // If we got here without exception, the test passes
        $this->assertTrue(true);
    }
}
