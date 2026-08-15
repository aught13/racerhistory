<?php
declare(strict_types=1);

namespace App\Test\TestCase\Service;

use App\Model\Entity\SiteOption;
use App\Model\Table\SiteOptionsTable;
use App\Service\SiteOptionsService;
use Cake\Cache\Cache;
use Cake\Core\Configure;
use Cake\ORM\Query\SelectQuery;
use Cake\TestSuite\TestCase;

class SiteOptionsServiceExtraTest extends TestCase
{
    /**
     * Clean up cache and configuration after each test.
     *
     * @return void
     */
    public function tearDown(): void
    {
        Cache::delete('global_site_options');
        Configure::delete('SiteOptions');
        parent::tearDown();
    }

    /**
     * Toggle boolean on unknown key returns null.
     *
     * @return void
     */
    public function testToggleBooleanSettingUnknownKeyReturnsNull(): void
    {
        $service = new SiteOptionsService($this->getMockBuilder(SiteOptionsTable::class)->disableOriginalConstructor()->getMock());
        $this->assertNull($service->toggleBooleanSetting('nonexistent_key', false));
    }

    /**
     * Ensure persisted setting retrieval respects definitions and defaults.
     *
     * @return void
     */
    public function testGetPersistedSettingRespectsDefinitions(): void
    {
        $definitions = [
            'registration' => ['label' => 'Reg', 'type' => 'checkbox', 'default' => true],
        ];

        $table = $this->getMockBuilder(SiteOptionsTable::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['find'])
            ->getMock();

        $row = new SiteOption(['option_key' => 'registration', 'value' => 'false']);

        $query = $this->getMockBuilder(SelectQuery::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['where', 'first'])
            ->getMock();
        $query->method('where')->willReturnSelf();
        $query->method('first')->willReturn($row);

        $table->method('find')->willReturn($query);

        $service = new SiteOptionsService($table, $definitions);
        $this->assertFalse($service->getPersistedSetting('registration', true));
    }
}
