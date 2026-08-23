<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller\Admin;

use App\Test\TestCase\Support\AuthTestTrait;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

class RolesControllerTest extends TestCase
{
    use IntegrationTestTrait;
    use AuthTestTrait;

    protected array $fixtures = [
        'app.Users',
        'app.Roles',
        'app.Permissions',
    ];

    /**
     * Ensure the matrix editor renders the expected BlogPosts custom rule controls.
     */
    public function testEditMatrixGetRendersBootstrapTable(): void
    {
        $this->mockIdentity();
        $this->get('/admin/roles/edit/2');

        $this->assertResponseOk();
        $this->assertResponseContains('Edit Blogger Permissions');
        $this->assertResponseContains('Blog Posts');
        $this->assertResponseContains('Can Pin Posts');
        $this->assertResponseContains('Can Modify Pin Rank / Expiration');
        $this->assertResponseContains('permissions[BlogPosts][can_read]" value="own"');
        $this->assertResponseContains('permissions[Images][can_read]" value="own"');
        $this->assertResponseContains('permissions[Users][can_read]" value="own"');
        $this->assertResponseNotContains('permissions[Games][can_read]" value="own"');
        $this->assertResponseNotContains('permissions[Roles][can_update]" value="own"');
        $this->assertResponseContains('permissions[Games][can_read]" value="all"');
        $this->assertResponseContains('permissions[Games][can_read]" value="none"');
    }

    /**
     * Ensure submitting the matrix persists CRUD and custom rule changes.
     */
    public function testEditMatrixPostPersistsPermissionChanges(): void
    {
        $this->mockIdentity();
        $this->enableCsrfToken();
        $this->enableSecurityToken();

        $this->post('/admin/roles/edit/2', [
            'permissions' => [
                'BlogPosts' => [
                    'can_create' => '1',
                    'can_read' => 'own',
                    'can_update' => 'own',
                    'can_delete' => 'none',
                    'custom_rules' => [
                        'can_pin_posts' => '0',
                        'can_manage_pin_settings' => '0',
                    ],
                ],
                'Images' => [
                    'can_create' => '1',
                    'can_read' => 'all',
                    'can_update' => 'all',
                    'can_delete' => 'none',
                ],
                'Games' => [
                    'can_create' => '1',
                    'can_read' => 'own',
                    'can_update' => 'own',
                    'can_delete' => 'own',
                ],
            ],
        ]);

        $this->assertRedirect(['prefix' => 'Admin', 'controller' => 'Roles', 'action' => 'edit', 2]);
        $this->assertSession('Role permissions have been updated.', 'Flash.flash.0.message');

        $permissions = $this->getTableLocator()->get('Permissions');
        $blogPermission = $permissions->find()->where(['role_id' => 2, 'model_name' => 'BlogPosts'])->firstOrFail();
        $imagePermission = $permissions->find()->where(['role_id' => 2, 'model_name' => 'Images'])->firstOrFail();
        $gamesPermission = $permissions->find()->where(['role_id' => 2, 'model_name' => 'Games'])->firstOrFail();
        $customRules = $blogPermission->custom_rules;
        if (is_string($customRules)) {
            $decoded = json_decode($customRules, true);
            $customRules = is_array($decoded) ? $decoded : [];
        }

        $this->assertSame('none', $blogPermission->can_delete);
        $this->assertSame('all', $imagePermission->can_update);
        $this->assertSame('none', $gamesPermission->can_read);
        $this->assertSame('none', $gamesPermission->can_update);
        $this->assertSame('none', $gamesPermission->can_delete);
        $this->assertSame(['can_pin_posts' => false, 'can_manage_pin_settings' => false], (array)$customRules);
    }
}
