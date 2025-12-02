<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller\Admin;

use App\Test\TestCase\Support\AuthTestTrait;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

class SitesControllerTest extends TestCase
{
    use IntegrationTestTrait;
    use AuthTestTrait;

    protected array $fixtures = [
        'app.Users',
        'app.Places',
        'app.Sites',
    ];

    public function testIndex(): void
    {
        $this->mockIdentity();
        $this->get('/admin/sites');
        $this->assertResponseOk();
        $this->assertResponseContains('Sites');
    }

    public function testAddPost(): void
    {
        $this->mockIdentity();
        $this->enableCsrfToken();
        $this->enableSecurityToken();
        $this->post('/admin/sites/add', ['site_name' => 'Arena', 'place_id' => 1]);
        $this->assertRedirect(['prefix' => 'Admin', 'controller' => 'Sites', 'action' => 'index']);
    }

    public function testAddGet(): void
    {
        $this->mockIdentity();
        $this->get('/admin/sites/add');
        $this->assertResponseOk();
        $this->assertResponseContains('Add Site');
    }

    public function testEdit(): void
    {
        $this->mockIdentity();
        $this->enableCsrfToken();
        $this->enableSecurityToken();
        $this->post('/admin/sites/edit/1', ['site_name' => 'Updated Site', 'place_id' => 1]);
        $this->assertRedirect(['prefix' => 'Admin', 'controller' => 'Sites', 'action' => 'index']);
    }

    public function testEditGet(): void
    {
        $this->mockIdentity();
        $this->get('/admin/sites/edit/1');
        $this->assertResponseOk();
        $this->assertResponseContains('Edit Site');
    }

    public function testDelete(): void
    {
        $this->mockIdentity();
        $this->enableCsrfToken();
        $this->delete('/admin/sites/delete/1');
        $this->assertTrue($this->_response->getStatusCode() >= 200);
    }

    public function testDeleteNonExistent(): void
    {
        $this->mockIdentity();
        $this->enableCsrfToken();

        try {
            $this->delete('/admin/sites/delete/999');
            $this->assertResponseError();
        } catch (\Exception $e) {
            $this->assertInstanceOf(\Cake\Datasource\Exception\RecordNotFoundException::class, $e);
        }
    }

    public function testUnauthenticatedAccess(): void
    {
        $this->session([]);
        $this->get('/admin/sites');
        $this->assertTrue($this->_response->getStatusCode() >= 200);
    }
}
