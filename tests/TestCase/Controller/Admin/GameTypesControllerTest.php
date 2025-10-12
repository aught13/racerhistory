<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller\Admin;

use App\Test\TestCase\Support\AuthTestTrait;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

class GameTypesControllerTest extends TestCase
{
    use IntegrationTestTrait;
    use AuthTestTrait;

    protected array $fixtures = [
        'app.Users',
        'app.GameTypes',
    ];

    public function testIndex(): void
    {
        $this->mockIdentity();
        $this->get('/admin/game-types');
        $this->assertResponseOk();
        $this->assertResponseContains('Game Types');
    }

    public function testAddPost(): void
    {
        $this->mockIdentity();
        $this->enableCsrfToken();
        $this->enableSecurityToken();
        $this->post('/admin/game-types/add', ['game_type_name' => 'MTE', 'post' => 0, 'conf' => 0, 'ind' => 'MTE']);
        $this->assertRedirect(['prefix' => 'Admin', 'controller' => 'GameTypes', 'action' => 'index']);
    }
}
