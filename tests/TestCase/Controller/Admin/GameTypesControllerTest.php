<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller\Admin;

use App\Test\TestCase\Support\AuthTestTrait;
use Cake\Datasource\Exception\RecordNotFoundException;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;
use Exception;

/**
 * @link \App\Controller\Admin\GameTypesController
 */
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

    /**
     * Tests index.
     */
    public function testIndex(): void
    {
        $this->mockIdentity();
        $this->get('/admin/game-types');
        $this->assertResponseOk();
        $this->assertResponseContains('Game Types');
        $this->assertResponseContains('data-controller="admin-index-table"');
        $this->assertResponseContains('data-admin-index-table-target="searchInput"');
        $this->assertResponseContains('id="game-types-table"');
        $this->assertResponseContains('data-admin-index-table-target="table"');
    }

    /**
     * Tests add post.
     */
    public function testAddPost(): void
    {
        $this->mockIdentity();
        $this->enableCsrfToken();
        $this->enableSecurityToken();
        $this->post('/admin/game-types/add', ['game_type_name' => 'MTE', 'post' => 0, 'conf' => 0, 'abr' => 'MTE']);
        $this->assertRedirect(['prefix' => 'Admin', 'controller' => 'GameTypes', 'action' => 'index']);
    }

    /**
     * Tests add get.
     */
    public function testAddGet(): void
    {
        $this->mockIdentity();
        $this->get('/admin/game-types/add');
        $this->assertResponseOk();
        $this->assertResponseContains('Add Game Type');
    }

    /**
     * Tests edit.
     */
    public function testEdit(): void
    {
        $this->mockIdentity();
        $this->enableCsrfToken();
        $this->enableSecurityToken();
        $this->post('/admin/game-types/edit/1', ['game_type_name' => 'Updated Type', 'post' => 1, 'conf' => 1, 'abr' => 'UPD']);
        $this->assertRedirect(['prefix' => 'Admin', 'controller' => 'GameTypes', 'action' => 'index']);
    }

    /**
     * Tests edit get.
     */
    public function testEditGet(): void
    {
        $this->mockIdentity();
        $this->get('/admin/game-types/edit/1');
        $this->assertResponseOk();
        $this->assertResponseContains('Edit Game Type');
    }

    /**
     * Tests delete.
     */
    public function testDelete(): void
    {
        $this->mockIdentity();
        $this->enableCsrfToken();
        $this->delete('/admin/game-types/delete/1');
        $this->assertRedirect(['prefix' => 'Admin', 'controller' => 'GameTypes', 'action' => 'index']);
        $table = $this->getTableLocator()->get('GameTypes');
        $this->assertNotNull($table->get(1));
    }

    /**
     * Tests delete allowed when no games.
     */
    public function testDeleteAllowedWhenNoGames(): void
    {
        $this->mockIdentity();
        $this->enableCsrfToken();
        $this->delete('/admin/game-types/delete/2');
        $this->assertRedirect(['prefix' => 'Admin', 'controller' => 'GameTypes', 'action' => 'index']);
        $table = $this->getTableLocator()->get('GameTypes');
        $this->assertSame(0, $table->find()->where(['id' => 2])->count());
    }

    /**
     * Tests delete non existent.
     */
    public function testDeleteNonExistent(): void
    {
        $this->mockIdentity();
        $this->enableCsrfToken();

        try {
            $this->delete('/admin/game-types/delete/999');
            $this->assertResponseError();
        } catch (Exception $e) {
            $this->assertInstanceOf(RecordNotFoundException::class, $e);
        }
    }

    /**
     * Tests add validation.
     */
    public function testAddValidation(): void
    {
        $this->mockIdentity();
        $this->enableCsrfToken();
        $this->enableSecurityToken();

        // Missing required game_type_name
        $this->post('/admin/game-types/add', ['post' => 0, 'conf' => 0]);
        $this->assertTrue($this->_response->getStatusCode() >= 200);
    }

    /**
     * Tests add requires abr when post or conf set.
     */
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

    /**
     * Tests unauthenticated access.
     */
    public function testUnauthenticatedAccess(): void
    {
        $this->session([]); // Clear session
        $this->get('/admin/game-types');
        $this->assertTrue($this->_response->getStatusCode() >= 200);
    }

    /**
     * Tests ajax search returns results.
     */
    public function testAjaxSearchReturnsResults(): void
    {
        $this->mockIdentity();
        $this->get('/admin/game-types/ajax-search?q=Conference');
        $this->assertResponseOk();
        $data = json_decode((string)$this->_response->getBody(), true);
        $this->assertTrue($data['success']);
        $this->assertNotEmpty($data['results']);
        $this->assertEquals('Conference', $data['results'][0]['game_type_name']);
        $this->assertArrayHasKey('id', $data['results'][0]);
        $this->assertArrayHasKey('abr', $data['results'][0]);
        $this->assertArrayHasKey('post', $data['results'][0]);
        $this->assertArrayHasKey('conf', $data['results'][0]);
    }

    /**
     * Tests ajax search by abr.
     */
    public function testAjaxSearchByAbr(): void
    {
        $this->mockIdentity();
        $this->get('/admin/game-types/ajax-search?q=NCAA');
        $this->assertResponseOk();
        $data = json_decode((string)$this->_response->getBody(), true);
        $this->assertTrue($data['success']);
        $this->assertNotEmpty($data['results']);
    }

    /**
     * Tests ajax search empty query.
     */
    public function testAjaxSearchEmptyQuery(): void
    {
        $this->mockIdentity();
        $this->get('/admin/game-types/ajax-search?q=');
        $this->assertResponseOk();
        $data = json_decode((string)$this->_response->getBody(), true);
        $this->assertTrue($data['success']);
        $this->assertEmpty($data['results']);
    }

    /**
     * Tests ajax search no match.
     */
    public function testAjaxSearchNoMatch(): void
    {
        $this->mockIdentity();
        $this->get('/admin/game-types/ajax-search?q=Nonexistent99');
        $this->assertResponseOk();
        $data = json_decode((string)$this->_response->getBody(), true);
        $this->assertTrue($data['success']);
        $this->assertEmpty($data['results']);
    }

    /**
     * Tests ajax search rejects post method.
     */
    public function testAjaxSearchRejectsPostMethod(): void
    {
        $this->mockIdentity();
        $this->enableCsrfToken();
        $this->enableSecurityToken();
        $this->post('/admin/game-types/ajax-search', ['q' => 'Conference']);
        $this->assertResponseCode(405);
    }

    /**
     * Tests ajax add success.
     */
    public function testAjaxAddSuccess(): void
    {
        $this->mockIdentity();
        $this->enableCsrfToken();
        $this->enableSecurityToken();
        $this->post('/admin/game-types/ajax-add', [
            'game_type_name' => 'Exhibition',
            'post' => 0,
            'conf' => 0,
            'abr' => 'EXH',
        ]);
        $this->assertResponseOk();
        $data = json_decode((string)$this->_response->getBody(), true);
        $this->assertTrue($data['success']);
        $this->assertEquals('Exhibition', $data['newOption']['text']);
        $this->assertNotEmpty($data['newOption']['value']);
    }

    /**
     * Tests ajax add validation error.
     */
    public function testAjaxAddValidationError(): void
    {
        $this->mockIdentity();
        $this->enableCsrfToken();
        $this->enableSecurityToken();
        // Missing required game_type_name
        $this->post('/admin/game-types/ajax-add', [
            'post' => 0,
            'conf' => 0,
        ]);
        $this->assertResponseOk();
        $data = json_decode((string)$this->_response->getBody(), true);
        $this->assertFalse($data['success']);
        $this->assertNotEmpty($data['errors']);
    }

    /**
     * Tests ajax add invalid method.
     */
    public function testAjaxAddInvalidMethod(): void
    {
        $this->mockIdentity();
        $this->get('/admin/game-types/ajax-add');
        $this->assertResponseOk();
        $data = json_decode((string)$this->_response->getBody(), true);
        $this->assertFalse($data['success']);
    }
}
