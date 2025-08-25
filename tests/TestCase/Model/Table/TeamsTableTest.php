<?php
declare(strict_types=1);

namespace App\Test\TestCase\Model\Table;

use App\Model\Table\TeamsTable;
use Cake\TestSuite\TestCase;

/**
 * App\Model\Table\TeamsTable Test Case
 */
class TeamsTableTest extends TestCase
{
    /**
     * Test fixtures
     *
     * @var array<string>
     */
    protected array $fixtures = [
        'app.Teams',
        'app.Sports',
    ];

    /**
     * Teams Table instance
     *
     * @var \App\Model\Table\TeamsTable
     */
    protected TeamsTable $Teams;

    /**
     * setUp method
     *
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();
        $config = $this->getTableLocator()->exists('Teams') ? [] : ['className' => TeamsTable::class];
        $this->Teams = $this->getTableLocator()->get('Teams', $config);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown(): void
    {
        unset($this->Teams);

        parent::tearDown();
    }

    /**
     * Test validationDefault method
     *
     * @return void
     */
    public function testValidationDefault(): void
    {
        $team = $this->Teams->newEntity([
            'sport_id' => 1,
            'team_name' => 'Test Team',
            'abbr' => 'TT',
            'gender' => 'M',
        ]);

        $this->assertEmpty($team->getErrors());
    }

    /**
     * Test validation fails for missing required fields
     *
     * @return void
     */
    public function testValidationRequiredFields(): void
    {
        $team = $this->Teams->newEntity([]);
        $errors = $team->getErrors();

        $this->assertArrayHasKey('sport_id', $errors);
        $this->assertArrayHasKey('team_name', $errors);
        $this->assertArrayHasKey('abbr', $errors);
        $this->assertArrayHasKey('gender', $errors);
    }

    /**
     * Test validation fails for invalid gender
     *
     * @return void
     */
    public function testValidationInvalidGender(): void
    {
        $team = $this->Teams->newEntity([
            'sport_id' => 1,
            'team_name' => 'Test Team',
            'abbr' => 'TT',
            'gender' => 'X', // Invalid gender
        ]);

        $errors = $team->getErrors();
        $this->assertArrayHasKey('gender', $errors);
    }

    /**
     * Test validation passes for valid genders
     *
     * @return void
     */
    public function testValidationValidGenders(): void
    {
        $validGenders = ['M', 'F', 'C'];

        foreach ($validGenders as $gender) {
            $team = $this->Teams->newEntity([
                'sport_id' => 1,
                'team_name' => 'Test Team ' . $gender,
                'abbr' => 'T' . $gender,
                'gender' => $gender,
            ]);

            $this->assertEmpty($team->getErrors(), "Gender $gender should be valid");
        }
    }

    /**
     * Test validation fails for too long values
     *
     * @return void
     */
    public function testValidationMaxLengths(): void
    {
        $team = $this->Teams->newEntity([
            'sport_id' => 1,
            'team_name' => str_repeat('a', 163), // Too long
            'team_description' => str_repeat('b', 241), // Too long
            'abbr' => 'TOOLONG', // Too long
            'gender' => 'M',
        ]);

        $errors = $team->getErrors();
        $this->assertArrayHasKey('team_name', $errors);
        $this->assertArrayHasKey('team_description', $errors);
        $this->assertArrayHasKey('abbr', $errors);
    }

    /**
     * Test belongsTo association with Sports
     *
     * @return void
     */
    public function testBelongsToSports(): void
    {
        $association = $this->Teams->getAssociation('Sports');
        $this->assertEquals('manyToOne', $association->type());
        $this->assertEquals('sport_id', $association->getForeignKey());
        $this->assertEquals('INNER', $association->getJoinType());
    }
}
