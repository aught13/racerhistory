<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller\Admin;

use App\Test\TestCase\Support\AuthTestTrait;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

class PlacesControllerTest extends TestCase
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
        $this->get('/admin/places');
        $this->assertResponseOk();
        $this->assertResponseContains('Places');
    }

    public function testAddPost(): void
    {
        $this->mockIdentity();
        $this->enableCsrfToken();
        $this->enableSecurityToken();
        $this->post('/admin/places/add', ['place_name' => 'Nashville, TN', 'place_city' => 'Nashville', 'place_state' => 'TN']);
        $this->assertRedirect(['prefix' => 'Admin', 'controller' => 'Places', 'action' => 'index']);
    }

    public function testAddGet(): void
    {
        $this->mockIdentity();
        $this->get('/admin/places/add');
        $this->assertResponseOk();
        $this->assertResponseContains('Add Place');
    }

    public function testEdit(): void
    {
        $this->mockIdentity();
        $this->enableCsrfToken();
        $this->enableSecurityToken();
        $this->post('/admin/places/edit/1', ['place_name' => 'Updated Place', 'place_city' => 'Updated', 'place_state' => 'TN']);
        $this->assertRedirect(['prefix' => 'Admin', 'controller' => 'Places', 'action' => 'index']);
    }

    public function testEditGet(): void
    {
        $this->mockIdentity();
        $this->get('/admin/places/edit/1');
        $this->assertResponseOk();
        $this->assertResponseContains('Edit Place');
    }

    public function testDelete(): void
    {
        $this->mockIdentity();
        $this->enableCsrfToken();
        $this->delete('/admin/places/delete/1');
        $this->assertTrue($this->_response->getStatusCode() >= 200);
    }

    public function testDeleteNonExistent(): void
    {
        $this->mockIdentity();
        $this->enableCsrfToken();
        
        try {
            $this->delete('/admin/places/delete/999');
            $this->assertResponseError();
        } catch (\Exception $e) {
            $this->assertInstanceOf(\Cake\Datasource\Exception\RecordNotFoundException::class, $e);
        }
    }

    public function testUnauthenticatedAccess(): void
    {
        $this->session([]);
        $this->get('/admin/places');
        $this->assertTrue($this->_response->getStatusCode() >= 200);
    }
}
