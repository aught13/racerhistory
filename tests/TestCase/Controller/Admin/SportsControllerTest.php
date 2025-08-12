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
        $this->assertResponseContains('Sport Details');
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
     * Test bulk action dispatcher
     *
     * @return void
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
}
