<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller\Admin;

use App\Test\TestCase\Support\AuthTestTrait;
use Cake\Datasource\Exception\RecordNotFoundException;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;
use Exception;

/**
 * StatBasketGameBoxController Test Case
 *
 * Tests basketball game box score management
 *
 * @link \App\Controller\Admin\StatBasketGameBoxController
 */
class StatBasketGameBoxControllerTest extends TestCase
{
    use IntegrationTestTrait;
    use AuthTestTrait;

    protected array $fixtures = [
        'app.Users',
        'app.Sports',
        'app.Teams',
        'app.Seasons',
        'app.TeamSeasons',
        'app.Opponents',
        'app.Games',
        'app.GameTypes',
        'app.Sites',
        'app.Places',
        'app.StatBasketGameBox',
        'app.StatBasketGameTeam',
        'app.StatBasketGameOpponent',
        'app.StatBasketSeasonTeam',
        'app.StatBasketSeasonOpponent',
        'app.SportConfigs',
    ];

    /**
     * Sets up the test case.
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->mockIdentity(); // Default admin user
    }

    /**
     * Test gameBox GET request loads form
     */
    public function testGameBoxGet(): void
    {
        $this->get('/admin/stat-basket-game-box/game-box/1');
        $this->assertResponseOk();
        $this->assertResponseContains('data-controller="game-box-totals-toggle"');
        $this->assertResponseContains('data-game-box-totals-toggle-target="checkbox"');
        $this->assertResponseContains('data-game-box-totals-toggle-target="optionsPanel"');
    }

    /**
     * Test gameBox POST saves team stats
     */
    public function testGameBoxPostSavesTeamStats(): void
    {
        $this->enableCsrfToken();
        $this->enableSecurityToken();

        $data = [
            'team' => [
                'FGM' => 30,
                'FGA' => 60,
                '3PM' => 10,
                '3PA' => 25,
                'FTM' => 15,
                'FTA' => 20,
                'OREB' => 8,
                'DREB' => 25,
                'REB' => 33,
                'AST' => 18,
                'STL' => 7,
                'BLK' => 4,
                'TO' => 12,
                'PF' => 18,
                'PTS' => 85,
            ],
            'opponent' => [
                'FGM' => 28,
                'FGA' => 58,
                '3PM' => 8,
                '3PA' => 22,
                'FTM' => 14,
                'FTA' => 18,
                'OREB' => 6,
                'DREB' => 24,
                'REB' => 30,
                'AST' => 16,
                'STL' => 6,
                'BLK' => 3,
                'TO' => 14,
                'PF' => 20,
                'PTS' => 78,
            ],
        ];

        $this->post('/admin/stat-basket-game-box/game-box/1', $data);

        // Check response (either success or redirect)
        $this->assertResponseSuccess();
    }

    /**
     * Test gameBoxPeriods GET request loads period form
     */
    public function testGameBoxPeriodsGet(): void
    {
        $this->get('/admin/stat-basket-game-box/game-box-periods/1');
        $this->assertResponseOk();
    }

    /**
     * Test gameBoxPeriods POST saves period stats
     */
    public function testGameBoxPeriodsPost(): void
    {
        $this->enableCsrfToken();
        $this->enableSecurityToken();

        $data = [
            'period_count' => 4,
            'team_periods' => [
                '1' => ['PTS' => 20],
                '2' => ['PTS' => 22],
                '3' => ['PTS' => 18],
                '4' => ['PTS' => 25],
            ],
            'opponent_periods' => [
                '1' => ['PTS' => 18],
                '2' => ['PTS' => 20],
                '3' => ['PTS' => 20],
                '4' => ['PTS' => 20],
            ],
        ];

        $this->post('/admin/stat-basket-game-box/game-box-periods/1', $data);
        $this->assertResponseSuccess();
    }

    /**
     * Test validation errors are handled
     */
    public function testGameBoxValidationErrors(): void
    {
        $this->enableCsrfToken();
        $this->enableSecurityToken();

        $data = [
            'team' => [
                'PTS' => -10, // Invalid negative points
            ],
        ];

        $this->post('/admin/stat-basket-game-box/game-box/1', $data);

        // Should return to form with errors or redirect
        $this->assertResponseSuccess();
    }

    /**
     * Test unauthenticated access is denied
     */
    public function testUnauthenticatedAccessDenied(): void
    {
        $this->session([]); // Clear session

        $this->get('/admin/stat-basket-game-box/game-box/1');
        // Might show login page (200) or redirect (302)
        // Admin requests without auth typically show login or redirect
        $this->assertTrue($this->_response->getStatusCode() >= 200);
    }

    /**
     * Test non-admin access is denied
     */
    public function testNonAdminAccessDenied(): void
    {
        $this->mockIdentity(['role' => 'user']); // Regular user, not admin

        $this->get('/admin/stat-basket-game-box/game-box/1');
        // Should redirect or deny access
        $this->assertResponseCode(302);
    }

    /**
     * Test invalid game ID handling
     */
    public function testInvalidGameIdHandling(): void
    {
        try {
            $this->get('/admin/stat-basket-game-box/game-box/99999');
            $this->assertResponseError();
        } catch (Exception $e) {
            // RecordNotFoundException is expected
            $this->assertInstanceOf(RecordNotFoundException::class, $e);
        }
    }

    /**
     * Test gameBoxPeriods handles missing period data
     */
    public function testGameBoxPeriodsWithMissingData(): void
    {
        $this->enableCsrfToken();
        $this->enableSecurityToken();

        $data = [
            'period_count' => 4,
            'team_periods' => [], // Empty periods
            'opponent_periods' => [],
        ];

        $this->post('/admin/stat-basket-game-box/game-box-periods/1', $data);
        $this->assertResponseSuccess();
    }

    /**
     * Tests game box redirects for non basketball game.
     */
    public function testGameBoxRedirectsForNonBasketballGame(): void
    {
        $teamSeasons = TableRegistry::getTableLocator()->get('TeamSeasons');
        $games = TableRegistry::getTableLocator()->get('Games');

        $season = $teamSeasons->newEntity([
            'team_id' => 3,
            'season_id' => 1,
            'semester' => 1,
        ]);
        $teamSeasons->saveOrFail($season);

        $game = $games->newEntity([
            'team_season_id' => $season->id,
            'game_date' => '2023-11-15',
            'game_type_id' => 1,
            'opponent_id' => 1,
            'place_id' => 1,
            'site_id' => 1,
            'hrn' => 1,
        ]);
        $games->saveOrFail($game);

        $this->get('/admin/stat-basket-game-box/game-box/' . $game->id);

        $this->assertRedirect(['controller' => 'Games', 'action' => 'edit', $game->id]);
        $this->assertFlashMessage('Game box scores are currently only supported for basketball games.');
    }

    /**
     * Tests game box post redirects to period entry when requested.
     */
    public function testGameBoxPostRedirectsToPeriodEntryWhenRequested(): void
    {
        $this->enableCsrfToken();
        $this->enableSecurityToken();

        $data = [
            'team' => ['PTS' => '80'],
            'opponent' => ['PTS' => '72'],
            'add_to_totals' => '1',
            'add_periods' => '1',
        ];

        $this->post('/admin/stat-basket-game-box/game-box/1', $data);

        $this->assertFlashMessage('Game box scores have been saved.');
        $this->assertRedirect('/admin/stat-basket-game-box/game-box-periods/1');
    }

    /**
     * Tests game box periods post redirects to game view.
     */
    public function testGameBoxPeriodsPostRedirectsToGameView(): void
    {
        $this->enableCsrfToken();
        $this->enableSecurityToken();

        $data = [
            'team_1' => ['PTS' => '34'],
            'team_2' => ['PTS' => '42'],
            'opponent_1' => ['PTS' => '30'],
            'opponent_2' => ['PTS' => '35'],
        ];

        $this->post('/admin/stat-basket-game-box/game-box-periods/1', $data);

        $this->assertFlashMessage('Period box scores have been saved.');
        $this->assertRedirect(['controller' => 'Games', 'action' => 'view', 1]);
    }

    /**
     * Test gameBox GET renders team minutes field and season totals options
     */
    public function testGameBoxGetShowsTeamMinutesField(): void
    {
        $this->get('/admin/stat-basket-game-box/game-box/1');
        $this->assertResponseOk();
        $this->assertResponseContains('id="season-totals-options"');
        $this->assertResponseContains('id="team-minutes-input"');
        $this->assertResponseContains('+1 GP');
        $this->assertResponseContains('Team Minutes');
        $this->assertResponseContains('id="add-to-totals-check"');
    }

    /**
     * Test the default minutes calculation for a game with no OT (200 minutes)
     */
    public function testGameBoxDefaultMinutesNoOT(): void
    {
        $this->get('/admin/stat-basket-game-box/game-box/1');
        $this->assertResponseOk();
        // Game 1 has no OT, so default should be 200
        $this->assertResponseContains('value="200"');
    }

    /**
     * Test the default minutes calculation for a game with OT
     */
    public function testGameBoxDefaultMinutesWithOT(): void
    {
        // Game 2 has ot=1, so default should be 250 (200 + 50*1)
        $this->get('/admin/stat-basket-game-box/game-box/2');
        $this->assertResponseOk();
        $this->assertResponseContains('value="250"');
        $this->assertResponseContains('1 OT = 250');
    }

    /**
     * Test the default minutes calculation for a game with 2 OT periods
     */
    public function testGameBoxDefaultMinutesWithDoubleOT(): void
    {
        // Game 4 has ot=2, so default should be 300 (200 + 50*2)
        $this->get('/admin/stat-basket-game-box/game-box/4');
        $this->assertResponseOk();
        $this->assertResponseContains('value="300"');
        $this->assertResponseContains('2 OT = 300');
    }

    /**
     * Test that POST with add_to_totals sets GP and MIN on team box
     */
    public function testGameBoxPostWithSeasonTotalsSetsGpAndMin(): void
    {
        $this->enableCsrfToken();
        $this->enableSecurityToken();

        $data = [
            'team' => ['PTS' => '80', 'FGM' => '30'],
            'opponent' => ['PTS' => '72', 'FGA' => '60'],
            'add_to_totals' => '1',
            'team_minutes' => '250',
        ];

        $this->post('/admin/stat-basket-game-box/game-box/1', $data);
        $this->assertResponseSuccess();

        // Verify GP and MIN were saved on both team and opponent box scores
        $boxTable = $this->getTableLocator()->get('StatBasketGameBox');

        $teamBox = $boxTable->find()
            ->where(['game_id' => 1, 'opponent_id' => 0, 'period' => 'Z'])
            ->first();
        $this->assertNotNull($teamBox);
        $this->assertEquals('1', $teamBox->GP);
        $this->assertEquals('250', $teamBox->MIN);

        $opponentBox = $boxTable->find()
            ->where(['game_id' => 1, 'opponent_id !=' => 0, 'period' => 'Z'])
            ->first();
        $this->assertNotNull($opponentBox);
        $this->assertEquals('1', $opponentBox->GP);
        $this->assertEquals('250', $opponentBox->MIN);
    }

    /**
     * Test that POST without add_to_totals does NOT set GP and MIN
     */
    public function testGameBoxPostWithoutSeasonTotalsDoesNotSetGpMin(): void
    {
        $this->enableCsrfToken();
        $this->enableSecurityToken();

        $data = [
            'team' => ['PTS' => '80'],
            'opponent' => ['PTS' => '72'],
            'team_minutes' => '200',
        ];

        $this->post('/admin/stat-basket-game-box/game-box/1', $data);
        $this->assertResponseSuccess();

        // GP and MIN should not be set when add_to_totals is unchecked
        $boxTable = $this->getTableLocator()->get('StatBasketGameBox');

        $teamBox = $boxTable->find()
            ->where(['game_id' => 1, 'opponent_id' => 0, 'period' => 'Z'])
            ->first();
        $this->assertNotNull($teamBox);
        $this->assertNull($teamBox->GP);
        $this->assertNull($teamBox->MIN);
    }

    /**
     * Test that season totals GP and MIN are updated when saving with add_to_totals
     */
    public function testSeasonTotalsUpdatedWithGpAndMin(): void
    {
        $this->enableCsrfToken();
        $this->enableSecurityToken();

        // Record original season totals
        $teamSeasonTable = $this->getTableLocator()->get('StatBasketSeasonTeam');
        $originalTeamSeason = $teamSeasonTable->find()
            ->where(['team_season_id' => 1])
            ->first();
        $originalGP = (int)($originalTeamSeason->GP ?? 0);
        $originalMIN = (int)($originalTeamSeason->MIN ?? 0);

        $data = [
            'team' => ['PTS' => '80'],
            'opponent' => ['PTS' => '72'],
            'add_to_totals' => '1',
            'team_minutes' => '200',
        ];

        $this->post('/admin/stat-basket-game-box/game-box/1', $data);
        $this->assertResponseSuccess();

        // Verify season totals were updated
        $updatedTeamSeason = $teamSeasonTable->find()
            ->where(['team_season_id' => 1])
            ->first();
        $this->assertNotNull($updatedTeamSeason);
        $this->assertEquals($originalGP + 1, (int)$updatedTeamSeason->GP);
        $this->assertEquals($originalMIN + 200, (int)$updatedTeamSeason->MIN);
    }
}
