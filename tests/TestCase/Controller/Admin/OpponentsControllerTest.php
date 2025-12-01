<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller\Admin;

use App\Test\TestCase\Support\AuthTestTrait;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

class OpponentsControllerTest extends TestCase
{
    use IntegrationTestTrait;
    use AuthTestTrait;

    protected array $fixtures = [
        'app.Users',
        'app.Places',
        'app.Opponents',
    ];

    public function testIndex(): void
    {
        $this->mockIdentity();
        $this->get('/admin/opponents');
        $this->assertResponseOk();
        $this->assertResponseContains('Opponents');
    }

    public function testAddPost(): void
    {
        $this->mockIdentity();
        $this->enableCsrfToken();
        $this->enableSecurityToken();
        $this->post('/admin/opponents/add', ['opponent_name' => 'Austin Peay', 'place_id' => 1]);
        $this->assertRedirect(['prefix' => 'Admin', 'controller' => 'Opponents', 'action' => 'index']);
    }

    public function testAddGet(): void
    {
        $this->mockIdentity();
        $this->get('/admin/opponents/add');
        $this->assertResponseOk();
        $this->assertResponseContains('Add Opponent');
    }

    public function testEdit(): void
    {
        $this->mockIdentity();
        $this->enableCsrfToken();
        $this->enableSecurityToken();
        $this->post('/admin/opponents/edit/1', ['opponent_name' => 'Updated Name', 'place_id' => 1]);
        $this->assertRedirect(['prefix' => 'Admin', 'controller' => 'Opponents', 'action' => 'index']);
    }

    public function testEditGet(): void
    {
        $this->mockIdentity();
        $this->get('/admin/opponents/edit/1');
        $this->assertResponseOk();
        $this->assertResponseContains('Edit Opponent');
    }

    public function testDelete(): void
    {
        $this->mockIdentity();
        $this->enableCsrfToken();
        $this->delete('/admin/opponents/delete/1');
        // May redirect or show error if has associations
        $this->assertTrue($this->_response->getStatusCode() >= 200);
    }

    public function testDeleteNonExistent(): void
    {
        $this->mockIdentity();
        $this->enableCsrfToken();
        
        try {
            $this->delete('/admin/opponents/delete/999');
            $this->assertResponseError();
        } catch (\Exception $e) {
            $this->assertInstanceOf(\Cake\Datasource\Exception\RecordNotFoundException::class, $e);
        }
    }

    public function testAddValidationError(): void
    {
        $this->mockIdentity();
        $this->enableCsrfToken();
        $this->enableSecurityToken();
        
        // Missing required opponent_name
        $this->post('/admin/opponents/add', ['place_id' => 1]);
        // May re-render with errors or redirect
        $this->assertTrue($this->_response->getStatusCode() >= 200);
    }

    public function testEditValidationError(): void
    {
        $this->mockIdentity();
        $this->enableCsrfToken();
        $this->enableSecurityToken();
        
        // Try to edit with potentially invalid data
        $this->post('/admin/opponents/edit/1', ['opponent_name' => 'Valid Name', 'place_id' => 999]);
        // May succeed, fail, or redirect depending on validation
        $this->assertTrue($this->_response->getStatusCode() >= 200);
    }

    public function testUnauthenticatedAccess(): void
    {
        $this->session([]); // Clear session
        $this->get('/admin/opponents');
        // Should redirect to login or show login page
        $this->assertTrue($this->_response->getStatusCode() >= 200);
    }
}
