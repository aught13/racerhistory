<?php
declare(strict_types=1);

namespace App\Test\TestCase\Service;

use App\Service\SiteOptionService;
use Cake\TestSuite\TestCase;

class SiteOptionServiceTest extends TestCase
{
    /**
     * @var array<string>
     */
    protected array $fixtures = [
        'app.SiteOptions',
    ];

    private SiteOptionService $service;

    public function setUp(): void
    {
        parent::setUp();
        $this->service = new SiteOptionService();
    }

    public function tearDown(): void
    {
        unset($this->service);
        parent::tearDown();
    }

    public function testGetOptionValueReturnsNullForMissingKey(): void
    {
        $this->assertNull($this->service->getOptionValue('missing_key'));
    }

    public function testGetOptionValueReturnsExistingValue(): void
    {
        $this->assertSame('true', $this->service->getOptionValue('registration'));
    }

    public function testSetOptionValueUpdatesExistingKey(): void
    {
        $this->assertTrue($this->service->setOptionValue('registration', 'false'));
        $this->assertSame('false', $this->service->getOptionValue('registration'));
    }

    public function testSetOptionValueCreatesNewKey(): void
    {
        $this->assertTrue($this->service->setOptionValue('new_option', 'abc'));
        $this->assertSame('abc', $this->service->getOptionValue('new_option'));
    }

    public function testGetBooleanOptionUsesDefaultWhenMissing(): void
    {
        $this->assertTrue($this->service->getBooleanOption('missing_bool', true));
        $this->assertFalse($this->service->getBooleanOption('missing_bool', false));
    }

    public function testGetBooleanOptionParsesTrueFalse(): void
    {
        $this->assertTrue($this->service->getBooleanOption('registration', false));

        $this->service->setOptionValue('registration', 'false');
        $this->assertFalse($this->service->getBooleanOption('registration', true));
    }

    public function testToggleBooleanOptionFlipsValue(): void
    {
        $this->assertTrue($this->service->getBooleanOption('registration', true));

        $newValue = $this->service->toggleBooleanOption('registration', true);
        $this->assertFalse($newValue);
        $this->assertSame('false', $this->service->getOptionValue('registration'));

        $newValue = $this->service->toggleBooleanOption('registration', true);
        $this->assertTrue($newValue);
        $this->assertSame('true', $this->service->getOptionValue('registration'));
    }
}
