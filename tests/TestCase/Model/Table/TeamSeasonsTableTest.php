<?php
declare(strict_types=1);

namespace App\Test\TestCase\Model\Table;

use App\Model\Table\TeamSeasonsTable;
use Cake\TestSuite\TestCase;

class TeamSeasonsTableTest extends TestCase
{
    protected array $fixtures = [
        'app.TeamSeasons',
        'app.Teams',
        'app.Seasons',
    ];

    private TeamSeasonsTable $TeamSeasons;

    /**
     * Sets up the test case.
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->TeamSeasons = $this->getTableLocator()->get('TeamSeasons');
    }

    /**
     * Tears down the test case.
     */
    protected function tearDown(): void
    {
        unset($this->TeamSeasons);
        parent::tearDown();
    }

    /**
     * Tests validation requires required fields.
     */
    public function testValidationRequiresRequiredFields(): void
    {
        $entity = $this->TeamSeasons->newEmptyEntity();
        $entity = $this->TeamSeasons->patchEntity($entity, [
            'season_id' => 1,
            'semester' => 1,
        ]);

        $this->assertArrayHasKey('team_id', $entity->getErrors());
    }

    /**
     * Tests validation allows optional fields.
     */
    public function testValidationAllowsOptionalFields(): void
    {
        $data = [
            'team_id' => 1,
            'season_id' => 1,
            'semester' => 1,
            'league' => 'Premier League',
            'league_abbr' => 'PL',
            'league_finish' => 'Champion',
            'team_season_notes' => 'Season notes',
            'team_season_image' => 'cover.jpg',
            'team_season_preview' => 'Preview text',
            'team_season_recap' => 'Recap text',
        ];

        $entity = $this->TeamSeasons->newEntity($data);
        $this->assertEmpty($entity->getErrors());
        $saved = $this->TeamSeasons->save($entity);
        $this->assertNotEmpty($saved);
        $this->assertNotEmpty($saved->id);
    }

    /**
     * Tests associations contain team and season.
     */
    public function testAssociationsContainTeamAndSeason(): void
    {
        $teamSeason = $this->TeamSeasons->get(1, contain: ['Teams', 'Seasons']);

        $this->assertNotEmpty($teamSeason->team);
        $this->assertNotEmpty($teamSeason->season);
        $this->assertSame(1, $teamSeason->team->get('id'));
        $this->assertSame(1, $teamSeason->season->get('id'));
    }

    /**
     * Tests timestamp behavior populates timestamps.
     */
    public function testTimestampBehaviorPopulatesTimestamps(): void
    {
        $entity = $this->TeamSeasons->newEntity([
            'team_id' => 1,
            'season_id' => 1,
            'semester' => 2,
        ]);

        $saved = $this->TeamSeasons->save($entity);
        $this->assertNotEmpty($saved);
        $this->assertNotEmpty($saved->created_at);
        $this->assertNotEmpty($saved->updated_at);
    }
}
