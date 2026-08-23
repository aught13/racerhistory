<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller\Admin;

use App\Test\TestCase\Support\AuthTestTrait;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

class AuthDebugTest extends TestCase
{
    use IntegrationTestTrait;
    use AuthTestTrait;

    protected array $fixtures = [
        'app.Users',
        'app.Roles',
        'app.Permissions',
        'app.Teams',
        'app.Seasons',
        'app.TeamSeasons',
        'app.Places',
        'app.Sites',
        'app.Opponents',
        'app.GameTypes',
        'app.Games',
    ];

    /**
     * Editor role with read permission can access the games index.
     */
    public function testEditorCanAccessGamesIndex(): void
    {
        $this->mockIdentity(['role' => 'editor', 'role_id' => 3, 'id' => 4, 'status' => 'active', 'active' => true]);
        $this->get('/admin/games');
        $this->assertResponseOk();
    }

    /**
     * Editor role without delete permission is denied before controller delete logic runs.
     */
    public function testEditorPostDeleteDebug(): void
    {
        $this->mockIdentity(['role' => 'editor', 'role_id' => 3, 'id' => 4, 'status' => 'active', 'active' => true]);
        $this->enableCsrfToken();
        $this->enableSecurityToken();

        $this->post('/admin/games/delete/1');
        $this->assertRedirectContains('/users/login');

        $games = $this->getTableLocator()->get('Games');
        $this->assertNotNull($games->find()->where(['id' => 1])->first());
    }

    /**
     * Admin role can perform destructive actions in the games admin area.
     */
    public function testAdminPostDeleteDebug(): void
    {
        $this->mockIdentity(['role' => 'admin', 'role_id' => 1, 'id' => 1, 'status' => 'active', 'active' => true]);
        $this->enableCsrfToken();
        $this->enableSecurityToken();

        $this->post('/admin/games/delete/1');

        $this->assertRedirect(['prefix' => 'Admin', 'controller' => 'Games', 'action' => 'index']);

        $games = $this->getTableLocator()->get('Games');
        $this->assertNull($games->find()->where(['id' => 1])->first());
    }
}
