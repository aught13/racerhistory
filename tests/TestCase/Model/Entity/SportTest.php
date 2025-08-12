<?php
declare(strict_types=1);

namespace App\Test\TestCase\Model\Entity;

use App\Model\Entity\Sport;
use Cake\TestSuite\TestCase;

/**
 * App\Model\Entity\Sport Test Case
 */
class SportTest extends TestCase
{
    /**
     * Test subject
     *
     * @var \App\Model\Entity\Sport
     */
    protected $Sport;

    /**
     * setUp method
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->Sport = new Sport([
            'id' => 1,
            'sport_name' => 'Basketball',
            'created_at' => '2025-01-01 12:00:00',
            'updated_at' => '2025-01-01 12:00:00',
        ]);
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
     * Test entity creation and property access
     *
     * @return void
     */
    public function testEntityCreation()
    {
        $this->assertEquals(1, $this->Sport->id);
        $this->assertEquals('Basketball', $this->Sport->sport_name);
        $this->assertEquals('2025-01-01 12:00:00', $this->Sport->created_at);
        $this->assertEquals('2025-01-01 12:00:00', $this->Sport->updated_at);
    }

    /**
     * Test entity property modification
     *
     * @return void
     */
    public function testPropertyModification()
    {
        $this->Sport->sport_name = 'Football';
        $this->assertEquals('Football', $this->Sport->sport_name);

        $this->Sport->set('sport_name', 'Baseball');
        $this->assertEquals('Baseball', $this->Sport->sport_name);
    }

    /**
     * Test entity accessibility
     *
     * @return void
     */
    public function testEntityAccessibility()
    {
        // Test that properties are accessible by default
        $this->assertTrue($this->Sport->isAccessible('sport_name'));
        $this->assertTrue($this->Sport->isAccessible('created_at'));
        $this->assertTrue($this->Sport->isAccessible('updated_at'));

        // ID is accessible by default in CakePHP entities
        $this->assertTrue($this->Sport->isAccessible('id'));
    }

    /**
     * Test entity conversion to array
     *
     * @return void
     */
    public function testToArray()
    {
        $array = $this->Sport->toArray();

        $this->assertIsArray($array);
        $this->assertArrayHasKey('id', $array);
        $this->assertArrayHasKey('sport_name', $array);
        $this->assertArrayHasKey('created_at', $array);
        $this->assertArrayHasKey('updated_at', $array);

        $this->assertEquals(1, $array['id']);
        $this->assertEquals('Basketball', $array['sport_name']);
    }
}
