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
}
