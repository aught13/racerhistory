<?php
declare(strict_types=1);

namespace App\Test\TestCase\Model\Table;

use App\Model\Table\TeamsTable;
use Cake\TestSuite\TestCase;

/**
 * TeamsTable tests covering validation and beforeSave behavior.
 */
class TeamsTableTest extends TestCase
{
    protected array $fixtures = [
        'app.Teams',
        'app.Sports',
    ];

    protected TeamsTable $Teams;

    /**
     * Set up Teams table instance for tests.
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        $config = $this->getTableLocator()->exists('Teams') ? [] : ['className' => TeamsTable::class];
        $this->Teams = $this->getTableLocator()->get('Teams', $config);
    }

    /**
     * Tear down Teams table instance.
     *
     * @return void
     */
    protected function tearDown(): void
    {
        unset($this->Teams);
        parent::tearDown();
    }

    /**
     * Validates that a properly formed team entity has no validation errors.
     *
     * @return void
     */
    public function testValidationDefaultAllowsValidTeam(): void
    {
        $team = $this->Teams->newEntity([
            'sport_key' => 'basketball',
            'team_name' => 'Test Team',
            'abbr' => 'TT',
            'team_nickname' => 'Testers',
            'team_scorebug' => 'TEST',
            'gender' => 'M',
        ]);

        $this->assertEmpty($team->getErrors());
    }

    /**
     * Validates that required fields produce validation errors when missing.
     *
     * @return void
     */
    public function testValidationRequiredFields(): void
    {
        $team = $this->Teams->newEntity([]);
        $errors = $team->getErrors();

        $this->assertArrayHasKey('sport_key', $errors);
        $this->assertArrayHasKey('team_name', $errors);
        $this->assertArrayHasKey('abbr', $errors);
        $this->assertArrayHasKey('team_nickname', $errors);
        $this->assertArrayHasKey('team_scorebug', $errors);
        $this->assertArrayHasKey('gender', $errors);
    }

    /**
     * Ensures beforeSave maps sport_key to sport_id on create.
     *
     * @return void
     */
    public function testBeforeSaveSetsSportIdFromSportKeyOnCreate(): void
    {
        $data = [
            'team_name' => 'Test Team',
            'abbr' => 'TST',
            'team_nickname' => 'Testers',
            'team_scorebug' => 'TST',
            'gender' => 'M',
            'sport_key' => 'basketball',
        ];

        $entity = $this->Teams->newEntity($data);
        $this->assertEmpty($entity->get('sport_id'));

        $saved = $this->Teams->save($entity);
        $this->assertNotFalse($saved);
        $this->assertNotEmpty($saved->get('sport_id'));
        $this->assertSame(1, (int)$saved->get('sport_id'));
    }

    /**
     * Validates gender field rejects invalid values.
     *
     * @return void
     */
    public function testValidationInvalidGender(): void
    {
        $team = $this->Teams->newEntity([
            'sport_key' => 'basketball',
            'team_name' => 'Test Team',
            'abbr' => 'TT',
            'team_nickname' => 'Testers',
            'team_scorebug' => 'TEST',
            'gender' => 'X', // Invalid gender
        ]);

        $errors = $team->getErrors();
        $this->assertArrayHasKey('gender', $errors);
    }

    /**
     * Validates maximum length constraints on team fields.
     *
     * @return void
     */
    public function testValidationMaxLengths(): void
    {
        $team = $this->Teams->newEntity([
            'sport_key' => 'basketball',
            'team_name' => str_repeat('a', 163), // Too long
            'team_description' => str_repeat('b', 241), // Too long
            'abbr' => 'TOOLONG', // Too long
            'team_nickname' => str_repeat('c', 31), // Too long
            'team_scorebug' => 'TOOLONG', // Too long
            'gender' => 'M',
        ]);

        $errors = $team->getErrors();
        $this->assertArrayHasKey('team_name', $errors);
        $this->assertArrayHasKey('team_description', $errors);
        $this->assertArrayHasKey('abbr', $errors);
        $this->assertArrayHasKey('team_nickname', $errors);
        $this->assertArrayHasKey('team_scorebug', $errors);
    }
}
