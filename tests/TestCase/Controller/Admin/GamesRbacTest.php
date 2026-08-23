<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller\Admin;

use App\Test\TestCase\Support\AuthTestTrait;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

class GamesRbacTest extends TestCase
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
        'app.Roles',
        'app.Permissions',
    ];

    /**
     * Non-admin users without delete permission are denied at route authorization.
     */
    public function testDeleteForbiddenForEditor(): void
    {
        // Non-admin identity (editor) should not be able to delete games
        $this->mockIdentity(['role' => 'editor', 'role_id' => 3, 'id' => 4]);
        $this->configRequest(['attributes' => ['identity' => ['id' => 4, 'role' => 'editor', 'role_id' => 3, 'status' => 'active', 'active' => true]]]);
        $this->enableCsrfToken();
        $this->enableSecurityToken();

        $this->post('/admin/games/delete/1');
        $this->assertRedirectContains('/users/login');

        // Ensure game still exists
        $games = $this->getTableLocator()->get('Games');
        $this->assertNotNull($games->get(1));
    }

    /**
     * Non-admin users without delete permission cannot execute bulk delete actions.
     */
    public function testBulkDeleteForbiddenForEditor(): void
    {
        $this->mockIdentity(['role' => 'editor', 'role_id' => 3, 'id' => 4]);
        $this->configRequest(['attributes' => ['identity' => ['id' => 4, 'role' => 'editor', 'role_id' => 3, 'status' => 'active', 'active' => true]]]);
        $this->enableCsrfToken();
        $this->enableSecurityToken();

        $this->post('/admin/games/bulk', ['bulk_action' => 'delete', 'game_ids' => [1]]);
        $this->assertRedirectContains('/users/login');

        $games = $this->getTableLocator()->get('Games');
        $this->assertNotNull($games->get(1));
    }
}
