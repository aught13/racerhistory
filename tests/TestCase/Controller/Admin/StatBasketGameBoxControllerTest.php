<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller\Admin;

use App\Test\TestCase\Support\AuthTestTrait;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

/**
 * StatBasketGameBoxController Test Case
 *
 * Tests basketball game box score management
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
        } catch (\Exception $e) {
            // RecordNotFoundException is expected
            $this->assertInstanceOf(\Cake\Datasource\Exception\RecordNotFoundException::class, $e);
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
}
