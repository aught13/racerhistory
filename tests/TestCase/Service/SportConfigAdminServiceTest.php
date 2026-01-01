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
        'app.SportConfigs',
    ];

    private SportConfigAdminService $service;

    public function setUp(): void
    {
        parent::setUp();
        $this->service = new SportConfigAdminService();
    }

    public function tearDown(): void
    {
        unset($this->service);
        parent::tearDown();
    }

    public function testGetFormattedConfigsForSportReturnsExpectedStructure(): void
    {
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
        $this->assertContains('Official 1', $configs['officials']['value']);

        // default template includes these keys under settings
        $this->assertSame(2, $configs['settings']['default_periods']['value']);
        $this->assertSame('cumulative', $configs['settings']['scoring_type']['value']);
    }

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

        // Note: SportConfigsTable stores strings literally here; controller/template decides how to display.
        $this->assertSame('Home Plate, First Base', $configs['officials']['value']);

        /** @var \App\Model\Table\SportConfigsTable $table */
        $table = TableRegistry::getTableLocator()->get('SportConfigs');
        $this->assertNotNull($table->find()->where(['sport_id' => 1, 'config_key' => 'period_name_7'])->first());
    }
}
