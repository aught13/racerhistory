<?php
declare(strict_types=1);

namespace App\Test\TestCase\Model\Table;

use App\Model\Table\SportConfigsTable;
use Cake\TestSuite\TestCase;

/**
 * App\Model\Table\SportConfigsTable Test Case
 */
class SportConfigsTableTest extends TestCase
{
    /**
     * Test subject
     */
    protected SportConfigsTable $SportConfigs;

    /**
     * Fixtures
     */
    protected array $fixtures = [
        'app.Sports',
        'app.SportConfigs',
    ];

    /**
     * setUp method
     */
    public function setUp(): void
    {
        parent::setUp();
        $config = $this->getTableLocator()->exists('SportConfigs') ? [] : ['className' => SportConfigsTable::class];
        $this->SportConfigs = $this->getTableLocator()->get('SportConfigs', $config);
    }

    /**
     * tearDown method
     */
    public function tearDown(): void
    {
        unset($this->SportConfigs);

        parent::tearDown();
    }

    /**
     * Test getConfigsForSport method
     */
    public function testGetConfigsForSport(): void
    {
        // Set some test configs
        $this->SportConfigs->setConfig(1, 'period_name_2', 'Half', 'Basketball halves');
        $this->SportConfigs->setConfig(1, 'period_name_4', 'Quarter', 'Basketball quarters');
        $this->SportConfigs->setConfig(1, 'officials', ['Referee 1', 'Referee 2'], 'Basketball officials');

        $configs = $this->SportConfigs->getConfigsForSport(1);

        $this->assertEquals('Half', $configs['period_name_2']);
        $this->assertEquals('Quarter', $configs['period_name_4']);
        $this->assertEquals(['Referee 1', 'Referee 2'], $configs['officials']);
    }

    /**
     * Test setConfig method
     */
    public function testSetConfig(): void
    {
        $result = $this->SportConfigs->setConfig(1, 'test_key', 'test_value', 'Test description');

        $this->assertNotFalse($result);
        $this->assertEquals('test_value', $result->config_value);
        $this->assertEquals('Test description', $result->description);
    }

    /**
     * Test getFormattedConfigsForSport method
     */
    public function testGetFormattedConfigsForSport(): void
    {
        // Set test configs
        $this->SportConfigs->setConfig(1, 'period_name_2', 'Half');
        $this->SportConfigs->setConfig(1, 'officials', ['Ref 1', 'Ref 2']);
        $this->SportConfigs->setConfig(1, 'default_periods', '2');

        $formatted = $this->SportConfigs->getFormattedConfigsForSport(1);

        $this->assertArrayHasKey('period_names', $formatted);
        $this->assertArrayHasKey('officials', $formatted);
        $this->assertArrayHasKey('settings', $formatted);

        $this->assertEquals('Half', $formatted['period_names']['2']['value']);
        $this->assertEquals(['Ref 1', 'Ref 2'], $formatted['officials']['value']);
        $this->assertEquals('2', $formatted['settings']['default_periods']['value']);
    }

    /**
     * Test saveBulkConfigs method
     */
    public function testSaveBulkConfigs(): void
    {
        $configs = [
            'period_name_2' => ['value' => 'Half', 'description' => 'Basketball halves'],
            'period_name_4' => ['value' => 'Quarter', 'description' => 'Basketball quarters'],
            'officials' => ['value' => ['Referee 1', 'Referee 2']],
        ];

        $result = $this->SportConfigs->saveBulkConfigs(1, $configs);

        $this->assertTrue($result);

        $savedConfigs = $this->SportConfigs->getConfigsForSport(1);
        $this->assertEquals('Half', $savedConfigs['period_name_2']);
        $this->assertEquals('Quarter', $savedConfigs['period_name_4']);
        $this->assertEquals(['Referee 1', 'Referee 2'], $savedConfigs['officials']);
    }

    /**
     * Test getDefaultConfigTemplate method
     */
    public function testGetDefaultConfigTemplate(): void
    {
        $template = $this->SportConfigs->getDefaultConfigTemplate();

        $this->assertArrayHasKey('period_name_2', $template);
        $this->assertArrayHasKey('period_name_4', $template);
        $this->assertArrayHasKey('officials', $template);
        $this->assertArrayHasKey('default_periods', $template);

        $this->assertEquals('Half', $template['period_name_2']['value']);
        $this->assertEquals('Quarter', $template['period_name_4']['value']);
    }
}
