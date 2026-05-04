<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller\Admin;

use App\Test\TestCase\Support\AuthTestTrait;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

/**
 * @link \App\Controller\Admin\TeamSeasonsController
 */
class TeamSeasonsControllerTest extends TestCase
{
    use IntegrationTestTrait;
    use AuthTestTrait;

    protected array $fixtures = [
        'app.TeamSeasons',
        'app.Seasons',
        'app.Teams',
        'app.Users',
        'app.Images',
        'app.TeamSeasonRosters',
        'app.Persons',
        'app.Sports',
        'app.StatBasketSeasonTeam',
        'app.StatBasketSeasonOpponent',
        'app.StatBasketSeasonPerson',
        'app.Games',
        'app.GameTypes',
        'app.Opponents',
        'app.Sites',
        'app.Places',
    ];

    /**
     * Tests index.
     */
    public function testIndex(): void
    {
        $this->mockIdentity();
        $this->get('/admin/team-seasons');
        $this->assertResponseOk();
        $this->assertResponseContains('id="confirm-delete-modal"');
    }

    /**
     * Tests view.
     */
    public function testView(): void
    {
        $this->mockIdentity();
        $this->get('/admin/team-seasons/view/1');
        $this->assertResponseOk();
        // Should include image element debug comment now that fixture sets team_season_image
        // Image presence now handled client-side; ensure basic page content loads instead
        $this->assertResponseContains('Basic Information');
        // Should also contain games management section
        $this->assertResponseContains('Games for this Season');
        // Season statistics heading should appear for basketball
        $this->assertResponseContains('Season Statistics');
    }

    /**
     * Tests view season stats display.
     */
    public function testViewSeasonStatsDisplay(): void
    {
        $this->mockIdentity();
        $this->get('/admin/team-seasons/view/1');
        $this->assertResponseOk();
        $this->assertResponseContains('Season Statistics');
        $this->assertResponseContains('TEAM TOTALS');
        $this->assertResponseContains('OPPONENT TOTALS');
        $this->assertResponseContains('FGM');
        $this->assertResponseContains('PTS');
    }

    /**
     * Tests view contains roster management element.
     */
    public function testViewContainsRosterManagementElement(): void
    {
        $this->mockIdentity();
        $this->get('/admin/team-seasons/view/1');
        $this->assertResponseOk();
        // Assert roster management element is present
        $this->assertResponseContains('Team Roster');
        $this->assertResponseContains('Add Roster Entry');
        $this->assertResponseContains('bulk-action-form-rosters');
        $this->assertResponseContains('rosters-table');
    }

    /**
     * Tests view with roster entries shows table.
     */
    public function testViewWithRosterEntriesShowsTable(): void
    {
        $this->mockIdentity();
        // First create a roster entry
        $rostersTable = $this->getTableLocator()->get('TeamSeasonRosters');
        $roster = $rostersTable->newEntity([
            'team_season_id' => 1,
            'person_id' => 1,
            'roster_number' => '10',
            'roster_position' => 'Forward',
        ]);
        $rostersTable->save($roster);

        $this->get('/admin/team-seasons/view/1');
        $this->assertResponseOk();
        // Should show the roster table with data
        $this->assertResponseContains('rosters-table');
        $this->assertResponseContains('roster-checkbox');
        $this->assertResponseContains('select-all-rosters');
    }

    /**
     * Tests view with no roster entries shows empty message.
     */
    public function testViewWithNoRosterEntriesShowsEmptyMessage(): void
    {
        $this->mockIdentity();
        // Ensure no roster entries exist for a deterministic empty-state assertion
        $rosters = $this->getTableLocator()->get('TeamSeasonRosters');
        $rosters->deleteAll(['team_season_id' => 1]);

        $this->get('/admin/team-seasons/view/1');
        $this->assertResponseOk();
        // Should show empty message when no rosters exist
        $this->assertResponseContains('No roster entries have been created for this team season yet');
        $this->assertResponseContains('Add the first roster entry');
    }

    /**
     * Tests add get.
     */
    public function testAddGet(): void
    {
        $this->mockIdentity();
        $this->get('/admin/team-seasons/add');
        $this->assertResponseOk();
        $this->assertResponseContains('hidden-team-form');
        $this->assertResponseContains('hidden-season-form');
    // Rich text editors textareas present
        $this->assertResponseContains('team-season-preview');
        $this->assertResponseContains('team-season-recap');
    }

    /**
     * Tests add post valid.
     */
    public function testAddPostValid(): void
    {
        $this->mockIdentity();
        $this->enableCsrfToken();
        $this->enableSecurityToken();

    // semester must be an integer per migrations/schema
        $data = ['team_id' => 1, 'season_id' => 1, 'semester' => 1];
        $this->post('/admin/team-seasons/add', $data);
        $this->assertRedirect(['prefix' => 'Admin', 'controller' => 'TeamSeasons', 'action' => 'index']);
    }

    /**
     * Tests add post invalid.
     */
    public function testAddPostInvalid(): void
    {
        $this->mockIdentity();
        $this->enableCsrfToken();
        $this->enableSecurityToken();
        $this->enableRetainFlashMessages();

        // Missing required team_id should fail
        $data = ['team_id' => '', 'season_id' => ''];
        $this->post('/admin/team-seasons/add', $data);
        $this->assertResponseOk();
        $this->assertFlashMessage('The team season could not be saved. Please, try again.');
        $this->assertResponseContains('The team season could not be saved.');
    }

    /**
     * Tests delete post.
     */
    public function testDeletePost(): void
    {
        $this->mockIdentity();
        $this->enableCsrfToken();
        $this->enableSecurityToken();

        $this->post('/admin/team-seasons/delete/1');
        $this->assertRedirect(['prefix' => 'Admin', 'controller' => 'TeamSeasons', 'action' => 'index']);
    }

    /**
     * Tests bulk delete.
     */
    public function testBulkDelete(): void
    {
        $this->mockIdentity();
        $this->enableCsrfToken();
        $this->enableSecurityToken();

        $this->post('/admin/team-seasons/bulk', ['bulk_action' => 'delete', 'team_season_ids' => [1]]);
        $this->assertRedirect(['prefix' => 'Admin', 'controller' => 'TeamSeasons', 'action' => 'index']);
    }

    /**
     * Tests bulk delete empty selection.
     */
    public function testBulkDeleteEmptySelection(): void
    {
        $this->mockIdentity();
        $this->enableCsrfToken();
        $this->enableSecurityToken();
        $this->enableRetainFlashMessages();

        $this->post('/admin/team-seasons/bulk', ['bulk_action' => 'delete', 'team_season_ids' => ['']]);
        $this->assertRedirect(['prefix' => 'Admin', 'controller' => 'TeamSeasons', 'action' => 'index']);
        $this->assertFlashMessage('No team seasons selected for deletion.');
    }

    /**
     * Tests bulk invalid action.
     */
    public function testBulkInvalidAction(): void
    {
        $this->mockIdentity();
        $this->enableCsrfToken();
        $this->enableSecurityToken();
        $this->enableRetainFlashMessages();

        $this->post('/admin/team-seasons/bulk', ['bulk_action' => 'nope', 'team_season_ids' => [1]]);
        $this->assertRedirect(['prefix' => 'Admin', 'controller' => 'TeamSeasons', 'action' => 'index']);
        $this->assertFlashMessage('Invalid bulk action.');
    }

    /**
     * Tests roster bulk delete through team seasons view.
     */
    public function testRosterBulkDeleteThroughTeamSeasonsView(): void
    {
        $this->mockIdentity();
        $this->enableCsrfToken();
        $this->enableSecurityToken();

        // Create roster entries to delete
        $rostersTable = $this->getTableLocator()->get('TeamSeasonRosters');
        $roster1 = $rostersTable->newEntity(['team_season_id' => 1, 'person_id' => 1]);
        $roster2 = $rostersTable->newEntity(['team_season_id' => 1, 'person_id' => 1]);
        $rostersTable->save($roster1);
        $rostersTable->save($roster2);

        // Perform bulk delete via TeamSeasonRosters bulk action
        $this->post('/admin/team-season-rosters/bulk', [
            'bulk_action' => 'delete',
            'team_season_roster_ids' => [$roster1->id, $roster2->id],
        ]);

        // Should redirect back to team season view
        $this->assertRedirectContains('/admin/team-seasons/view/1');

        // Verify rosters were deleted
        $remaining = $rostersTable->find()->where(['id IN' => [$roster1->id, $roster2->id]])->count();
        $this->assertEquals(0, $remaining);
    }

    /**
     * Tests roster bulk delete empty selection through team seasons view.
     */
    public function testRosterBulkDeleteEmptySelectionThroughTeamSeasonsView(): void
    {
        $this->mockIdentity();
        $this->enableCsrfToken();
        $this->enableSecurityToken();
        $this->enableRetainFlashMessages();

        // Try bulk delete with no selections
        $this->post('/admin/team-season-rosters/bulk', [
            'bulk_action' => 'delete',
            'team_season_roster_ids' => [''],
        ]);

        // Should redirect and show error message
        $this->assertRedirect();
        $this->assertFlashMessage('No team season rosters selected for deletion.');
    }

    /**
     * Tests view with same sport navigation.
     */
    public function testViewWithSameSportNavigation(): void
    {
        $this->mockIdentity();

        // Create additional team seasons for the same sport for navigation testing
        $teamSeasonsTable = $this->getTableLocator()->get('TeamSeasons');
        $seasonsTable = $this->getTableLocator()->get('Seasons');

        // Create additional seasons (fixture has 2023-2024 (id=1) and 2024-2025 (id=2))
        $season2022 = $seasonsTable->newEntity(['start' => 2022, 'end' => 2023]);
        $seasonsTable->save($season2022);

        $season2025 = $seasonsTable->newEntity(['start' => 2025, 'end' => 2026]);
        $seasonsTable->save($season2025);

        // Create team seasons for navigation using existing teams (team_id 1 and 2 both have sport_id = 1)
        $teamSeason2022 = $teamSeasonsTable->newEntity([
            'team_id' => 2, // Use different team but same sport
            'season_id' => $season2022->id,
            'semester' => 1, // Fall (integer in fixture)
        ]);
        $teamSeasonsTable->save($teamSeason2022);

        $teamSeason2025 = $teamSeasonsTable->newEntity([
            'team_id' => 2, // Use different team but same sport
            'season_id' => $season2025->id,
            'semester' => 2, // Spring (integer in fixture)
        ]);
        $teamSeasonsTable->save($teamSeason2025);

        // View the middle team season (fixture ID 1 is team_id=1, season_id=1 which is 2023-2024)
        $this->get('/admin/team-seasons/view/1');
        $this->assertResponseOk();

        // Check navigation variables are set correctly
        $previousTeamSeason = $this->viewVariable('previousTeamSeason');
        $this->assertNotNull($previousTeamSeason, 'Previous team season should be available');
        $this->assertEquals(2023, $previousTeamSeason->season->end, 'Previous should be 2022-2023 season');
        $this->assertEquals(1, $previousTeamSeason->team->sport_id, 'Previous should be same sport');

        $nextTeamSeason = $this->viewVariable('nextTeamSeason');
        $this->assertNotNull($nextTeamSeason, 'Next team season should be available');
        // The next should be 2025-2026 since we created that season above
        $this->assertEquals(2026, $nextTeamSeason->season->end, 'Next should be 2025-2026 season');
        $this->assertEquals(1, $nextTeamSeason->team->sport_id, 'Next should be same sport');
    }

    /**
     * Tests view with different sport does not show in navigation.
     */
    public function testViewWithDifferentSportDoesNotShowInNavigation(): void
    {
        $this->mockIdentity();

        // Create a team season in a different sport
        $seasonsTable = $this->getTableLocator()->get('Seasons');
        $teamsTable = $this->getTableLocator()->get('Teams');
        $teamSeasonsTable = $this->getTableLocator()->get('TeamSeasons');
        $sportsTable = $this->getTableLocator()->get('Sports');

        // Create a different sport
        $differentSport = $sportsTable->newEntity(['sport_name' => 'Soccer']);
        $sportsTable->save($differentSport);

        // Create a team in the different sport
        $differentTeam = $teamsTable->newEntity([
            'team_name' => 'Soccer Team',
            'sport_id' => $differentSport->id,
            'abbreviation' => 'SOC',
            'gender' => 'Female',
        ]);
        $teamsTable->save($differentTeam);

        // Create a team season with the different sport in a future season
        $futureSeason = $seasonsTable->newEntity(['start' => 2025, 'end' => 2026]);
        $seasonsTable->save($futureSeason);

        $differentTeamSeason = $teamSeasonsTable->newEntity([
            'team_id' => $differentTeam->id,
            'season_id' => $futureSeason->id,
            'semester' => 'Fall',
        ]);
        $teamSeasonsTable->save($differentTeamSeason);

        // View the original team season (should not see the different sport in navigation)
        $this->get('/admin/team-seasons/view/1');
        $this->assertResponseOk();

        // Should not have the different sport team season in navigation
        $nextTeamSeason = $this->viewVariable('nextTeamSeason');
        if ($nextTeamSeason) {
            $this->assertNotEquals(
                $differentSport->id,
                $nextTeamSeason->team->sport_id,
                'Navigation should not include different sport team seasons',
            );
        }
    }

    /**
     * Tests view navigation buttons show team season links.
     */
    public function testViewNavigationButtonsShowTeamSeasonLinks(): void
    {
        $this->mockIdentity();

        // Create additional team season for navigation
        $teamSeasonsTable = $this->getTableLocator()->get('TeamSeasons');
        $seasonsTable = $this->getTableLocator()->get('Seasons');

        $futureSeason = $seasonsTable->newEntity(['start' => 2025, 'end' => 2026]);
        $seasonsTable->save($futureSeason);

        $futureTeamSeason = $teamSeasonsTable->newEntity([
            'team_id' => 2, // Different team but same sport as fixture
            'season_id' => $futureSeason->id,
            'semester' => 2, // Spring (integer in fixture)
        ]);
        $teamSeasonsTable->save($futureTeamSeason);

        $this->get('/admin/team-seasons/view/1');
        $this->assertResponseOk();

        // Check for team season navigation elements
        $this->assertResponseContains('/admin/team-seasons/view/', 'Should link to team seasons controller');
        $this->assertResponseContains('bi-chevron-left', 'Previous button icon should be present');
        $this->assertResponseContains('bi-chevron-right', 'Next button icon should be present');
        $this->assertResponseContains('2025-2026', 'Should show next season year range');
    }

    /**
     * Test that games are sorted by game_date ascending (chronological)
     */
    public function testViewGamesSortedByDateAscending(): void
    {
        $this->mockIdentity();
        $this->get('/admin/team-seasons/view/1');
        $this->assertResponseOk();

        $teamSeasonGames = $this->viewVariable('teamSeasonGames');
        $this->assertNotNull($teamSeasonGames);

        $dates = [];
        foreach ($teamSeasonGames as $game) {
            $dates[] = $game->game_date->format('Y-m-d');
        }

        // Verify dates are in ascending order
        $sorted = $dates;
        sort($sorted);
        $this->assertSame($sorted, $dates, 'Games should be sorted by date ascending');
    }
}
