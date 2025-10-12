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
}
