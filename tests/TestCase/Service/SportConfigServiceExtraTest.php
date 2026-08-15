<?php
declare(strict_types=1);

namespace App\Test\TestCase\Service;

use App\Service\SportConfigService;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;

class SportConfigServiceExtraTest extends TestCase
{
    protected array $fixtures = [
        'app.Sports',
        'app.SiteOptions',
    ];

    private SportConfigService $service;

    /**
     * Test setup: instantiate SportConfigService.
     *
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->service = new SportConfigService();
    }

    /**
     * Test teardown: unset service.
     *
     * @return void
     */
    public function tearDown(): void
    {
        unset($this->service);
        parent::tearDown();
    }

    /**
     * Verify saveMergedConfig persists overrides and resetToDefaults clears them.
     *
     * @return void
     */
    public function testSaveMergedConfigPersistsOverrideAndReset(): void
    {
        $merged = $this->service->getMergedConfig('basketball');
        $this->assertIsArray($merged);

        // Modify officials to force an override
        $merged['officials'] = ['Override Ref 1', 'Override Ref 2'];

        $ok = $this->service->saveMergedConfig('basketball', $merged);
        $this->assertTrue($ok, 'saveMergedConfig should persist override');

        // Check site_options table for the override payload
        $table = TableRegistry::getTableLocator()->get('SiteOptions');
        $row = $table->find()->where(['option_key' => 'sports.override.basketball'])->first();
        $this->assertNotNull($row, 'override row must exist');

        $decoded = json_decode((string)$row->value, true);
        $this->assertIsArray($decoded);
        $this->assertArrayHasKey('officials', $decoded);

        // getMergedConfig should reflect the persisted override
        $after = $this->service->getMergedConfig('basketball');
        $this->assertSame(['Override Ref 1', 'Override Ref 2'], $after['officials']);

        // Reset to defaults removes override
        $this->assertTrue($this->service->resetToDefaults('basketball'));
        $row = $table->find()->where(['option_key' => 'sports.override.basketball'])->first();
        $this->assertNull($row, 'override row should be deleted after reset');
    }
}
