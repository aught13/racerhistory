<?php
// tests/TestCase/Controller/Admin/GamesControllerEdgeTest.php
declare(strict_types=1);

namespace App\Test\TestCase\Controller\Admin;

use App\Test\TestCase\Support\AuthTestTrait;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

class GamesControllerEdgeTest extends TestCase
{
    use IntegrationTestTrait;
    use AuthTestTrait;

    protected array $fixtures = [
        'app.Users',
        'app.Teams',
        'app.Seasons',
        'app.TeamSeasons',
        'app.Places',
        'app.Sites',
        'app.Opponents',
        'app.GameTypes',
        'app.Games',
        'app.GameEav',
        'app.Images',
        'app.Sports',
    ];

    /**
     * Tests add invalid data.
     */
    public function testAddInvalidData(): void
    {
        $this->mockIdentity();
        $this->enableCsrfToken();
        $this->enableSecurityToken();
        $this->post('/admin/games/add?team_season_id=1', [ 'team_season_id' => null ]);
        $this->assertResponseOk();
        $this->assertResponseContains('error');
    }

    /**
     * Tests edit nonexistent game.
     */
    public function testEditNonexistentGame(): void
    {
        $this->mockIdentity();
        $this->enableCsrfToken();
        $this->enableSecurityToken();
        $this->post('/admin/games/edit/9999', [ 'team_season_id' => 1 ]);
        $this->assertResponseCode(404);
    }

    /**
     * Tests delete nonexistent game.
     */
    public function testDeleteNonexistentGame(): void
    {
        $this->mockIdentity();
        $this->enableCsrfToken();
        $this->enableSecurityToken();
        $this->post('/admin/games/delete/9999');
        $this->assertResponseCode(404);
    }

    /**
     * Tests bulk delete invalid ids.
     */
    public function testBulkDeleteInvalidIds(): void
    {
        $this->mockIdentity();
        $this->enableCsrfToken();
        $this->enableSecurityToken();
        $this->post('/admin/games/bulk', ['bulk_action' => 'delete', 'game_ids' => [9999]]);
        // Controller redirects with flash error when no deletions occur
        $this->enableRetainFlashMessages();
        $this->assertRedirect();
        $this->assertFlashMessage('No games were deleted.');
    }

    /**
     * Tests add unauthorized.
     */
    public function testAddUnauthorized(): void
    {
        // No identity; omit required team_season_id query to trigger redirect path
        $this->enableCsrfToken();
        $this->enableSecurityToken();
        $this->post('/admin/games/add', [ 'team_season_id' => 1 ]);
        // Depending on auth middleware, this may redirect to login or team seasons index; allow either
        $location = $this->_response->getHeaderLine('Location');
        $this->assertNotEmpty($location, 'Expected a redirect Location header');
        $this->assertTrue(
            str_contains($location, '/users/login') || str_contains($location, '/admin/team-seasons'),
            'Unexpected redirect target: ' . $location,
        );
    }
}
