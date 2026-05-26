<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller\Admin;

use App\Test\TestCase\Support\AuthTestTrait;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

/**
 * @link \App\Controller\Admin\SeasonsController
 */
class SeasonsControllerTest extends TestCase
{
    use IntegrationTestTrait;
    use AuthTestTrait;

    protected array $fixtures = [
        'app.Seasons',
        'app.TeamSeasons',
        'app.Users',
    ];

    /**
     * Tests index.
     */
    public function testIndex(): void
    {
        $this->mockIdentity();
        $this->get('/admin/seasons');
        $this->assertResponseOk();
        $this->assertResponseContains('data-controller="admin-bulk-table"');
        $this->assertResponseContains('data-admin-bulk-table-target="bulkForm"');
        $this->assertResponseContains('data-admin-bulk-table-role="row-checkbox"');
        $this->assertResponseContains('id="confirm-delete-modal"');
    }

    /**
     * Tests index unauthenticated.
     */
    public function testIndexUnauthenticated(): void
    {
        $this->get('/admin/seasons');
        $this->assertRedirectContains('/users/login');
    }

    /**
     * Tests view.
     */
    public function testView(): void
    {
        $this->mockIdentity();
        $this->get('/admin/seasons/view/1');
        $this->assertResponseOk();
    }

    /**
     * Tests view invalid.
     */
    public function testViewInvalid(): void
    {
        $this->mockIdentity();
        $this->get('/admin/seasons/view/999');
        $this->assertResponseError();
    }

    /**
     * Tests add get.
     */
    public function testAddGet(): void
    {
        $this->mockIdentity();
        $this->get('/admin/seasons/add');
        $this->assertResponseOk();
        $this->assertResponseContains('Add New Season');
        $this->assertResponseContains('data-controller="season-form"');
        $this->assertResponseContains('data-season-form-target="startYear"');
        $this->assertResponseContains('data-season-form-target="endYear"');
        $this->assertResponseContains('hidden-season-form');
    }

    /**
     * Tests add post valid.
     */
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

    /**
     * Tests add post invalid.
     */
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

    /**
     * Tests edit get.
     */
    public function testEditGet(): void
    {
        $this->mockIdentity();
        $this->get('/admin/seasons/edit/1');
        $this->assertResponseOk();
    }

    /**
     * Tests edit post valid.
     */
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

    /**
     * Tests delete post.
     */
    public function testDeletePost(): void
    {
        $this->mockIdentity();
        $this->enableCsrfToken();
        $this->enableSecurityToken();

        $this->post('/admin/seasons/delete/1');
        $this->assertRedirect(['prefix' => 'Admin', 'controller' => 'Seasons', 'action' => 'index']);
        $this->assertFlashMessage('The season has been deleted.');
    }

    /**
     * Tests bulk delete.
     */
    public function testBulkDelete(): void
    {
        $this->mockIdentity();
        $this->enableCsrfToken();
        $this->enableSecurityToken();

        $this->post('/admin/seasons/bulk', ['bulk_action' => 'delete', 'season_ids' => [1,2]]);
        $this->assertRedirect(['prefix' => 'Admin', 'controller' => 'Seasons', 'action' => 'index']);
    }

    /**
     * Tests bulk delete empty selection.
     */
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

    /**
     * Tests bulk invalid action.
     */
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

    /**
     * Tests ajax add.
     */
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

    /**
     * Tests ajax add invalid method.
     */
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

    /**
     * Tests view with previous and next seasons.
     */
    public function testViewWithPreviousAndNextSeasons(): void
    {
        $this->mockIdentity();

        // Create additional seasons for navigation testing
        $seasonsTable = $this->getTableLocator()->get('Seasons');
        $seasons = [
            ['start' => 2020, 'end' => 2021],
            ['start' => 2025, 'end' => 2026],
        ];

        foreach ($seasons as $seasonData) {
            $season = $seasonsTable->newEntity($seasonData);
            $seasonsTable->save($season);
        }

        // View middle season (2023-2024, fixture ID 1), should have both previous and next
        $this->get('/admin/seasons/view/1');
        $this->assertResponseOk();

        // Check that navigation variables are set
        $previousSeason = $this->viewVariable('previousSeason');
        $this->assertNotNull($previousSeason, 'Previous season should be available');
        $this->assertEquals(2021, $previousSeason->end, 'Previous season should be 2020-2021');

        $nextSeason = $this->viewVariable('nextSeason');
        $this->assertNotNull($nextSeason, 'Next season should be available');
        $this->assertEquals(2025, $nextSeason->end, 'Next season should be 2024-2025');
    }

    /**
     * Tests view first season has no previous.
     */
    public function testViewFirstSeasonHasNoPrevious(): void
    {
        $this->mockIdentity();

        // Clear all seasons except one to test first season behavior
        $seasonsTable = $this->getTableLocator()->get('Seasons');
        $seasonsTable->deleteAll(['id !=' => 1]);

        // View the only season (should have no previous or next)
        $this->get('/admin/seasons/view/1');
        $this->assertResponseOk();

        $previousSeason = $this->viewVariable('previousSeason');
        $this->assertNull($previousSeason, 'Only season should have no previous season');

        $nextSeason = $this->viewVariable('nextSeason');
        $this->assertNull($nextSeason, 'Only season should have no next season');
    }

    /**
     * Tests view navigation buttons in template.
     */
    public function testViewNavigationButtonsInTemplate(): void
    {
        $this->mockIdentity();

        // Create an additional season to ensure navigation appears
        $seasonsTable = $this->getTableLocator()->get('Seasons');
        $season = $seasonsTable->newEntity(['start' => 2025, 'end' => 2026]);
        $seasonsTable->save($season);

        $this->get('/admin/seasons/view/1');
        $this->assertResponseOk();

        // Check for navigation elements in template
        $this->assertResponseContains('bi-chevron-left', 'Previous button icon should be present');
        $this->assertResponseContains('bi-chevron-right', 'Next button icon should be present');
        $this->assertResponseContains('btn-outline-secondary', 'Navigation buttons should have correct styling');
    }

    /**
     * Test admin seasons pages include turbo-frame for SPA navigation.
     */
    public function testAdminPagesContainTurboFrame(): void
    {
        $this->mockIdentity();
        $this->get('/admin/seasons');
        $this->assertResponseOk();
        $this->assertResponseContains('<turbo-frame id="admin-content"');
    }
}
