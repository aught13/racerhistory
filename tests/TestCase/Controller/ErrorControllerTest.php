<?php

declare(strict_types=1);

namespace App\Test\TestCase\Controller;

use App\Controller\ErrorController;
use Cake\Event\EventInterface;
use Cake\Http\ServerRequest;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

/**
 * App\Controller\ErrorController Test Case
 */
class ErrorControllerTest extends TestCase
{
    use IntegrationTestTrait;

    /**
     * Test that ErrorController initializes correctly
     *
     * @return void
     */
    public function testInitialize(): void
    {
        $request = $this->createMock(ServerRequest::class);
        $controller = new ErrorController($request);
        $controller->initialize();
        $this->assertTrue(method_exists($controller, 'beforeRender'));
    }

    /**
     * Test that error pages are accessible without authentication
     *
     * @return void
     */
    public function testErrorPagesUnauthenticatedAccess(): void
    {
        // Since error pages are typically handled by CakePHP's error handling,
        // we'll test by triggering a not found error
        $this->get('/nonexistent-page');
        $this->assertResponseCode(404);
    }

    /**
     * Test beforeRender sets correct template path
     *
     * @return void
     */
    public function testBeforeRenderSetsTemplatePath(): void
    {
        $request = $this->createMock(ServerRequest::class);
        $controller = new ErrorController($request);
        $controller->initialize();

        $event = $this->createMock(EventInterface::class);
        $controller->beforeRender($event);

        $templatePath = $controller->viewBuilder()->getTemplatePath();
        $this->assertEquals('Error', $templatePath);
    }

    /**
     * Test that error layout is set based on debug mode
     *
     * @return void
     */
    public function testErrorLayoutSetBasedOnDebugMode(): void
    {
        $request = $this->createMock(ServerRequest::class);
        $controller = new ErrorController($request);
        $controller->initialize();

        $event = $this->createMock(EventInterface::class);
        $controller->beforeRender($event);

        $layout = $controller->viewBuilder()->getLayout();

        // Layout should be set to either 'error' or 'dev_error' based on debug mode
        $this->assertContains($layout, ['error', 'dev_error']);
    }
}
