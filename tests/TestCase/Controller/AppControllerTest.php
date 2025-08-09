<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller;

use App\Controller\AppController;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

/**
 * App\Controller\AppController Test Case
 */
class AppControllerTest extends TestCase
{
    use IntegrationTestTrait;

    /**
     * Fixtures
     *
     * @var array<string>
     */
    protected array $fixtures = [
        'app.Users',
    ];

    /**
     * Test initialize method components loading
     *
     * @return void
     */
    public function testInitializeComponents(): void
    {
        $request = $this->createMock(\Cake\Http\ServerRequest::class);
        $controller = new AppController($request);
        $controller->initialize();

        $this->assertTrue($controller->components()->has('Flash'));
        $this->assertTrue($controller->components()->has('FormProtection'));
    }

    /**
     * Test beforeFilter for non-admin, non-user controllers
     *
     * @return void
     */
    public function testBeforeFilterPublicController(): void
    {
        $this->get('/pages/display/home');
        $this->assertResponseOk();
    }

    /**
     * Test that public pages are accessible without authentication
     *
     * @return void
     */
    public function testPublicPagesAccess(): void
    {
        // Test home page access
        $this->get('/');
        $this->assertResponseOk();

        // Test pages controller
        $this->get('/pages/display/home');
        $this->assertResponseOk();
    }

    /**
     * Test Flash component is available
     *
     * @return void
     */
    public function testFlashComponentLoaded(): void
    {
        $request = $this->createMock(\Cake\Http\ServerRequest::class);
        $controller = new AppController($request);
        $controller->initialize();

        $this->assertTrue($controller->components()->has('Flash'));
    }

    /**
     * Test FormProtection component is loaded
     *
     * @return void
     */
    public function testFormProtectionComponentLoaded(): void
    {
        $request = $this->createMock(\Cake\Http\ServerRequest::class);
        $controller = new AppController($request);
        $controller->initialize();

        $this->assertTrue($controller->components()->has('FormProtection'));
    }
}
