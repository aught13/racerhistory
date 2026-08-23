<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller\Admin;

use App\Test\TestCase\Support\AuthTestTrait;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

class SeasonsRbacTest extends TestCase
{
    use IntegrationTestTrait;
    use AuthTestTrait;

    protected array $fixtures = [
        'app.Seasons',
        'app.Roles',
        'app.Permissions',
        'app.Users',
    ];

    /**
     * Non-admin users without delete permission are rejected before controller delete logic runs.
     */
    public function testDeleteForbiddenForBlogger(): void
    {
        $this->mockIdentity(['role' => 'blogger', 'role_id' => 2, 'id' => 3]);
        $this->configRequest(['attributes' => ['identity' => ['id' => 3, 'role' => 'blogger', 'role_id' => 2, 'status' => 'active', 'active' => true]]]);
        $this->enableCsrfToken();
        $this->enableSecurityToken();

        $this->post('/admin/seasons/delete/1');
        $this->assertRedirectContains('/users/login');

        $seasons = $this->getTableLocator()->get('Seasons');
        $this->assertNotNull($seasons->get(1));
    }

    /**
     * Non-admin users without delete permission cannot execute bulk delete actions.
     */
    public function testBulkDeleteForbiddenForBlogger(): void
    {
        $this->mockIdentity(['role' => 'blogger', 'role_id' => 2, 'id' => 3]);
        $this->configRequest(['attributes' => ['identity' => ['id' => 3, 'role' => 'blogger', 'role_id' => 2, 'status' => 'active', 'active' => true]]]);
        $this->enableCsrfToken();
        $this->enableSecurityToken();

        $this->post('/admin/seasons/bulk', ['bulk_action' => 'delete', 'season_ids' => [1]]);
        $this->assertRedirectContains('/users/login');

        $seasons = $this->getTableLocator()->get('Seasons');
        $this->assertNotNull($seasons->get(1));
    }
}
