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
        'app.SportConfigs',
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
        $this->assertResponseContains('data-controller="admin-bulk-table"');
        $this->assertResponseContains('data-admin-bulk-table-target="bulkForm"');
        $this->assertResponseContains('data-admin-bulk-table-role="row-checkbox"');
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
        $this->assertResponseContains('data-controller="sports-form"');
        $this->assertResponseContains('data-sports-form-target="form"');
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
        $this->assertResponseContains('data-controller="sports-form"');
        $this->assertResponseContains('data-sports-form-target="form"');
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

    /**
     * Tests bulk action authenticated.
     */
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
        $this->assertResponseContains('data-controller="admin-confirm-delete"');
    }

    /**
     * Test editConfigs method GET request
     *
     * @return void
     */
    public function testEditConfigsGet(): void
    {
        $this->mockIdentity();
        $this->get('/admin/sports/edit-configs/1');
        $this->assertResponseOk();
        $this->assertResponseContains('Edit Sport Configurations');
        $this->assertResponseContains('data-controller="sports-configs-form"');
        $this->assertResponseContains('data-sports-configs-form-target="periodNamesContainer"');
        $this->assertResponseContains('data-sports-configs-form-target="settingsContainer"');
    }

    /**
     * Test editConfigs method POST request
     *
     * @return void
     */
    public function testEditConfigsPost(): void
    {
        $this->mockIdentity();

        $configData = [
            'configs' => [
                'period_name_2' => [
                    'value' => 'Half',
                    'description' => 'Updated period name for 2 periods',
                ],
                'officials' => [
                    'value' => 'Referee',
                    'description' => 'Updated officials info',
                ],
                'default_periods' => [
                    'value' => '2',
                    'description' => 'Default number of periods',
                ],
            ],
        ];

        $this->post('/admin/sports/edit-configs/1', $configData);
        $this->assertRedirect('/admin/sports/configs/1');
        $this->assertFlashMessage('Sport configurations have been updated.');
    }

    /**
     * Test configs method GET request
     *
     * @return void
     */
    public function testConfigsGet(): void
    {
        $this->mockIdentity();
        $this->get('/admin/sports/configs/1');
        $this->assertResponseOk();
        $this->assertResponseContains('Sport Configurations');
        $this->assertResponseContains('Officials');
    }

    /**
     * Test editConfigs loads existing fixture values correctly
     *
     * @return void
     */
    public function testEditConfigsLoadsExistingValues(): void
    {
        $this->mockIdentity();

        // Load the edit form - should show fixture values, not defaults
        $this->get('/admin/sports/edit-configs/1');
        $this->assertResponseOk();

        // Should contain values from the fixture (Half, Quarter, Basketball officials)
        $this->assertResponseContains('Half'); // period_name_2 from fixture
        $this->assertResponseContains('Quarter'); // period_name_4 from fixture
        // Note: Officials in fixture are JSON array, should be displayed as comma-separated
        $this->assertResponseContains('Referee 1'); // from fixture officials array
    }

    /**
     * Test that saved values persist after save/reload cycle
     *
     * @return void
     */
    public function testSaveAndReloadCycle(): void
    {
        $this->mockIdentity();

        // Save custom officials data
        $configData = [
            'configs' => [
                'officials' => [
                    'value' => 'Home Plate, First Base, Third Base',
                    'description' => 'Baseball officials',
                ],
            ],
        ];

        $this->post('/admin/sports/edit-configs/1', $configData);
        $this->assertRedirect('/admin/sports/configs/1');

        // Now verify it was saved by checking the configs view
        $this->get('/admin/sports/configs/1');
        $this->assertResponseOk();
        $this->assertResponseContains('Home Plate');
        $this->assertResponseContains('First Base');
        $this->assertResponseContains('Third Base');
    }

    /**
     * Test that edit form shows saved values (the core issue)
     *
     * @return void
     */
    public function testEditFormShowsSavedValues(): void
    {
        $this->mockIdentity();

        // Save custom officials data
        $configData = [
            'configs' => [
                'officials' => [
                    'value' => 'Home Plate, First Base, Third Base',
                    'description' => 'Baseball officials',
                ],
            ],
        ];

        $this->post('/admin/sports/edit-configs/1', $configData);
        $this->assertRedirect('/admin/sports/configs/1');

        // Now load the EDIT form and verify it shows our saved values (not defaults)
        $this->get('/admin/sports/edit-configs/1');
        $this->assertResponseOk();
        $this->assertResponseContains('Home Plate, First Base, Third Base');
    }

    /**
     * Test that custom period names are saved and loaded correctly
     *
     * @return void
     */
    public function testCustomPeriodNames(): void
    {
        $this->mockIdentity();

        // Save custom period names (7 and 9 periods instead of default 2 and 4)
        $configData = [
            'configs' => [
                'period_name_7' => [
                    'value' => 'Inning',
                    'description' => 'Baseball innings for 7 periods',
                ],
                'period_name_9' => [
                    'value' => 'Inning',
                    'description' => 'Baseball innings for 9 periods',
                ],
            ],
        ];

        $this->post('/admin/sports/edit-configs/1', $configData);
        $this->assertRedirect('/admin/sports/configs/1');

        // Verify the custom period names are displayed in configs view
        $this->get('/admin/sports/configs/1');
        $this->assertResponseOk();
        $this->assertResponseContains('7'); // Should show 7 periods
        $this->assertResponseContains('9'); // Should show 9 periods

        // Now load the EDIT form and verify it shows our custom values (not defaults)
        $this->get('/admin/sports/edit-configs/1');
        $this->assertResponseOk();
        // Should NOT contain default period counts
        $this->assertResponseNotContains('configs[period_name_2][periods]');
        $this->assertResponseNotContains('configs[period_name_4][periods]');
        // Should contain our custom period counts
        $this->assertResponseContains('configs[period_name_7][periods]');
        $this->assertResponseContains('configs[period_name_9][periods]');
    }

    /**
     * Test that JavaScript-generated period names are processed correctly
     *
     * @return void
     */
    public function testJavaScriptGeneratedPeriodNames(): void
    {
        $this->mockIdentity();

        // Simulate what happens when JavaScript adds new period names
        // This matches the structure created by the addPeriodName() JavaScript function
        $configData = [
            'configs' => [
                'period_name_new_0' => [
                    'periods' => '7',
                    'value' => 'Inning',
                    'description' => 'Baseball innings for 7 periods',
                ],
                'period_name_new_1' => [
                    'periods' => '9',
                    'value' => 'Inning',
                    'description' => 'Baseball innings for 9 periods',
                ],
            ],
        ];

        $this->post('/admin/sports/edit-configs/1', $configData);
        $this->assertRedirect('/admin/sports/configs/1');

        // Verify the period names were saved with correct keys (period_name_7, period_name_9)
        $this->get('/admin/sports/configs/1');
        $this->assertResponseOk();
        $this->assertResponseContains('7'); // Should show 7 periods
        $this->assertResponseContains('9'); // Should show 9 periods

        // Should NOT show the temporary JavaScript keys
        $this->assertResponseNotContains('new_0');
        $this->assertResponseNotContains('new_1');
    }

    /**
     * Tests add config validates key.
     */
    public function testAddConfigValidatesKey(): void
    {
        $this->mockIdentity();
        $this->enableCsrfToken();
        $this->enableSecurityToken();

        $postData = [
            'config_key' => '',
            'config_value' => 'ShouldNotSave',
            'description' => 'Missing key test',
        ];

        $this->post('/admin/sports/add-config/1', $postData);
        $this->assertRedirect('/admin/sports/edit-configs/1');
        $this->assertFlashMessage('Configuration key is required.');
    }

    /**
     * Tests add config creates value.
     */
    public function testAddConfigCreatesValue(): void
    {
        $this->mockIdentity();
        $this->enableCsrfToken();
        $this->enableSecurityToken();

        $postData = [
            'config_key' => 'test_color_palette',
            'config_value' => 'Green,Blue',
            'description' => 'Palette for theme tests',
        ];

        $this->post('/admin/sports/add-config/1', $postData);
        $this->assertRedirect('/admin/sports/edit-configs/1');
        $this->assertFlashMessage('Configuration added successfully.');

        $this->get('/admin/sports/configs/1');
        $this->assertResponseContains('test_color_palette');
        $this->assertResponseContains('Green');
        $this->assertResponseContains('Blue');
    }

    /**
     * Tests delete config removes entry.
     */
    public function testDeleteConfigRemovesEntry(): void
    {
        $this->mockIdentity();
        $this->enableCsrfToken();
        $this->enableSecurityToken();

        $this->delete('/admin/sports/delete-config/1/officials');
        $this->assertRedirect('/admin/sports/edit-configs/1');
        $this->assertFlashMessage('Configuration deleted successfully.');

        $this->get('/admin/sports/configs/1');
        $this->assertResponseNotContains('Referee 1');
    }

    /**
     * Tests reset configs restores defaults.
     */
    public function testResetConfigsRestoresDefaults(): void
    {
        $this->mockIdentity();
        $this->enableCsrfToken();
        $this->enableSecurityToken();

        // Overwrite an existing config so we can verify the reset clears it.
        $this->post('/admin/sports/edit-configs/1', [
            'configs' => [
                'officials' => [
                    'value' => 'Custom Official',
                    'description' => 'Temporary override',
                ],
            ],
        ]);
        $this->assertRedirect('/admin/sports/configs/1');

        $this->post('/admin/sports/reset-configs/1');
        $this->assertRedirect('/admin/sports/edit-configs/1');
        $this->assertFlashMessage('Sport configurations have been reset to defaults.');

        $this->get('/admin/sports/configs/1');
        $this->assertResponseContains('Referee 1');
    }

    /**
     * Test admin pages include turbo-frame for SPA navigation.
     */
    public function testAdminPagesContainTurboFrame(): void
    {
        $this->mockIdentity();
        $this->get('/admin/sports');
        $this->assertResponseOk();
        $this->assertResponseContains('<turbo-frame id="admin-content"');

        $this->mockIdentity();
        $this->get('/admin/sports/view/1');
        $this->assertResponseOk();
        $this->assertResponseContains('<turbo-frame id="admin-content"');
    }
}
