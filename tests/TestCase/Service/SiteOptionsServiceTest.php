<?php
declare(strict_types=1);

namespace App\Test\TestCase\Service;

use App\Model\Entity\SiteOption;
use App\Model\Table\SiteOptionsTable;
use App\Service\SiteOptionsService;
use Cake\Cache\Cache;
use Cake\Core\Configure;
use Cake\Database\Connection;
use Cake\Datasource\ResultSetDecorator;
use Cake\ORM\Query\SelectQuery;
use Cake\TestSuite\TestCase;

class SiteOptionsServiceTest extends TestCase
{
    /**
     * Clears mutable cache/config state between tests.
     */
    public function tearDown(): void
    {
        Cache::delete('global_site_options');
        Configure::delete('SiteOptions');
        parent::tearDown();
    }

    /**
     * Missing keys in the payload should still be persisted via configured defaults.
     */
    public function testSaveSettingsPersistsAllRegisteredKeysAndRefreshesRuntimeCache(): void
    {
        $definitions = [
            'registration' => [
                'label' => 'Enable User Registration',
                'type' => 'checkbox',
                'default' => true,
            ],
            'site_maintenance' => [
                'label' => 'Site Maintenance Mode',
                'type' => 'checkbox',
                'default' => false,
            ],
            'records_per_page' => [
                'label' => 'Default Records Per Page',
                'type' => 'number',
                'default' => 20,
            ],
            'support_email' => [
                'label' => 'System Support Email Address',
                'type' => 'email',
                'default' => 'admin@example.com',
            ],
        ];

        $existingRegistration = new SiteOption([
            'id' => 1,
            'option_key' => 'registration',
            'value' => 'true',
        ]);

        $query = $this->getMockBuilder(SelectQuery::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['where', 'all'])
            ->getMock();
        $query->expects($this->once())
            ->method('where')
            ->with(['option_key IN' => array_keys($definitions)])
            ->willReturnSelf();
        $query->expects($this->once())
            ->method('all')
            ->willReturn(new ResultSetDecorator([$existingRegistration]));

        $connection = $this->getMockBuilder(Connection::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['transactional'])
            ->getMock();
        $connection->expects($this->once())
            ->method('transactional')
            ->willReturnCallback(static function (callable $callback): bool {
                return (bool)$callback();
            });

        $savedValues = [];

        $table = $this->getMockBuilder(SiteOptionsTable::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['find', 'patchEntity', 'newEntity', 'save', 'getConnection'])
            ->getMock();
        $table->method('find')->willReturn($query);
        $table->method('getConnection')->willReturn($connection);
        $table->method('patchEntity')->willReturnCallback(
            static function (SiteOption $entity, array $data): SiteOption {
                $entity->set('value', (string)($data['value'] ?? ''));

                return $entity;
            },
        );
        $table->method('newEntity')->willReturnCallback(
            static function (array $data): SiteOption {
                return new SiteOption($data);
            },
        );
        $table->method('save')->willReturnCallback(
            static function (SiteOption $entity) use (&$savedValues): SiteOption {
                $savedValues[(string)$entity->option_key] = (string)$entity->value;

                return $entity;
            },
        );

        Cache::write('global_site_options', ['stale' => true]);

        $service = new SiteOptionsService($table, $definitions);
        $result = $service->saveSettings([
            'registration' => '0',
            'support_email' => 'helpdesk@example.com',
        ]);

        $this->assertTrue($result);
        $this->assertSame([
            'registration' => 'false',
            'site_maintenance' => 'false',
            'records_per_page' => '20',
            'support_email' => 'helpdesk@example.com',
        ], $savedValues);

        $cached = Cache::read('global_site_options');
        $this->assertIsArray($cached);
        $this->assertSame(false, $cached['registration']);
        $this->assertSame(false, $cached['site_maintenance']);
        $this->assertSame(20, $cached['records_per_page']);
        $this->assertSame('helpdesk@example.com', $cached['support_email']);

        $this->assertSame(false, Configure::read('SiteOptions.registration'));
        $this->assertSame(20, Configure::read('SiteOptions.records_per_page'));
    }

    /**
     * Cache should remain untouched when transactional save fails.
     */
    public function testSaveSettingsReturnsFalseWhenPersistFails(): void
    {
        $definitions = [
            'registration' => [
                'label' => 'Enable User Registration',
                'type' => 'checkbox',
                'default' => true,
            ],
            'support_email' => [
                'label' => 'System Support Email Address',
                'type' => 'email',
                'default' => 'admin@example.com',
            ],
        ];

        $query = $this->getMockBuilder(SelectQuery::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['where', 'all'])
            ->getMock();
        $query->method('where')->willReturnSelf();
        $query->method('all')->willReturn(new ResultSetDecorator([]));

        $connection = $this->getMockBuilder(Connection::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['transactional'])
            ->getMock();
        $connection->method('transactional')
            ->willReturnCallback(static function (callable $callback): bool {
                return (bool)$callback();
            });

        $table = $this->getMockBuilder(SiteOptionsTable::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['find', 'newEntity', 'save', 'getConnection'])
            ->getMock();
        $table->method('find')->willReturn($query);
        $table->method('getConnection')->willReturn($connection);
        $table->method('newEntity')->willReturnCallback(
            static function (array $data): SiteOption {
                return new SiteOption($data);
            },
        );
        $table->method('save')->willReturnCallback(
            static function (SiteOption $entity): SiteOption|false {
                if ($entity->option_key === 'support_email') {
                    return false;
                }

                return $entity;
            },
        );

        Cache::write('global_site_options', ['registration' => true, 'support_email' => 'old@example.com']);

        $service = new SiteOptionsService($table, $definitions);
        $result = $service->saveSettings([
            'registration' => '0',
            'support_email' => 'broken@example.com',
        ]);

        $this->assertFalse($result);
        $this->assertSame(
            ['registration' => true, 'support_email' => 'old@example.com'],
            Cache::read('global_site_options'),
        );
    }
}
