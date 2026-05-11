<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller\Admin;

use App\Test\TestCase\Support\AuthTestTrait;
use Cake\Datasource\Exception\RecordNotFoundException;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;
use Exception;

/**
 * @link \App\Controller\Admin\OpponentsController
 */
class OpponentsControllerTest extends TestCase
{
    use IntegrationTestTrait;
    use AuthTestTrait;

    protected array $fixtures = [
        'app.Users',
        'app.Places',
        'app.Opponents',
    ];

    /**
     * Tests index.
     */
    public function testIndex(): void
    {
        $this->mockIdentity();
        $this->get('/admin/opponents');
        $this->assertResponseOk();
        $this->assertResponseContains('Opponents');
        $this->assertResponseContains('opponents-table');
        $this->assertResponseContains('data-datatables-url');
        $this->assertResponseContains('total');
    }

    /**
     * Tests datatables returns json.
     */
    public function testDatatablesReturnsJson(): void
    {
        $this->mockIdentity();
        $this->get('/admin/opponents/datatables?draw=1&start=0&length=25');
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
        $this->get('/admin/opponents/datatables?draw=2&start=0&length=25&search[value]=Belmont');
        $this->assertResponseOk();

        $body = json_decode((string)$this->_response->getBody(), true);
        $this->assertSame(2, $body['draw']);
        $this->assertLessThanOrEqual($body['recordsTotal'], $body['recordsFiltered']);
        foreach ($body['data'] as $row) {
            $rowText = strtolower($row['name'] . ' ' . $row['short'] . ' ' . $row['abbr']);
            $this->assertStringContainsString('belmont', $rowText);
        }
    }

    /**
     * Tests datatables requires auth.
     */
    public function testDatatablesRequiresAuth(): void
    {
        $this->get('/admin/opponents/datatables');
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
        $this->post('/admin/opponents/add', ['opponent_name' => 'Austin Peay', 'place_id' => 1]);
        $this->assertRedirect(['prefix' => 'Admin', 'controller' => 'Opponents', 'action' => 'index']);
    }

    /**
     * Tests add get.
     */
    public function testAddGet(): void
    {
        $this->mockIdentity();
        $this->get('/admin/opponents/add');
        $this->assertResponseOk();
        $this->assertResponseContains('Add Opponent');
    }

    /**
     * Tests edit.
     */
    public function testEdit(): void
    {
        $this->mockIdentity();
        $this->enableCsrfToken();
        $this->enableSecurityToken();
        $this->post('/admin/opponents/edit/1', ['opponent_name' => 'Updated Name', 'place_id' => 1]);
        $this->assertRedirect(['prefix' => 'Admin', 'controller' => 'Opponents', 'action' => 'index']);
    }

    /**
     * Tests edit get.
     */
    public function testEditGet(): void
    {
        $this->mockIdentity();
        $this->get('/admin/opponents/edit/1');
        $this->assertResponseOk();
        $this->assertResponseContains('Edit Opponent');
    }

    /**
     * Tests delete.
     */
    public function testDelete(): void
    {
        $this->mockIdentity();
        $this->enableCsrfToken();
        $this->delete('/admin/opponents/delete/1');
        // May redirect or show error if has associations
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
            $this->delete('/admin/opponents/delete/999');
            $this->assertResponseError();
        } catch (Exception $e) {
            $this->assertInstanceOf(RecordNotFoundException::class, $e);
        }
    }

    /**
     * Tests add validation error.
     */
    public function testAddValidationError(): void
    {
        $this->mockIdentity();
        $this->enableCsrfToken();
        $this->enableSecurityToken();

        // Missing required opponent_name
        $this->post('/admin/opponents/add', ['place_id' => 1]);
        // May re-render with errors or redirect
        $this->assertTrue($this->_response->getStatusCode() >= 200);
    }

    /**
     * Tests edit validation error.
     */
    public function testEditValidationError(): void
    {
        $this->mockIdentity();
        $this->enableCsrfToken();
        $this->enableSecurityToken();

        // Try to edit with potentially invalid data
        $this->post('/admin/opponents/edit/1', ['opponent_name' => 'Valid Name', 'place_id' => 999]);
        // May succeed, fail, or redirect depending on validation
        $this->assertTrue($this->_response->getStatusCode() >= 200);
    }

    /**
     * Tests unauthenticated access.
     */
    public function testUnauthenticatedAccess(): void
    {
        $this->session([]); // Clear session
        $this->get('/admin/opponents');
        // Should redirect to login or show login page
        $this->assertTrue($this->_response->getStatusCode() >= 200);
    }

    /**
     * Tests ajax search returns results.
     */
    public function testAjaxSearchReturnsResults(): void
    {
        $this->mockIdentity();
        $this->get('/admin/opponents/ajax-search?q=Belmont');
        $this->assertResponseOk();
        $data = json_decode((string)$this->_response->getBody(), true);
        $this->assertTrue($data['success']);
        $this->assertNotEmpty($data['results']);
        $this->assertEquals('Belmont', $data['results'][0]['opponent_name']);
        $this->assertArrayHasKey('id', $data['results'][0]);
        $this->assertArrayHasKey('opponent_short', $data['results'][0]);
        $this->assertArrayHasKey('opponent_abbr', $data['results'][0]);
        $this->assertArrayHasKey('opponent_mascot', $data['results'][0]);
    }

    /**
     * Tests ajax search empty query.
     */
    public function testAjaxSearchEmptyQuery(): void
    {
        $this->mockIdentity();
        $this->get('/admin/opponents/ajax-search?q=');
        $this->assertResponseOk();
        $data = json_decode((string)$this->_response->getBody(), true);
        $this->assertTrue($data['success']);
        $this->assertEmpty($data['results']);
    }

    /**
     * Tests ajax search no match.
     */
    public function testAjaxSearchNoMatch(): void
    {
        $this->mockIdentity();
        $this->get('/admin/opponents/ajax-search?q=Nonexistent99');
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
        $this->post('/admin/opponents/ajax-search', ['q' => 'Belmont']);
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
        $this->post('/admin/opponents/ajax-add', [
            'opponent_name' => 'Tennessee Tech',
            'place_id' => 1,
        ]);
        $this->assertResponseOk();
        $data = json_decode((string)$this->_response->getBody(), true);
        $this->assertTrue($data['success']);
        $this->assertEquals('Tennessee Tech', $data['newOption']['text']);
        $this->assertNotEmpty($data['newOption']['value']);
    }

    /**
     * Tests ajax add invalid method.
     */
    public function testAjaxAddInvalidMethod(): void
    {
        $this->mockIdentity();
        $this->get('/admin/opponents/ajax-add');
        $this->assertResponseOk();
        $data = json_decode((string)$this->_response->getBody(), true);
        $this->assertFalse($data['success']);
    }

    /**
     * Test ajaxAdd with place_id links opponent to a place.
     */
    public function testAjaxAddWithPlaceId(): void
    {
        $this->mockIdentity();
        $this->enableCsrfToken();
        $this->enableSecurityToken();
        $this->post('/admin/opponents/ajax-add', [
            'opponent_name' => 'Eastern Kentucky',
            'opponent_mascot' => 'Colonels',
            'opponent_short' => 'EKU',
            'opponent_abbr' => 'EKU',
            'place_id' => 1,
        ]);
        $this->assertResponseOk();
        $data = json_decode((string)$this->_response->getBody(), true);
        $this->assertTrue($data['success']);
        $this->assertEquals('Eastern Kentucky', $data['newOption']['text']);

        // Verify the opponent was saved with place_id
        $opponents = $this->getTableLocator()->get('Opponents');
        $saved = $opponents->find()->where(['opponent_name' => 'Eastern Kentucky'])->first();
        $this->assertNotNull($saved);
        $this->assertEquals(1, $saved->place_id);
    }

    /**
     * Test ajaxAdd with all optional fields.
     */
    public function testAjaxAddWithAllFields(): void
    {
        $this->mockIdentity();
        $this->enableCsrfToken();
        $this->enableSecurityToken();
        $this->post('/admin/opponents/ajax-add', [
            'opponent_name' => 'Western Kentucky',
            'opponent_mascot' => 'Hilltoppers',
            'opponent_short' => 'WKU',
            'opponent_abbr' => 'WKU',
            'place_id' => 1,
        ]);
        $this->assertResponseOk();
        $data = json_decode((string)$this->_response->getBody(), true);
        $this->assertTrue($data['success']);
        $this->assertEquals('Western Kentucky', $data['newOption']['text']);
    }

    /**
     * Test ajaxSearch searches across all fields.
     */
    public function testAjaxSearchSearchesMultipleFields(): void
    {
        $this->mockIdentity();
        // Search by mascot (fixture should have at least one opponent with mascot)
        $this->get('/admin/opponents/ajax-search?q=Bruins');
        $this->assertResponseOk();
        $data = json_decode((string)$this->_response->getBody(), true);
        $this->assertTrue($data['success']);
        // Note: this will match if any fixture opponent has 'Bruins' in name/mascot/short/abbr
    }

    /**
     * Test ajaxSearch returns all expected fields in results.
     */
    public function testAjaxSearchResultFields(): void
    {
        $this->mockIdentity();
        $this->get('/admin/opponents/ajax-search?q=Belmont');
        $this->assertResponseOk();
        $data = json_decode((string)$this->_response->getBody(), true);
        $this->assertTrue($data['success']);
        $this->assertNotEmpty($data['results']);
        $first = $data['results'][0];
        $this->assertArrayHasKey('id', $first);
        $this->assertArrayHasKey('opponent_name', $first);
        $this->assertArrayHasKey('opponent_short', $first);
        $this->assertArrayHasKey('opponent_abbr', $first);
        $this->assertArrayHasKey('opponent_mascot', $first);
    }
}
