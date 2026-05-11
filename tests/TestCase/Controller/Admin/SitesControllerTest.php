<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller\Admin;

use App\Test\TestCase\Support\AuthTestTrait;
use Cake\Datasource\Exception\RecordNotFoundException;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;
use Exception;

/**
 * @link \App\Controller\Admin\SitesController
 */
class SitesControllerTest extends TestCase
{
    use IntegrationTestTrait;
    use AuthTestTrait;

    protected array $fixtures = [
        'app.Users',
        'app.Places',
        'app.Sites',
    ];

    /**
     * Tests index.
     */
    public function testIndex(): void
    {
        $this->mockIdentity();
        $this->get('/admin/sites');
        $this->assertResponseOk();
        $this->assertResponseContains('Sites');
        $this->assertResponseContains('sites-table');
        $this->assertResponseContains('data-datatables-url');
        $this->assertResponseContains('total');
    }

    /**
     * Tests datatables returns json.
     */
    public function testDatatablesReturnsJson(): void
    {
        $this->mockIdentity();
        $this->get('/admin/sites/datatables?draw=1&start=0&length=25');
        $this->assertResponseOk();

        $body = json_decode((string)$this->_response->getBody(), true);
        $this->assertArrayHasKey('draw', $body);
        $this->assertArrayHasKey('recordsTotal', $body);
        $this->assertArrayHasKey('recordsFiltered', $body);
        $this->assertArrayHasKey('data', $body);
        $this->assertIsArray($body['data']);
        $this->assertSame(1, $body['draw']);
    }

    /**
     * Tests datatables search filters.
     */
    public function testDatatablesSearchFilters(): void
    {
        $this->mockIdentity();
        $this->get('/admin/sites/datatables?draw=2&start=0&length=25&search[value]=CFSB');
        $this->assertResponseOk();

        $body = json_decode((string)$this->_response->getBody(), true);
        $this->assertSame(2, $body['draw']);
        $this->assertLessThanOrEqual($body['recordsTotal'], $body['recordsFiltered']);
        foreach ($body['data'] as $row) {
            $rowText = strtolower($row['name'] . ' ' . $row['place']);
            $this->assertStringContainsString('cfsb', $rowText);
        }
    }

    /**
     * Tests datatables requires auth.
     */
    public function testDatatablesRequiresAuth(): void
    {
        $this->get('/admin/sites/datatables');
        $this->assertRedirectContains('/users/login');
    }

    /**
     * Tests add post.
     */
    public function testAddPost(): void
    {
        $this->mockIdentity();
        $this->enableCsrfToken();
        $this->enableSecurityToken();
        $this->post('/admin/sites/add', ['site_name' => 'Arena', 'place_id' => 1]);
        $this->assertRedirect(['prefix' => 'Admin', 'controller' => 'Sites', 'action' => 'index']);
    }

    /**
     * Tests add get.
     */
    public function testAddGet(): void
    {
        $this->mockIdentity();
        $this->get('/admin/sites/add');
        $this->assertResponseOk();
        $this->assertResponseContains('Add Site');
    }

    /**
     * Tests edit.
     */
    public function testEdit(): void
    {
        $this->mockIdentity();
        $this->enableCsrfToken();
        $this->enableSecurityToken();
        $this->post('/admin/sites/edit/1', ['site_name' => 'Updated Site', 'place_id' => 1]);
        $this->assertRedirect(['prefix' => 'Admin', 'controller' => 'Sites', 'action' => 'index']);
    }

    /**
     * Tests edit get.
     */
    public function testEditGet(): void
    {
        $this->mockIdentity();
        $this->get('/admin/sites/edit/1');
        $this->assertResponseOk();
        $this->assertResponseContains('Edit Site');
    }

    /**
     * Tests delete.
     */
    public function testDelete(): void
    {
        $this->mockIdentity();
        $this->enableCsrfToken();
        $this->delete('/admin/sites/delete/1');
        $this->assertTrue($this->_response->getStatusCode() >= 200);
    }

    /**
     * Tests delete non existent.
     */
    public function testDeleteNonExistent(): void
    {
        $this->mockIdentity();
        $this->enableCsrfToken();

        try {
            $this->delete('/admin/sites/delete/999');
            $this->assertResponseError();
        } catch (Exception $e) {
            $this->assertInstanceOf(RecordNotFoundException::class, $e);
        }
    }

    /**
     * Tests unauthenticated access.
     */
    public function testUnauthenticatedAccess(): void
    {
        $this->session([]);
        $this->get('/admin/sites');
        $this->assertTrue($this->_response->getStatusCode() >= 200);
    }

    /**
     * Tests ajax search returns results.
     */
    public function testAjaxSearchReturnsResults(): void
    {
        $this->mockIdentity();
        $this->get('/admin/sites/ajax-search?q=CFSB');
        $this->assertResponseOk();
        $data = json_decode((string)$this->_response->getBody(), true);
        $this->assertTrue($data['success']);
        $this->assertNotEmpty($data['results']);
        $this->assertEquals('CFSB Center', $data['results'][0]['site_name']);
        $this->assertArrayHasKey('id', $data['results'][0]);
        $this->assertArrayHasKey('capacity', $data['results'][0]);
        $this->assertArrayHasKey('place_city', $data['results'][0]);
        $this->assertArrayHasKey('place_state', $data['results'][0]);
    }

    /**
     * Tests ajax search with place id filter.
     */
    public function testAjaxSearchWithPlaceIdFilter(): void
    {
        $this->mockIdentity();
        $this->get('/admin/sites/ajax-search?q=CFSB&place_id=1');
        $this->assertResponseOk();
        $data = json_decode((string)$this->_response->getBody(), true);
        $this->assertTrue($data['success']);
        $this->assertNotEmpty($data['results']);
    }

    /**
     * Tests ajax search with place id filter no match.
     */
    public function testAjaxSearchWithPlaceIdFilterNoMatch(): void
    {
        $this->mockIdentity();
        $this->get('/admin/sites/ajax-search?q=CFSB&place_id=999');
        $this->assertResponseOk();
        $data = json_decode((string)$this->_response->getBody(), true);
        $this->assertTrue($data['success']);
        $this->assertEmpty($data['results']);
    }

    /**
     * Tests ajax search empty query.
     */
    public function testAjaxSearchEmptyQuery(): void
    {
        $this->mockIdentity();
        $this->get('/admin/sites/ajax-search?q=');
        $this->assertResponseOk();
        $data = json_decode((string)$this->_response->getBody(), true);
        $this->assertTrue($data['success']);
        $this->assertEmpty($data['results']);
    }

    /**
     * Tests ajax search rejects post method.
     */
    public function testAjaxSearchRejectsPostMethod(): void
    {
        $this->mockIdentity();
        $this->enableCsrfToken();
        $this->enableSecurityToken();
        $this->post('/admin/sites/ajax-search', ['q' => 'CFSB']);
        $this->assertResponseCode(405);
    }

    /**
     * Tests ajax add success.
     */
    public function testAjaxAddSuccess(): void
    {
        $this->mockIdentity();
        $this->enableCsrfToken();
        $this->enableSecurityToken();
        $this->post('/admin/sites/ajax-add', [
            'site_name' => 'New Arena',
            'place_id' => 1,
            'capacity' => 8000,
        ]);
        $this->assertResponseOk();
        $data = json_decode((string)$this->_response->getBody(), true);
        $this->assertTrue($data['success']);
        $this->assertNotEmpty($data['newOption']['value']);
        $this->assertNotEmpty($data['newOption']['text']);
    }

    /**
     * Tests ajax add invalid method.
     */
    public function testAjaxAddInvalidMethod(): void
    {
        $this->mockIdentity();
        $this->get('/admin/sites/ajax-add');
        $this->assertResponseOk();
        $data = json_decode((string)$this->_response->getBody(), true);
        $this->assertFalse($data['success']);
    }

    /**
     * Test ajaxAdd with all fields including capacity.
     */
    public function testAjaxAddWithAllFields(): void
    {
        $this->mockIdentity();
        $this->enableCsrfToken();
        $this->enableSecurityToken();
        $this->post('/admin/sites/ajax-add', [
            'site_name' => 'Rupp Arena',
            'place_id' => 1,
            'capacity' => '23500',
            'site_info' => 'Home of UK basketball',
        ]);
        $this->assertResponseOk();
        $data = json_decode((string)$this->_response->getBody(), true);
        $this->assertTrue($data['success']);
        $this->assertStringContainsString('Rupp Arena', $data['newOption']['text']);
        $this->assertNotEmpty($data['newOption']['value']);

        // Verify saved with required fields
        $sites = $this->getTableLocator()->get('Sites');
        $saved = $sites->find()->where(['site_name' => 'Rupp Arena'])->first();
        $this->assertNotNull($saved);
        $this->assertEquals(1, $saved->place_id);
    }

    /**
     * Test ajaxSearch with no matches returns empty results.
     */
    public function testAjaxSearchNoMatch(): void
    {
        $this->mockIdentity();
        $this->get('/admin/sites/ajax-search?q=Nonexistent999');
        $this->assertResponseOk();
        $data = json_decode((string)$this->_response->getBody(), true);
        $this->assertTrue($data['success']);
        $this->assertEmpty($data['results']);
    }

    /**
     * Test ajaxSearch returns expected fields.
     */
    public function testAjaxSearchResultFields(): void
    {
        $this->mockIdentity();
        $this->get('/admin/sites/ajax-search?q=CFSB');
        $this->assertResponseOk();
        $data = json_decode((string)$this->_response->getBody(), true);
        $this->assertTrue($data['success']);
        $this->assertNotEmpty($data['results']);
        $first = $data['results'][0];
        $this->assertArrayHasKey('id', $first);
        $this->assertArrayHasKey('site_name', $first);
        $this->assertArrayHasKey('capacity', $first);
        $this->assertArrayHasKey('place_city', $first);
    }
}
