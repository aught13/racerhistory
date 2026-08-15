<?php
declare(strict_types=1);

namespace App\Test\TestCase\Service;

use App\Service\SportConfigAdminService;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;

class SportConfigAdminServiceTest extends TestCase
{
    /**
     * @var array<string>
     */
    protected array $fixtures = [
        'app.Sports',
        'app.SiteOptions',
    ];

    private SportConfigAdminService $service;

    /**
     * Sets up the test case.
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->service = new SportConfigAdminService();
    }

    /**
     * Tears down the test case.
     */
    public function tearDown(): void
    {
        unset($this->service);
        parent::tearDown();
    }

    /**
     * Tests get formatted configs for sport returns expected structure.
     */
    public function testGetFormattedConfigsForSportReturnsExpectedStructure(): void
    {
        // Ensure a known default state to avoid order-dependent mutations from other tests
        $this->service->resetToDefaults(1);
        $configs = $this->service->getFormattedConfigsForSport(1);

        $this->assertIsArray($configs);
        $this->assertArrayHasKey('period_names', $configs);
        $this->assertArrayHasKey('officials', $configs);
        $this->assertArrayHasKey('settings', $configs);

        $this->assertArrayHasKey('2', $configs['period_names']);
        $this->assertSame('Half', $configs['period_names']['2']['value']);
        $this->assertSame('Quarter', $configs['period_names']['4']['value']);

        $this->assertIsArray($configs['officials']['value']);
        $this->assertContains('Referee 1', $configs['officials']['value']);
    }

    /**
     * Tests normalize formatted configs seeds defaults when empty.
     */
    public function testNormalizeFormattedConfigsSeedsDefaultsWhenEmpty(): void
    {
        $normalized = $this->service->normalizeFormattedConfigs([
            'period_names' => [],
            'officials' => [],
            'settings' => [],
        ]);

        $this->assertArrayHasKey('period_names', $normalized);
        $this->assertArrayHasKey('settings', $normalized);
        $this->assertArrayHasKey('officials', $normalized);

        $this->assertArrayHasKey('2', $normalized['period_names']);
        $this->assertSame('Half', $normalized['period_names']['2']['value']);
        $this->assertSame('Quarter', $normalized['period_names']['4']['value']);

        $this->assertIsArray($normalized['officials']['value']);
        $this->assertNotEmpty($normalized['officials']['value']);
    }

    /**
     * Tests set config and delete config.
     */
    public function testSetConfigAndDeleteConfig(): void
    {
        $this->assertTrue($this->service->setConfig(1, 'test_key', 'abc', 'test desc'));

        $configs = $this->service->getFormattedConfigsForSport(1);
        $this->assertSame('abc', $configs['settings']['test_key']['value']);
        $this->assertSame('test desc', $configs['settings']['test_key']['description']);

        $this->assertTrue($this->service->deleteConfig(1, 'test_key'));

        $configs = $this->service->getFormattedConfigsForSport(1);
        $this->assertArrayNotHasKey('test_key', $configs['settings']);
    }

    /**
     * Tests reset to defaults replaces existing configs.
     */
    public function testResetToDefaultsReplacesExistingConfigs(): void
    {
        $this->assertTrue($this->service->setConfig(1, 'officials', ['Only Official'], 'custom'));

        $configs = $this->service->getFormattedConfigsForSport(1);
        $this->assertSame(['Only Official'], $configs['officials']['value']);

        $this->assertTrue($this->service->resetToDefaults(1));

        $configs = $this->service->getFormattedConfigsForSport(1);
        $this->assertSame('Half', $configs['period_names']['2']['value']);
        $this->assertSame('Quarter', $configs['period_names']['4']['value']);

        $this->assertIsArray($configs['officials']['value']);
        $this->assertContains('Referee 1', $configs['officials']['value']);

        // default template includes these keys under settings
        $this->assertSame(4, $configs['settings']['default_periods']['value']);
        $this->assertSame('cumulative', $configs['settings']['scoring_type']['value']);
    }

    /**
     * Tests save bulk configs persists java script period name keys.
     */
    public function testSaveBulkConfigsPersistsJavaScriptPeriodNameKeys(): void
    {
        $ok = $this->service->saveBulkConfigs(1, [
            'period_name_new_0' => [
                'periods' => '7',
                'value' => 'Inning',
                'description' => 'Innings',
            ],
            'officials' => [
                'value' => 'Home Plate, First Base',
                'description' => 'baseball-style',
            ],
        ]);

        $this->assertTrue($ok);

        $configs = $this->service->getFormattedConfigsForSport(1);
        $this->assertSame('Inning', $configs['period_names']['7']['value']);

        $this->assertSame(['Home Plate', 'First Base'], $configs['officials']['value']);
        $this->assertSame('baseball-style', $configs['officials']['description']);

        /** @var \App\Model\Table\SiteOptionsTable $table */
        $table = TableRegistry::getTableLocator()->get('SiteOptions');
        $row = $table->find()->where(['option_key' => 'sports.override.basketball'])->first();

        $this->assertNotNull($row);
        $decoded = json_decode((string)$row->value, true);
        $this->assertIsArray($decoded);
        $this->assertSame('Inning', $decoded['period_names']['7'] ?? null);
    }
}
