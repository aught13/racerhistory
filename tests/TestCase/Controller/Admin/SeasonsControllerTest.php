<?php

declare(strict_types=1);

namespace App\Test\TestCase\Controller\Admin;

use App\Test\TestCase\Support\AuthTestTrait;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

class SeasonsControllerTest extends TestCase
{
    use IntegrationTestTrait;
    use AuthTestTrait;

    protected array $fixtures = [
        'app.Seasons',
        'app.TeamSeasons',
        'app.Users',
    ];

    public function testIndex(): void
    {
        $this->mockIdentity();
        $this->get('/admin/seasons');
        $this->assertResponseOk();
        $this->assertResponseContains('id="confirm-delete-modal"');
    }

    public function testIndexUnauthenticated(): void
    {
        $this->get('/admin/seasons');
        $this->assertRedirectContains('/users/login');
    }

    public function testView(): void
    {
        $this->mockIdentity();
        $this->get('/admin/seasons/view/1');
        $this->assertResponseOk();
    }

    public function testViewInvalid(): void
    {
        $this->mockIdentity();
        $this->get('/admin/seasons/view/999');
        $this->assertResponseError();
    }

    public function testAddGet(): void
    {
        $this->mockIdentity();
        $this->get('/admin/seasons/add');
        $this->assertResponseOk();
        $this->assertResponseContains('Add New Season');
        $this->assertResponseContains('hidden-season-form');
    }

    public function testAddPostValid(): void
    {
        $this->mockIdentity();
        $this->enableCsrfToken();
        $this->enableSecurityToken();

        $data = [
            'start' => 2025,
            'end' => 2026,
        ];

        $this->post('/admin/seasons/add', $data);
        $this->assertRedirect(['prefix' => 'Admin', 'controller' => 'Seasons', 'action' => 'index']);
        $this->assertFlashMessage('The season has been saved.');
    }

    public function testAddPostInvalid(): void
    {
        $this->mockIdentity();
        $this->enableCsrfToken();
        $this->enableSecurityToken();
        $this->enableRetainFlashMessages();

        $data = [ 'start' => '' ];
        $this->post('/admin/seasons/add', $data);
        $this->assertResponseOk();
        $this->assertFlashMessage('The season could not be saved. Please, try again.');
    // Ensure the error is present in the response body
        $this->assertResponseContains('The season could not be saved.');
    }

    public function testEditGet(): void
    {
        $this->mockIdentity();
        $this->get('/admin/seasons/edit/1');
        $this->assertResponseOk();
    }

    public function testEditPostValid(): void
    {
        $this->mockIdentity();
        $this->enableCsrfToken();
        $this->enableSecurityToken();

        $data = ['start' => 2022, 'end' => 2023];
        $this->post('/admin/seasons/edit/1', $data);
        $this->assertRedirect(['prefix' => 'Admin', 'controller' => 'Seasons', 'action' => 'index']);
        $this->assertFlashMessage('The season has been saved.');
    }

    public function testDeletePost(): void
    {
        $this->mockIdentity();
        $this->enableCsrfToken();
        $this->enableSecurityToken();

        $this->post('/admin/seasons/delete/1');
        $this->assertRedirect(['prefix' => 'Admin', 'controller' => 'Seasons', 'action' => 'index']);
        $this->assertFlashMessage('The season has been deleted.');
    }

    public function testBulkDelete(): void
    {
        $this->mockIdentity();
        $this->enableCsrfToken();
        $this->enableSecurityToken();

        $this->post('/admin/seasons/bulk', ['bulk_action' => 'delete', 'season_ids' => [1,2]]);
        $this->assertRedirect(['prefix' => 'Admin', 'controller' => 'Seasons', 'action' => 'index']);
    }

    public function testBulkDeleteEmptySelection(): void
    {
        $this->mockIdentity();
        $this->enableCsrfToken();
        $this->enableSecurityToken();
        $this->enableRetainFlashMessages();

        // Empty/invalid ids should trigger flash error
        $this->post('/admin/seasons/bulk', ['bulk_action' => 'delete', 'season_ids' => ['']]);
        $this->assertRedirect(['prefix' => 'Admin', 'controller' => 'Seasons', 'action' => 'index']);
        $this->assertFlashMessage('No seasons selected for deletion.');
    }

    public function testBulkInvalidAction(): void
    {
        $this->mockIdentity();
        $this->enableCsrfToken();
        $this->enableSecurityToken();
        $this->enableRetainFlashMessages();

        $this->post('/admin/seasons/bulk', ['bulk_action' => 'nonsense', 'season_ids' => [1]]);
        $this->assertRedirect(['prefix' => 'Admin', 'controller' => 'Seasons', 'action' => 'index']);
        $this->assertFlashMessage('Invalid bulk action.');
    }

    public function testAjaxAdd(): void
    {
        $this->mockIdentity();
        $this->enableCsrfToken();
        $this->enableSecurityToken();

        $this->configRequest(['headers' => ['Accept' => 'application/json']]);
        $this->post('/admin/seasons/ajaxAdd', ['start' => 2028, 'end' => 2029]);
        $this->assertResponseCode(200);
        $body = (string)$this->_response->getBody();
        $this->assertStringContainsString('success', $body);
    }

    public function testAjaxAddInvalidMethod(): void
    {
        $this->mockIdentity();
        $this->configRequest(['headers' => ['Accept' => 'application/json']]);

        // GET to ajaxAdd should return JSON error payload
        $this->get('/admin/seasons/ajaxAdd');
        $this->assertResponseCode(200);
        $body = (string)$this->_response->getBody();
        $this->assertStringContainsString('"success":false', $body);
        $this->assertStringContainsString('Invalid request method', $body);
    }
}
