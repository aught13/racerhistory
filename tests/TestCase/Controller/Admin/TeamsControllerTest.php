<?php

declare(strict_types=1);

namespace App\Test\TestCase\Controller\Admin;

use App\Test\TestCase\Support\AuthTestTrait;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

/**
 * App\Controller\Admin\TeamsController Test Case
 *
 * Uses the following fixtures:
 *  - app.Teams
 *  - app.Sports
 *  - app.Users
 */
class TeamsControllerTest extends TestCase
{
    use IntegrationTestTrait;
    use AuthTestTrait;

    /**
     * Fixtures
     *
     * @var array<string>
     */
    protected array $fixtures = [
        'app.Teams',
        'app.Sports',
        'app.Users',
    ];

    /**
     * Test index method
     *
     * @return void
     */
    public function testIndex(): void
    {
        $this->mockIdentity();
        $this->get('/admin/teams');
        $this->assertResponseOk();
    }

    /**
     * Test index method without authentication
     *
     * @return void
     */
    public function testIndexUnauthenticated(): void
    {
        $this->get('/admin/teams');
        $this->assertRedirectContains('/users/login');
    }

    /**
     * Test view method
     *
     * @return void
     */
    public function testView(): void
    {
        $this->mockIdentity();
        $this->get('/admin/teams/view/1');
        $this->assertResponseOk();
    }

    /**
     * Test view method with invalid id
     *
     * @return void
     */
    public function testViewInvalidId(): void
    {
        $this->mockIdentity();
        $this->get('/admin/teams/view/999');
        $this->assertResponseError();
    }

    /**
     * Test add method GET
     *
     * @return void
     */
    public function testAddGet(): void
    {
        $this->mockIdentity();
        $this->get('/admin/teams/add');
        $this->assertResponseOk();
        $this->assertResponseContains('Add New Sport');
        $this->assertResponseContains('hidden-sport-form');
        $this->assertResponseContains('_Token');
    }

    /**
     * Test add method POST with valid data
     *
     * @return void
     */
    public function testAddPostValid(): void
    {
        $this->mockIdentity();
        $this->enableCsrfToken();
        $this->enableSecurityToken();

        $data = [
            'sport_id' => 1,
            'team_name' => 'Test Team',
            'team_description' => 'Test description',
            'abbr' => 'TEST',
            'gender' => 'M',
        ];

        $this->post('/admin/teams/add', $data);
        $this->assertRedirect(['prefix' => 'Admin', 'controller' => 'Teams', 'action' => 'index']);
        $this->assertFlashMessage('The team has been saved.');
    }

    /**
     * Test add method POST with invalid data
     *
     * @return void
     */
    public function testAddPostInvalid(): void
    {
        $this->mockIdentity();
        $this->enableCsrfToken();
        $this->enableSecurityToken();
        $this->enableRetainFlashMessages();

        $data = [
            'team_name' => '', // Required field missing
        ];

        $this->post('/admin/teams/add', $data);
        $this->assertResponseOk();
        $this->assertFlashMessage('The team could not be saved. Please, try again.');
    }

    /**
     * Test add method GET with sport_id parameter
     *
     * @return void
     */
    public function testAddGetWithSportId(): void
    {
        $this->mockIdentity();
        $this->get('/admin/teams/add?sport_id=1');
        $this->assertResponseOk();

        // Check that the sport_id is pre-populated in the view variable
        $viewVars = $this->viewVariable('team');
        $this->assertNotNull($viewVars);
        $this->assertEquals(1, $viewVars->sport_id);
    }

    /**
     * Test edit method GET
     *
     * @return void
     */
    public function testEditGet(): void
    {
        $this->mockIdentity();
        $this->get('/admin/teams/edit/1');
        $this->assertResponseOk();
    }

    /**
     * Test edit method POST with valid data
     *
     * @return void
     */
    public function testEditPostValid(): void
    {
        $this->mockIdentity();
        $this->enableCsrfToken();
        $this->enableSecurityToken();

        $data = [
            'sport_id' => 1,
            'team_name' => 'Updated Team Name',
            'team_description' => 'Updated description',
            'abbr' => 'UPD',
            'gender' => 'F',
        ];

        $this->post('/admin/teams/edit/1', $data);
        $this->assertRedirect(['prefix' => 'Admin', 'controller' => 'Teams', 'action' => 'index']);
        $this->assertFlashMessage('The team has been saved.');
    }

    /**
     * Test edit method POST with invalid data
     *
     * @return void
     */
    public function testEditPostInvalid(): void
    {
        $this->mockIdentity();
        $this->enableCsrfToken();
        $this->enableSecurityToken();
        $this->enableRetainFlashMessages();

        $data = [
            'team_name' => '', // Required field empty
        ];

        $this->post('/admin/teams/edit/1', $data);
        $this->assertResponseOk();
        $this->assertFlashMessage('The team could not be saved. Please, try again.');
    }

    /**
     * Test delete method
     *
     * @return void
     */
    public function testDeletePost(): void
    {
        $this->mockIdentity();
        $this->enableCsrfToken();
        $this->enableSecurityToken();

        $this->post('/admin/teams/delete/1');
        $this->assertRedirect(['prefix' => 'Admin', 'controller' => 'Teams', 'action' => 'index']);
        $this->assertFlashMessage('The team has been deleted.');
    }

    /**
     * Test delete method with GET (should fail)
     *
     * @return void
     */
    public function testDeleteGet(): void
    {
        $this->mockIdentity();
        $this->get('/admin/teams/delete/1');
        $this->assertResponseError();
    }

    /**
     * Test bulk delete method
     *
     * @return void
     */
    public function testBulkDelete(): void
    {
        $this->mockIdentity();
        $this->enableCsrfToken();
        $this->enableSecurityToken();

        $data = [
            'bulk_action' => 'delete',
            'team_ids' => [1, 2],
        ];

        $this->post('/admin/teams/bulk', $data);
        $this->assertRedirect(['prefix' => 'Admin', 'controller' => 'Teams', 'action' => 'index']);
        $this->assertFlashMessage('Deleted 2 team(s).');
    }

    /**
     * Test bulk delete with no teams selected
     *
     * @return void
     */
    public function testBulkDeleteNoSelection(): void
    {
        $this->mockIdentity();
        $this->enableCsrfToken();
        $this->enableSecurityToken();

        $data = [
            'bulk_action' => 'delete',
            'team_ids' => [],
        ];

        $this->post('/admin/teams/bulk', $data);
        $this->assertRedirect(['prefix' => 'Admin', 'controller' => 'Teams', 'action' => 'index']);
        $this->assertFlashMessage('No teams selected for deletion.');
    }

    /**
     * Bulk delete with invalid identifiers should be sanitized to empty selection.
     */
    public function testBulkDeleteAllInvalidIds(): void
    {
        $this->mockIdentity();
        $this->enableCsrfToken();
        $this->enableSecurityToken();
        $this->post('/admin/teams/bulk', [
            'bulk_action' => 'delete',
            'team_ids' => ['abc', '', null],
        ]);
        $this->assertRedirect(['prefix' => 'Admin', 'controller' => 'Teams', 'action' => 'index']);
        $this->assertFlashMessage('No teams selected for deletion.');
    }

    /**
     * Bulk delete with only non-existing numeric id after sanitization -> zero deletions.
     */
    public function testBulkDeleteNonExistingAfterSanitize(): void
    {
        $this->mockIdentity();
        $this->enableCsrfToken();
        $this->enableSecurityToken();
        $this->post('/admin/teams/bulk', [
            'bulk_action' => 'delete',
            'team_ids' => ['xyz', '9999', ''],
        ]);
        $this->assertRedirect(['prefix' => 'Admin', 'controller' => 'Teams', 'action' => 'index']);
        $this->assertFlashMessage('No teams could be deleted.');
    }

    /**
     * Test bulk action with invalid action
     *
     * @return void
     */
    public function testBulkInvalidAction(): void
    {
        $this->mockIdentity();
        $this->enableCsrfToken();
        $this->enableSecurityToken();

        $data = [
            'bulk_action' => 'invalid',
            'team_ids' => [1],
        ];

        $this->post('/admin/teams/bulk', $data);
        $this->assertRedirect(['prefix' => 'Admin', 'controller' => 'Teams', 'action' => 'index']);
        $this->assertFlashMessage('Invalid bulk action.');
    }

    /**
     * Index should include confirm delete modal element.
     */
    public function testIndexIncludesConfirmDeleteModal(): void
    {
        $this->mockIdentity();
        $this->get('/admin/teams');
        $this->assertResponseOk();
        $this->assertResponseContains('id="confirm-delete-modal"');
    }
}
