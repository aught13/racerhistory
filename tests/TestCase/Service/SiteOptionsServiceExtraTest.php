<?php
declare(strict_types=1);

namespace App\Test\TestCase\Service;

use App\Model\Entity\SiteOption;
use App\Model\Table\SiteOptionsTable;
use App\Service\SiteOptionsService;
use Cake\Cache\Cache;
use Cake\Core\Configure;
use Cake\ORM\Query\SelectQuery;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;

class SiteOptionsServiceExtraTest extends TestCase
{
    /**
     * @var array<string>
     */
    protected array $fixtures = [
        'app.SiteOptions',
    ];

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

    /**
     * Toggle updates should not reset unrelated ad_* settings.
     */
    public function testToggleBooleanSettingPreservesAdSettings(): void
    {
        $table = TableRegistry::getTableLocator()->get('SiteOptions');

        $this->upsertOption($table, 'registration', 'true');
        $this->upsertOption($table, 'ad_script', '<script>adsbygoogle</script>');
        $this->upsertOption($table, 'ad_publisher_id', 'ca-pub-1234567890');
        $this->upsertOption($table, 'ad_below_nav_html', '<ins class="adsbygoogle"></ins>');

        $definitions = [
            'registration' => ['label' => 'Registration', 'type' => 'checkbox', 'default' => true],
            'ad_script' => ['label' => 'Ad Script', 'type' => 'textarea', 'default' => ''],
            'ad_publisher_id' => ['label' => 'Publisher', 'type' => 'text', 'default' => ''],
            'ad_below_nav_html' => ['label' => 'Nav Ad', 'type' => 'textarea', 'default' => ''],
        ];

        $service = new SiteOptionsService($table, $definitions);
        $this->assertFalse($service->toggleBooleanSetting('registration', true));

        $this->assertSame('false', (string)$table->find()->where(['option_key' => 'registration'])->firstOrFail()->value);
        $this->assertSame(
            '<script>adsbygoogle</script>',
            (string)$table->find()->where(['option_key' => 'ad_script'])->firstOrFail()->value,
        );
        $this->assertSame(
            'ca-pub-1234567890',
            (string)$table->find()->where(['option_key' => 'ad_publisher_id'])->firstOrFail()->value,
        );
        $this->assertSame(
            '<ins class="adsbygoogle"></ins>',
            (string)$table->find()->where(['option_key' => 'ad_below_nav_html'])->firstOrFail()->value,
        );
    }

    /**
     * Role-privileges writes should preserve existing ad_* settings.
     */
    public function testUpdateRolePrivilegesPreservesAdSettings(): void
    {
        $table = TableRegistry::getTableLocator()->get('SiteOptions');

        $this->upsertOption($table, 'registration', 'true');
        $this->upsertOption($table, 'ad_script', '<script>adsbygoogle</script>');
        $this->upsertOption($table, 'ad_publisher_id', 'ca-pub-999');
        $this->upsertOption($table, 'role_privileges', '{"admin":["bypass_all"]}');

        $definitions = [
            'registration' => ['label' => 'Registration', 'type' => 'checkbox', 'default' => true],
            'ad_script' => ['label' => 'Ad Script', 'type' => 'textarea', 'default' => ''],
            'ad_publisher_id' => ['label' => 'Publisher', 'type' => 'text', 'default' => ''],
            'role_privileges' => ['label' => 'Role Privileges', 'type' => 'textarea', 'default' => ''],
        ];

        $service = new SiteOptionsService($table, $definitions);
        $saved = $service->updateRolePrivileges([
            'admin' => ['bypass_all', 'manage_users'],
            'editor' => ['view_any'],
        ]);

        $this->assertTrue($saved);
        $this->assertSame(
            '<script>adsbygoogle</script>',
            (string)$table->find()->where(['option_key' => 'ad_script'])->firstOrFail()->value,
        );
        $this->assertSame(
            'ca-pub-999',
            (string)$table->find()->where(['option_key' => 'ad_publisher_id'])->firstOrFail()->value,
        );

        $updatedPrivileges = (string)$table->find()->where(['option_key' => 'role_privileges'])->firstOrFail()->value;
        $this->assertStringContainsString('manage_users', $updatedPrivileges);
        $this->assertStringContainsString('editor', $updatedPrivileges);
    }

    /**
     * Upsert helper for site option records in integration-style tests.
     *
     * @param \App\Model\Table\SiteOptionsTable $table
     * @param string $key
     * @param string $value
     */
    private function upsertOption(SiteOptionsTable $table, string $key, string $value): void
    {
        $row = $table->find()->where(['option_key' => $key])->first();
        if ($row instanceof SiteOption) {
            $row->value = $value;
            $table->saveOrFail($row);

            return;
        }

        $table->saveOrFail($table->newEntity([
            'option_key' => $key,
            'value' => $value,
        ]));
    }
}
