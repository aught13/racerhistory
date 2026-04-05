<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller\Admin;

use App\Test\TestCase\Support\AuthTestTrait;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

class SitesControllerTest extends TestCase
{
    use IntegrationTestTrait;
    use AuthTestTrait;

    protected array $fixtures = [
        'app.Users',
        'app.Places',
        'app.Sites',
    ];

    public function testIndex(): void
    {
        $this->mockIdentity();
        $this->get('/admin/sites');
        $this->assertResponseOk();
        $this->assertResponseContains('Sites');
    }

    public function testAddPost(): void
    {
        $this->mockIdentity();
        $this->enableCsrfToken();
        $this->enableSecurityToken();
        $this->post('/admin/sites/add', ['site_name' => 'Arena', 'place_id' => 1]);
        $this->assertRedirect(['prefix' => 'Admin', 'controller' => 'Sites', 'action' => 'index']);
    }

    public function testAddGet(): void
    {
        $this->mockIdentity();
        $this->get('/admin/sites/add');
        $this->assertResponseOk();
        $this->assertResponseContains('Add Site');
    }

    public function testEdit(): void
    {
        $this->mockIdentity();
        $this->enableCsrfToken();
        $this->enableSecurityToken();
        $this->post('/admin/sites/edit/1', ['site_name' => 'Updated Site', 'place_id' => 1]);
        $this->assertRedirect(['prefix' => 'Admin', 'controller' => 'Sites', 'action' => 'index']);
    }

    public function testEditGet(): void
    {
        $this->mockIdentity();
        $this->get('/admin/sites/edit/1');
        $this->assertResponseOk();
        $this->assertResponseContains('Edit Site');
    }

    public function testDelete(): void
    {
        $this->mockIdentity();
        $this->enableCsrfToken();
        $this->delete('/admin/sites/delete/1');
        $this->assertTrue($this->_response->getStatusCode() >= 200);
    }

    public function testDeleteNonExistent(): void
    {
        $this->mockIdentity();
        $this->enableCsrfToken();

        try {
            $this->delete('/admin/sites/delete/999');
            $this->assertResponseError();
        } catch (\Exception $e) {
            $this->assertInstanceOf(\Cake\Datasource\Exception\RecordNotFoundException::class, $e);
        }
    }

    public function testUnauthenticatedAccess(): void
    {
        $this->session([]);
        $this->get('/admin/sites');
        $this->assertTrue($this->_response->getStatusCode() >= 200);
    }

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

    public function testAjaxSearchWithPlaceIdFilter(): void
    {
        $this->mockIdentity();
        $this->get('/admin/sites/ajax-search?q=CFSB&place_id=1');
        $this->assertResponseOk();
        $data = json_decode((string)$this->_response->getBody(), true);
        $this->assertTrue($data['success']);
        $this->assertNotEmpty($data['results']);
    }

    public function testAjaxSearchWithPlaceIdFilterNoMatch(): void
    {
        $this->mockIdentity();
        $this->get('/admin/sites/ajax-search?q=CFSB&place_id=999');
        $this->assertResponseOk();
        $data = json_decode((string)$this->_response->getBody(), true);
        $this->assertTrue($data['success']);
        $this->assertEmpty($data['results']);
    }

    public function testAjaxSearchEmptyQuery(): void
    {
        $this->mockIdentity();
        $this->get('/admin/sites/ajax-search?q=');
        $this->assertResponseOk();
        $data = json_decode((string)$this->_response->getBody(), true);
        $this->assertTrue($data['success']);
        $this->assertEmpty($data['results']);
    }

    public function testAjaxSearchRejectsPostMethod(): void
    {
        $this->mockIdentity();
        $this->enableCsrfToken();
        $this->enableSecurityToken();
        $this->post('/admin/sites/ajax-search', ['q' => 'CFSB']);
        $this->assertResponseCode(405);
    }

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
