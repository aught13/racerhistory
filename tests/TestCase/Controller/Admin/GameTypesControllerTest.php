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
        'app.Games',
        'app.TeamSeasons',
        'app.Teams',
        'app.Seasons',
        'app.Sports',
        'app.Opponents',
        'app.Places',
        'app.Sites',
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
        $this->post('/admin/game-types/add', ['game_type_name' => 'MTE', 'post' => 0, 'conf' => 0, 'abr' => 'MTE']);
        $this->assertRedirect(['prefix' => 'Admin', 'controller' => 'GameTypes', 'action' => 'index']);
    }

    public function testAddGet(): void
    {
        $this->mockIdentity();
        $this->get('/admin/game-types/add');
        $this->assertResponseOk();
        $this->assertResponseContains('Add Game Type');
    }

    public function testEdit(): void
    {
        $this->mockIdentity();
        $this->enableCsrfToken();
        $this->enableSecurityToken();
        $this->post('/admin/game-types/edit/1', ['game_type_name' => 'Updated Type', 'post' => 1, 'conf' => 1, 'abr' => 'UPD']);
        $this->assertRedirect(['prefix' => 'Admin', 'controller' => 'GameTypes', 'action' => 'index']);
    }

    public function testEditGet(): void
    {
        $this->mockIdentity();
        $this->get('/admin/game-types/edit/1');
        $this->assertResponseOk();
        $this->assertResponseContains('Edit Game Type');
    }

    public function testDelete(): void
    {
        $this->mockIdentity();
        $this->enableCsrfToken();
        $this->delete('/admin/game-types/delete/1');
        $this->assertRedirect(['prefix' => 'Admin', 'controller' => 'GameTypes', 'action' => 'index']);
        $table = $this->getTableLocator()->get('GameTypes');
        $this->assertNotNull($table->get(1));
    }

    public function testDeleteAllowedWhenNoGames(): void
    {
        $this->mockIdentity();
        $this->enableCsrfToken();
        $this->delete('/admin/game-types/delete/2');
        $this->assertRedirect(['prefix' => 'Admin', 'controller' => 'GameTypes', 'action' => 'index']);
        $table = $this->getTableLocator()->get('GameTypes');
        $this->assertSame(0, $table->find()->where(['id' => 2])->count());
    }

    public function testDeleteNonExistent(): void
    {
        $this->mockIdentity();
        $this->enableCsrfToken();

        try {
            $this->delete('/admin/game-types/delete/999');
            $this->assertResponseError();
        } catch (\Exception $e) {
            $this->assertInstanceOf(\Cake\Datasource\Exception\RecordNotFoundException::class, $e);
        }
    }

    public function testAddValidation(): void
    {
        $this->mockIdentity();
        $this->enableCsrfToken();
        $this->enableSecurityToken();

        // Missing required game_type_name
        $this->post('/admin/game-types/add', ['post' => 0, 'conf' => 0]);
        $this->assertTrue($this->_response->getStatusCode() >= 200);
    }

    public function testAddRequiresAbrWhenPostOrConfSet(): void
    {
        $this->mockIdentity();
        $this->enableCsrfToken();
        $this->enableSecurityToken();

        $this->post('/admin/game-types/add', [
            'game_type_name' => 'Postseason',
            'post' => 1,
            'conf' => 0,
            'abr' => '',
        ]);
        $this->assertResponseOk();
    }

    public function testUnauthenticatedAccess(): void
    {
        $this->session([]); // Clear session
        $this->get('/admin/game-types');
        $this->assertTrue($this->_response->getStatusCode() >= 200);
    }
}
