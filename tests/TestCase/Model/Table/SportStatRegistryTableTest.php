<?php
declare(strict_types=1);

namespace App\Test\TestCase\Model\Table;

use App\Model\Table\SportStatRegistryTable;
use Cake\TestSuite\TestCase;

/**
 * App\Model\Table\SportStatRegistryTable Test Case
 */
class SportStatRegistryTableTest extends TestCase
{
    /**
     * Test subject
     *
     * @var \App\Model\Table\SportStatRegistryTable
     */
    protected SportStatRegistryTable $SportStatRegistry;

    /**
     * Fixtures
     *
     * @var array<string>
     */
    protected array $fixtures = [
        'app.Sports',
        'app.SportStatRegistry',
    ];

    /**
     * setUp method
     *
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->SportStatRegistry = $this->getTableLocator()->get('SportStatRegistry');
    }

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown(): void
    {
        unset($this->SportStatRegistry);
        parent::tearDown();
    }

    /**
     * Test validationDefault method
     *
     * @return void
     */
    public function testValidationDefault(): void
    {
        // Test valid data
        $validData = [
            'sport_id' => 1,
            'context' => 'game',
            'entity_type' => 'player',
            'table_name' => 'stat_test_game_player',
            'display_name' => 'Test Game Player Stats',
            'field_mapping' => json_encode(['PTS' => ['label' => 'Points', 'type' => 'numeric']]),
        ];
        $entity = $this->SportStatRegistry->newEntity($validData);
        $this->assertEmpty($entity->getErrors());

        // Test invalid context
        $invalidContext = $validData;
        $invalidContext['context'] = 'invalid_context';
        $entity = $this->SportStatRegistry->newEntity($invalidContext);
        $this->assertNotEmpty($entity->getErrors());
        $this->assertArrayHasKey('context', $entity->getErrors());

        // Test invalid entity_type
        $invalidEntityType = $validData;
        $invalidEntityType['entity_type'] = 'invalid_type';
        $entity = $this->SportStatRegistry->newEntity($invalidEntityType);
        $this->assertNotEmpty($entity->getErrors());
        $this->assertArrayHasKey('entity_type', $entity->getErrors());

        // Test table_name format validation
        $invalidTableName = $validData;
        $invalidTableName['table_name'] = 'Invalid Table-Name';
        $entity = $this->SportStatRegistry->newEntity($invalidTableName);
        $this->assertNotEmpty($entity->getErrors());
        $this->assertArrayHasKey('table_name', $entity->getErrors());

        // Test invalid JSON in field_mapping
        $invalidJson = $validData;
        $invalidJson['field_mapping'] = '{invalid:json}';
        $entity = $this->SportStatRegistry->newEntity($invalidJson);
        $this->assertNotEmpty($entity->getErrors());
        $this->assertArrayHasKey('field_mapping', $entity->getErrors());
    }

    /**
     * Test buildRules method
     *
     * @return void
     */
    public function testBuildRules(): void
    {
        $data = [
            'sport_id' => 1,
            'context' => 'game',
            'entity_type' => 'box', // Use 'box' instead of 'player' to avoid fixture conflict
            'table_name' => 'stat_test_game_box_unique',
            'display_name' => 'Test Unique Game Box Stats',
        ];

        // First save should work
        $entity = $this->SportStatRegistry->newEntity($data);
        $result = $this->SportStatRegistry->save($entity);
        $this->assertNotFalse($result);

        // Second save with same sport/context/entity_type should fail
        $entity = $this->SportStatRegistry->newEntity($data);
        $result = $this->SportStatRegistry->save($entity);
        $this->assertFalse($result);
    }

    /**
     * Test finder methods
     *
     * @return void
     */
    public function testFinders(): void
    {
        // Create test data that doesn't conflict with fixtures
        // Fixtures already have: sport_id=1 game/team, sport_id=1 game/player, sport_id=2 game/team
        $testData = [
            [
                'sport_id' => 1,
                'context' => 'season',
                'entity_type' => 'team',
                'table_name' => 'stat_test_season_team',
                'display_name' => 'Test Season Team Stats',
            ],
        ];

        $entities = $this->SportStatRegistry->newEntities($testData);
        $this->SportStatRegistry->saveMany($entities);

        // Test findBySport - fixtures have 2 for sport_id=1, we add 1 more = 3 total
        $sportResults = $this->SportStatRegistry->find('bySport', ['sport_id' => 1])->toArray();
        $this->assertCount(3, $sportResults);

        // Test findByContext - fixtures have 3 'game' records (2 for sport_id=1, 1 for sport_id=2)
        $contextResults = $this->SportStatRegistry->find('byContext', ['context' => 'game'])->toArray();
        $this->assertCount(3, $contextResults);

        // Test findByEntityType - fixture has only 1 'player' record (basketball)
        $entityResults = $this->SportStatRegistry->find('byEntityType', ['entity_type' => 'player'])->toArray();
        $this->assertCount(1, $entityResults);

        // Test combination of finders
        $combinedResults = $this->SportStatRegistry->find('bySport', ['sport_id' => 1])
            ->find('byContext', ['context' => 'game'])
            ->find('byEntityType', ['entity_type' => 'player'])
            ->toArray();

        $this->assertGreaterThanOrEqual(1, count($combinedResults));
    }
}
