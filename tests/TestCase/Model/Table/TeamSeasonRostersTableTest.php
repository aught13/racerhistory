<?php
declare(strict_types=1);

namespace App\Test\TestCase\Model\Table;

use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;

class TeamSeasonRostersTableTest extends TestCase
{
    protected array $fixtures = [
        'app.TeamSeasonRosters',
        'app.TeamSeasons',
        'app.Teams',
        'app.Seasons',
        'app.Persons',
    ];

    protected $TeamSeasonRosters;

    /**
     * Sets up the test case.
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->TeamSeasonRosters = TableRegistry::getTableLocator()->get('TeamSeasonRosters');
    }

    /**
     * Tears down the test case.
     */
    public function tearDown(): void
    {
        unset($this->TeamSeasonRosters);
        parent::tearDown();
    }

    /**
     * Tests validation success.
     */
    public function testValidationSuccess(): void
    {
        $entity = $this->TeamSeasonRosters->newEntity([
            'team_season_id' => 1,
            'person_id' => 1,
            'roster_number' => '34',
            'roster_position' => 'C',
        ]);
        $this->assertEmpty($entity->getErrors());
        $this->assertNotFalse($this->TeamSeasonRosters->save($entity));
    }

    /**
     * Tests validation missing required.
     */
    public function testValidationMissingRequired(): void
    {
        $entity = $this->TeamSeasonRosters->newEntity([
            'team_season_id' => null,
            'person_id' => null,
        ]);
        $this->assertNotEmpty($entity->getErrors());
        $this->assertArrayHasKey('team_season_id', $entity->getErrors());
        $this->assertArrayHasKey('person_id', $entity->getErrors());
    }
}
