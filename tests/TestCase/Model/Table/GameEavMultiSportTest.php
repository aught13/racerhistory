<?php
declare(strict_types=1);

namespace App\Test\TestCase\Model\Table;

use App\Model\Table\GameEavTable;
use Cake\TestSuite\TestCase;

/**
 * App\Model\Table\GameEavTable Test Case for Multi-Sport Features
 */
class GameEavMultiSportTest extends TestCase
{
    /**
     * Fixtures
     *
     * @var array<string>
     */
    protected array $fixtures = [
        'app.GameEav',
        'app.Games',
        'app.Sports',
        'app.Teams',
        'app.TeamSeasons',
    ];

    /**
     * Test GameEav table instance
     */
    protected GameEavTable $GameEav;

    /**
     * setUp method
     *
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();
        $config = $this->getTableLocator()->exists('GameEav') ? [] : ['className' => GameEavTable::class];
        $this->GameEav = $this->getTableLocator()->get('GameEav', $config);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown(): void
    {
        unset($this->GameEav);
        parent::tearDown();
    }

    /**
     * Test sport configuration loading
     *
     * @return void
     */
    public function testSportConfigurationLoading(): void
    {
        // Test basketball (sport_id = 1) - should have both 2-period and 4-period configs
        $template2 = $this->GameEav->getEavTemplateForSport(1, '2', '0');
        $template4 = $this->GameEav->getEavTemplateForSport(1, '4', '0');

        // Test 2-period basketball (halves)
        $this->assertArrayHasKey('period_1_team', $template2);
        $this->assertArrayHasKey('period_2_team', $template2);
        $this->assertArrayNotHasKey('period_3_team', $template2);
        $label2 = $template2['period_1_team']['display_label'] ?? $template2['period_1_team']['label'] ?? '';
        $this->assertStringContainsString('Half', $label2);

        // Test 4-period basketball (quarters)
        $this->assertArrayHasKey('period_1_team', $template4);
        $this->assertArrayHasKey('period_4_team', $template4);
        $label4 = $template4['period_1_team']['display_label'] ?? $template4['period_1_team']['label'] ?? '';
        $this->assertStringContainsString('Quarter', $label4);

        // Test officials
        $this->assertArrayHasKey('official_1', $template2);
        $this->assertArrayHasKey('official_2', $template2);
    }

    /**
     * Test overtime period generation
     *
     * @return void
     */
    public function testOvertimePeriodGeneration(): void
    {
        // Test basketball with 1 overtime
        $template = $this->GameEav->getEavTemplateForSport(1, '2', '1');

        $this->assertArrayHasKey('overtime_1_team', $template);
        $this->assertArrayHasKey('overtime_1_opponent', $template);
            $labelOt = $template['overtime_1_team']['display_label'] ?? $template['overtime_1_team']['label'] ?? '';
            $this->assertStringContainsString('OT', $labelOt);

        // Test with multiple overtimes
        $templateMultiOT = $this->GameEav->getEavTemplateForSport(1, '2', '3');
        $this->assertArrayHasKey('overtime_3_team', $templateMultiOT);
    }

    /**
     * Test different sports configurations
     *
     * @return void
     */
    public function testDifferentSportsConfigurations(): void
    {
        // Test football (assuming sport_id = 11 based on migration data)
        $footballTemplate = $this->GameEav->getEavTemplateForSport(11, '4', '0');

        $this->assertArrayHasKey('period_1_team', $footballTemplate);
        $this->assertArrayHasKey('period_4_team', $footballTemplate);
        // Should use Quarter for 4-period sports
            $labelFootball = $footballTemplate['period_1_team']['display_label'] ?? $footballTemplate['period_1_team']['label'] ?? '';
            $this->assertStringContainsString('Quarter', $labelFootball);
    }

    /**
     * Test bulk attribute saving
     *
     * @return void
     */
    public function testBulkAttributeSaving(): void
    {
        $gameId = 1;
        $eavData = [
            'period_1_team' => '25',
            'period_1_opponent' => '20',
            'period_2_team' => '30',
            'period_2_opponent' => '25',
            'official_1' => 'John Doe',
            'official_2' => 'Jane Smith',
        ];

        $result = $this->GameEav->saveBulkAttributes($gameId, $eavData);
        $this->assertTrue($result);

        // Verify saved attributes
        $savedAttributes = $this->GameEav->getAttributesForGame($gameId);
        $this->assertEquals('25', $savedAttributes['period_1_team']);
        $this->assertEquals('John Doe', $savedAttributes['official_1']);
    }

    /**
     * Test formatted scoring display
     *
     * @return void
     */
    public function testFormattedScoringDisplay(): void
    {
        $gameId = 2;

        // Save some test scoring data
        $eavData = [
            'period_1_team' => '15',
            'period_1_opponent' => '12',
            'period_2_team' => '18',
            'period_2_opponent' => '16',
            'overtime_1_team' => '5',
            'overtime_1_opponent' => '3',
        ];

        $this->GameEav->saveBulkAttributes($gameId, $eavData);

        // Test formatted scoring
        $scoring = $this->GameEav->getFormattedScoring($gameId, 2, 1);

        $this->assertEquals(15, $scoring['periods'][1]['team']);
        $this->assertEquals(12, $scoring['periods'][1]['opponent']);
        $this->assertEquals(5, $scoring['overtime'][1]['team']);
        $this->assertEquals(3, $scoring['overtime'][1]['opponent']);
        $this->assertEquals(38, $scoring['totals']['team']); // 15+18+5
        $this->assertEquals(31, $scoring['totals']['opponent']); // 12+16+3
    }

    /**
     * Test varchar period handling (backward compatibility)
     *
     * @return void
     */
    public function testVarcharPeriodHandling(): void
    {
        // Test that varchar periods are properly converted to integers
        $template = $this->GameEav->getEavTemplateForSport(1, '4', '2');

        // Should have 4 regular periods + 2 overtime periods
        $this->assertArrayHasKey('period_4_team', $template);
        $this->assertArrayHasKey('overtime_2_team', $template);

        // Test empty/null values default to reasonable defaults
        $templateDefault = $this->GameEav->getEavTemplateForSport(1, '', '');
        $this->assertArrayHasKey('period_1_team', $templateDefault);
        $this->assertArrayHasKey('period_2_team', $templateDefault);
    }
}
