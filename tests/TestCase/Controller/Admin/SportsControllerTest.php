<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller\Admin;

use App\Test\TestCase\Support\AuthTestTrait;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

/**
 * App\Controller\Admin\SportsController Test Case
 *
 * @uses \App\Controller\Admin\SportsController
 */
class SportsControllerTest extends TestCase
{
    use IntegrationTestTrait;
    use AuthTestTrait;

    /**
     * Fixtures
     *
     * @var array<string>
     */
    protected array $fixtures = [
        'app.Sports',
        'app.Teams',
        'app.Users',
        'app.SiteOptions',
    ];

    /**
     * Set up method
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->enableCsrfToken();
        $this->enableSecurityToken();
    }

    /**
     * Test index method
     *
     * @return void
     */
    public function testIndexUnauthenticated()
    {
        $this->get('/admin/sports');
        $this->assertRedirect();
        $this->assertRedirectContains('/users/login');
    }

    /**
     * Test index method with authentication
     *
     * @return void
     */
    public function testIndexAuthenticated()
    {
        $this->mockIdentity();
        $this->get('/admin/sports');
        $this->assertResponseOk();
        $this->assertResponseContains('Sports Management');
    }

    /**
     * Test view method
     *
     * @return void
     */
    public function testViewAuthenticated()
    {
        $this->mockIdentity();
        $this->get('/admin/sports/view/1');
        $this->assertResponseOk();
        $this->assertResponseContains('Basketball Details');
        $this->assertResponseContains('Associated Teams');
    }

    /**
     * Test view method displays associated teams
     *
     * @return void
     */
    public function testViewDisplaysAssociatedTeams()
    {
        $this->mockIdentity();
        $this->get('/admin/sports/view/1');
        $this->assertResponseOk();

        // Should contain teams table and team data
        $this->assertResponseContains('Los Angeles Lakers');
        $this->assertResponseContains('LAL');
        $this->assertResponseContains('Male');

        // Should contain action buttons for teams
        $this->assertResponseContains('/admin/teams/view/1');
        $this->assertResponseContains('/admin/teams/edit/1');
        $this->assertResponseContains('/admin/teams/delete/1');

        // Should contain "Add Team" button
        $this->assertResponseContains('/admin/teams/add?sport_id=1');
        $this->assertResponseContains('Add Team');
    }

    /**
     * Test view method for sport with no teams
     *
     * @return void
     */
    public function testViewWithNoTeams()
    {
        $this->mockIdentity();
        $this->get('/admin/sports/view/3'); // Baseball has no teams in fixture
        $this->assertResponseOk();

        $this->assertResponseContains('Baseball Details');
        $this->assertResponseContains('Associated Teams');
        $this->assertResponseContains('No teams are currently associated with this sport');
        $this->assertResponseContains('Add First Team');
        $this->assertResponseContains('/admin/teams/add?sport_id=3');
    }

    /**
     * Test view method loads teams with contain
     *
     * @return void
     */
    public function testViewLoadsTeamsData()
    {
        $this->mockIdentity();
        $this->get('/admin/sports/view/1');
        $this->assertResponseOk();

        // Check that the sport variable is set with teams
        $viewVars = $this->viewVariable('sport');
        $this->assertNotNull($viewVars);
        $this->assertEquals('Basketball', $viewVars->sport_name);
        $this->assertNotEmpty($viewVars->teams);
        $this->assertCount(2, $viewVars->teams); // Basketball has 2 teams in fixture
    }

    /**
     * Test add method GET
     *
     * @return void
     */
    public function testAddGetAuthenticated()
    {
        $this->mockIdentity();
        $this->get('/admin/sports/add');
        $this->assertResponseOk();
        $this->assertResponseContains('Add New Sport');
    }

    /**
     * Test add method POST
     *
     * @return void
     */
    public function testAddPostAuthenticated()
    {
        $this->mockIdentity();

        $data = [
            'sport_name' => 'Test Sport',
        ];
        $this->post('/admin/sports/add', $data);
        $this->assertRedirect('/admin/sports');
        $this->assertFlashMessage('The sport has been saved.');
    }

    /**
     * Test edit method GET
     *
     * @return void
     */
    public function testEditGetAuthenticated()
    {
        $this->mockIdentity();
        $this->get('/admin/sports/edit/1');
        $this->assertResponseOk();
        $this->assertResponseContains('Edit Sport');
    }

    /**
     * Test edit method POST
     *
     * @return void
     */
    public function testEditPostAuthenticated()
    {
        $this->mockIdentity();

        $data = [
            'sport_name' => 'Updated Sport Name',
        ];
        $this->post('/admin/sports/edit/1', $data);
        $this->assertRedirect('/admin/sports');
        $this->assertFlashMessage('The sport has been saved.');
    }

    /**
     * Test delete method
     *
     * @return void
     */
    public function testDeleteAuthenticated()
    {
        $this->mockIdentity();
        $this->post('/admin/sports/delete/1');
        $this->assertRedirect('/admin/sports');
        $this->assertFlashMessage('The sport has been deleted.');
    }

    /**
     * Test bulk delete method
     *
     * @return void
     */
    public function testBulkDeleteAuthenticated()
    {
        $this->mockIdentity();

        $data = [
            'sport_ids' => ['1'],
        ];
        $this->post('/admin/sports/bulkDelete', $data);
        $this->assertRedirect('/admin/sports');
        $this->assertFlashMessage('Deleted 1 sport(s).');
    }

    /**
     * Bulk delete with only invalid (non-numeric/empty) ids -> treated as no selection.
     */
    public function testBulkDeleteAllInvalidIds()
    {
        $this->mockIdentity();
        $this->post('/admin/sports/bulkDelete', [
            'sport_ids' => ['abc', '', null],
        ]);
        $this->assertRedirect('/admin/sports');
        $this->assertFlashMessage('No sports selected for deletion.');
    }

    /**
     * Bulk delete with sanitized list containing only non-existing numeric id -> deletion count 0.
     */
    public function testBulkDeleteNonExistingAfterSanitize()
    {
        $this->mockIdentity();
        $this->post('/admin/sports/bulkDelete', [
            'sport_ids' => ['xyz', '9999', ''], // becomes [9999]
        ]);
        $this->assertRedirect('/admin/sports');
        $this->assertFlashMessage('No sports could be deleted.');
    }

    /**
     * Test AJAX add method POST with FormProtection
     *
     * @return void
     */
    public function testAjaxAddPostWithFormProtection()
    {
        $this->mockIdentity();
        $this->enableCsrfToken();
        $this->enableSecurityToken();

        $data = [
            'sport_name' => 'Test AJAX Sport',
        ];

        $this->post('/admin/sports/ajaxAdd', $data);
        $this->assertResponseOk();
        $this->assertHeader('Content-Type', 'application/json');

        $response = json_decode((string)$this->_response->getBody(), true);
        $this->assertTrue($response['success']);
        $this->assertEquals('Sport has been added successfully.', $response['message']);
        $this->assertArrayHasKey('newOption', $response);
        $this->assertEquals('Test AJAX Sport', $response['newOption']['text']);
    }

    public function testBulkActionAuthenticated()
    {
        $this->mockIdentity();

        $data = [
            'bulk_action' => 'delete',
            'sport_ids' => ['1'],
        ];
        $this->post('/admin/sports/bulk', $data);
        $this->assertRedirect('/admin/sports');
    }

    /**
     * Test AJAX add method with valid data
     *
     * @return void
     */
    public function testAjaxAddValid()
    {
        $this->mockIdentity();
        $this->configRequest(['headers' => ['X-Requested-With' => 'XMLHttpRequest']]);

        $data = [
            'sport_name' => 'Tennis',
        ];

        $this->post('/admin/sports/ajaxAdd', $data);
        $this->assertResponseOk();
        $this->assertContentType('application/json');

        $response = json_decode((string)$this->_response->getBody(), true);
        $this->assertTrue($response['success']);
        $this->assertStringContainsString('successfully', $response['message']);
        $this->assertEquals('Tennis', $response['newOption']['text']);
        $this->assertIsNumeric($response['newOption']['value']);
    }

    /**
     * Test AJAX add method with invalid data
     *
     * @return void
     */
    public function testAjaxAddInvalid()
    {
        $this->mockIdentity();
        $this->configRequest(['headers' => ['X-Requested-With' => 'XMLHttpRequest']]);

        $data = [
            'sport_name' => '', // Required field empty
        ];

        $this->post('/admin/sports/ajaxAdd', $data);
        $this->assertResponseOk();
        $this->assertContentType('application/json');

        $response = json_decode((string)$this->_response->getBody(), true);
        $this->assertFalse($response['success']);
        $this->assertNotEmpty($response['errors']);
    }

    /**
     * Test AJAX add method with duplicate sport name
     *
     * @return void
     */
    public function testAjaxAddDuplicate()
    {
        $this->mockIdentity();
        $this->configRequest(['headers' => ['X-Requested-With' => 'XMLHttpRequest']]);

        $data = [
            'sport_name' => 'Basketball', // Already exists in fixture
        ];

        $this->post('/admin/sports/ajaxAdd', $data);
        $this->assertResponseOk();
        $this->assertContentType('application/json');

        $response = json_decode((string)$this->_response->getBody(), true);
        $this->assertFalse($response['success']);
        $this->assertNotEmpty($response['errors']);
    }

    /**
     * AJAX add via GET should return JSON error (method invalid)
     */
    public function testAjaxAddGetMethod()
    {
        $this->mockIdentity();
        $this->get('/admin/sports/ajaxAdd');
        $this->assertResponseOk();
        $this->assertContentType('application/json');
        $response = json_decode((string)$this->_response->getBody(), true);
        $this->assertFalse($response['success']);
        $this->assertContains('Invalid request method.', $response['errors']);
    }

    /**
     * Sports index should include reusable confirm delete modal element.
     */
    public function testIndexContainsConfirmDeleteModal()
    {
        $this->mockIdentity();
        $this->get('/admin/sports');
        $this->assertResponseOk();
        $this->assertResponseContains('id="confirm-delete-modal"');
    }
}
