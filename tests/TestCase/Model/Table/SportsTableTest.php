<?php

declare(strict_types=1);

namespace App\Test\TestCase\Model\Table;

use App\Model\Table\SportsTable;
use Cake\TestSuite\TestCase;

/**
 * App\Model\Table\SportsTable Test Case
 */
class SportsTableTest extends TestCase
{
    /**
     * Test subject
     *
     * @var \App\Model\Table\SportsTable
     */
    protected $Sports;

    /**
     * Fixtures
     *
     * @var array<string>
     */
    protected array $fixtures = [
        'app.Sports',
    ];

    /**
     * setUp method
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        $config = $this->getTableLocator()->exists('Sports') ? [] : ['className' => SportsTable::class];
        $this->Sports = $this->getTableLocator()->get('Sports', $config);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    protected function tearDown(): void
    {
        parent::tearDown();
    }

    /**
     * Test validationDefault method
     *
     * @return void
     */
    public function testValidationDefault()
    {
        // Test that sport_name is required
        $sport = $this->Sports->newEntity(['sport_name' => '']);
        $errors = $sport->getErrors();
        $this->assertArrayHasKey('sport_name', $errors);

        // Test that sport_name cannot be too long
        $sport = $this->Sports->newEntity(['sport_name' => str_repeat('a', 163)]);
        $errors = $sport->getErrors();
        $this->assertArrayHasKey('sport_name', $errors);

        // Test valid sport creation
        $sport = $this->Sports->newEntity(['sport_name' => 'Valid Sport']);
        $errors = $sport->getErrors();
        $this->assertArrayNotHasKey('sport_name', $errors);
    }

    /**
     * Test sport creation
     *
     * @return void
     */
    public function testCreateSport()
    {
        $data = [
            'sport_name' => 'Tennis',
        ];

        $sport = $this->Sports->newEntity($data);
        $result = $this->Sports->save($sport);

        $this->assertInstanceOf('App\Model\Entity\Sport', $result);
        $this->assertEquals('Tennis', $result->sport_name);
        $this->assertNotEmpty($result->id);
    }

    /**
     * Test sport name uniqueness
     *
     * @return void
     */
    public function testSportNameUniqueness()
    {
        // Try to create a sport with the same name as in fixture
        $data = [
            'sport_name' => 'Basketball', // This exists in fixture
        ];

        $sport = $this->Sports->newEntity($data);
        $result = $this->Sports->save($sport);

        $this->assertFalse($result);
        $errors = $sport->getErrors();
        $this->assertArrayHasKey('sport_name', $errors);
    }

    /**
     * Test table configuration
     *
     * @return void
     */
    public function testTableConfiguration()
    {
        $this->assertEquals('sports', $this->Sports->getTable());
        $this->assertEquals('id', $this->Sports->getPrimaryKey());
        $this->assertEquals('sport_name', $this->Sports->getDisplayField());
        $this->assertTrue($this->Sports->hasBehavior('Timestamp'));
    }

    /**
     * Test find operations
     *
     * @return void
     */
    public function testFindOperations()
    {
        // Test find all
        $sports = $this->Sports->find()->all();
        $this->assertCount(3, $sports);

        // Test find by name
        $sport = $this->Sports->find()->where(['sport_name' => 'Basketball'])->first();
        $this->assertEquals('Basketball', $sport->sport_name);

        // Test display field
        $list = $this->Sports->find('list')->toArray();
        $this->assertEquals('Basketball', $list[1]);
    }
}
