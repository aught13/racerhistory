<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller\Admin;

use App\Test\TestCase\Support\AuthTestTrait;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

class SiteOptionsControllerExtraTest extends TestCase
{
    use IntegrationTestTrait;
    use AuthTestTrait;

    protected array $fixtures = [
        'app.Users',
        'app.SiteOptions',
        'app.Sports',
    ];

    /**
     * Test setup: enable CSRF and security tokens.
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
     * Test that sports configs page renders for a given sport id.
     *
     * @return void
     */
    public function testSportsConfigsRendersForId(): void
    {
        $this->mockIdentity();

        $this->get('/admin/site-options/sports-configs/1');

        $this->assertResponseOk();
        $this->assertResponseContains('Sport Configurations');
        $this->assertResponseContains('Officials');
    }

    /**
     * Test that posting edits saves configs and redirects appropriately.
     *
     * @return void
     */
    public function testEditSportConfigsPostSavesAndRedirects(): void
    {
        $this->mockIdentity();
        $this->enableRetainFlashMessages();

        $this->post('/admin/site-options/edit-sport-configs/1', [
            'configs' => [
                'officials' => [
                    'value' => 'Ref1, Ref2',
                    'description' => 'Updated',
                ],
            ],
        ]);

        $this->assertRedirectContains('/admin/site-options/sports-configs/');
        $this->assertFlashMessage('Sport configurations have been updated.');
    }

    /**
     * Test add sport config validation errors and successful addition.
     *
     * @return void
     */
    public function testAddSportConfigValidationAndSuccess(): void
    {
        $this->mockIdentity();
        $this->enableRetainFlashMessages();

        // validation error when key missing
        $this->post('/admin/site-options/add-sport-config/1', [
            'config_key' => '',
            'config_value' => 'ignored',
            'description' => 'missing key',
        ]);

        $this->assertRedirectContains('/admin/site-options/edit-sport-configs/');
        $this->assertFlashMessage('Configuration key is required.');

        // success when key provided
        $this->post('/admin/site-options/add-sport-config/1', [
            'config_key' => 'officials',
            'config_value' => 'New Official',
            'description' => '',
        ]);

        $this->assertRedirectContains('/admin/site-options/edit-sport-configs/');
        $this->assertFlashMessage('Configuration added successfully.');
    }

    /**
     * Test deleting a sport config with empty key shows an error.
     *
     * @return void
     */
    public function testDeleteSportConfigEmptyKeyShowsError(): void
    {
        $this->mockIdentity();
        $this->enableRetainFlashMessages();

        $this->delete('/admin/site-options/delete-sport-config/1');

        $this->assertRedirectContains('/admin/site-options/edit-sport-configs/');
        $this->assertFlashMessage('Unable to delete configuration.');
    }

    /**
     * Test resetting sport configs posts and redirects to editor.
     *
     * @return void
     */
    public function testResetSportConfigsPostsAndRedirects(): void
    {
        $this->mockIdentity();
        $this->enableRetainFlashMessages();

        $this->post('/admin/site-options/reset-sport-configs/1');

        $this->assertRedirectContains('/admin/site-options/edit-sport-configs/');
        $this->assertFlashMessage('Sport configurations have been reset to defaults.');
    }

    /**
     * Invalid sport refs should redirect to edit with a helpful flash.
     */
    public function testSportsConfigsInvalidSportRefRedirectsToEdit(): void
    {
        $this->mockIdentity();
        $this->enableRetainFlashMessages();

        $this->get('/admin/site-options/sports-configs/not-a-sport');

        $this->assertRedirect('/admin/site-options/edit');
        $this->assertFlashMessage('Sport not found.');
    }

    /**
     * Role-privilege editor should render and persist posted matrix changes.
     */
    public function testEditRolePrivilegesGetAndPost(): void
    {
        $this->mockIdentity();

        $this->get('/admin/site-options/edit-role-privileges');
        $this->assertResponseOk();
        $this->assertResponseContains('Role Privileges');

        $this->enableRetainFlashMessages();
        $this->post('/admin/site-options/edit-role-privileges', [
            'privileges' => [
                'admin' => 'bypass_all, manage_users',
                'editor' => ['view_any', 'edit_any'],
                'author' => 'view_own',
            ],
            'delete_role' => [
                'author' => '1',
            ],
            'new_role' => 'reviewer',
            'new_privileges' => 'view_any,comment_moderate',
        ]);

        $this->assertRedirect('/admin/site-options/edit-role-privileges');
        $this->assertFlashMessage('Role privileges have been updated.');

        $this->get('/admin/site-options/edit-role-privileges');
        $this->assertResponseOk();
        $this->assertResponseContains('Role Privileges');
    }
}
