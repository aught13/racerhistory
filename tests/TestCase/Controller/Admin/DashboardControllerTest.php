<?php
namespace App\Test\TestCase\Controller\Admin;

use App\Controller\Admin\DashboardController;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

class DashboardControllerTest extends TestCase
{
    use IntegrationTestTrait;

    public function testIndex(): void
    {
        $this->get('/admin');
        $this->assertResponseOk();
        $this->assertResponseContains('Dashboard');
    }
}
