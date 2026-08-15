<?php
declare(strict_types=1);

namespace App\Test\TestCase\Service;

use App\Service\SportConfigAdminService;
use Cake\TestSuite\TestCase;

class SportConfigAdminServiceExtraTest extends TestCase
{
    protected array $fixtures = [
        'app.Sports',
        'app.SiteOptions',
    ];

    private SportConfigAdminService $service;

    /**
     * Set up SportConfigAdminService instance for tests.
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new SportConfigAdminService();
    }

    /**
     * Tear down test instance.
     *
     * @return void
     */
    protected function tearDown(): void
    {
        unset($this->service);
        parent::tearDown();
    }

    /**
     * Ensure default config template contains expected keys.
     *
     * @return void
     */
    public function testGetDefaultConfigTemplateContainsExpectedKeys(): void
    {
        $template = $this->service->getDefaultConfigTemplate('basketball');

        $this->assertArrayHasKey('officials', $template);
        $this->assertArrayHasKey('default_periods', $template);
        $this->assertArrayHasKey('period_name_2', $template);
    }

    /**
     * Verify setConfig handles period names and supports_periods setting.
     *
     * @return void
     */
    public function testSetConfigSupportsPeriodsAndPeriodName(): void
    {
        // set a period name
        $this->assertTrue($this->service->setConfig('basketball', 'period_name_3', 'Third'));

        $configs = $this->service->getFormattedConfigsForSport('basketball');
        $this->assertArrayHasKey('3', $configs['period_names']);
        $this->assertSame('Third', $configs['period_names']['3']['value']);

        // set supports_periods to 'any' string and verify persisted override
        $this->assertTrue($this->service->setConfig('basketball', 'supports_periods', 'any'));

        // Read formatted configs and assert supports_periods is represented
        $formatted = $this->service->getFormattedConfigsForSport('basketball');
        $this->assertArrayHasKey('settings', $formatted);
        $this->assertArrayHasKey('supports_periods', $formatted['settings']);
    }
}
