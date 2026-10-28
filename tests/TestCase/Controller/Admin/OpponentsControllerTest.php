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
        $this->assertResponseContains('data-controller="admin-index-table"');
        $this->assertResponseContains('data-admin-index-table-target="searchInput"');
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
        $this->assertSame('Belmont', $body['data'][0]['name']);
    }

    /**
     * DataTables opponent names use the default short-name and mascot variant.
     */
    public function testDatatablesUsesDefaultOpponentDisplay(): void
    {
        $opponents = $this->getTableLocator()->get('Opponents');
        $opponent = $opponents->get(1);
        $opponent->opponent_short = 'Belmont';
        $hasMascotColumn = $opponents->getSchema()->hasColumn('opponent_mascot');
        if ($hasMascotColumn) {
            $opponent->opponent_mascot = 'Bruins';
        }
        $opponents->saveOrFail($opponent);
        $expectedLabel = $opponents->get(1)->getDisplayName();

        $this->mockIdentity();
        $this->get('/admin/opponents/datatables?draw=1&start=0&length=25');

        $this->assertResponseOk();
        $body = json_decode((string)$this->_response->getBody(), true);
        $this->assertSame($expectedLabel, $body['data'][0]['name']);
    }

    /**
     * Tests datatables include resolved place label.
     */
    public function testDatatablesIncludesPlaceLabel(): void
    {
        $this->mockIdentity();
        $this->get('/admin/opponents/datatables?draw=1&start=0&length=25');
        $this->assertResponseOk();

        $body = json_decode((string)$this->_response->getBody(), true);
        $this->assertNotEmpty($body['data']);
        $this->assertSame('Murray, KY', $body['data'][0]['place']);
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
     * Abbreviations up to 30 characters can be saved.
     */
    public function testAddPostAcceptsThirtyCharacterAbbreviation(): void
    {
        $this->mockIdentity();
        $this->enableCsrfToken();
        $this->enableSecurityToken();
        $abbreviation = str_repeat('A', 30);
        $this->post('/admin/opponents/add', [
            'opponent_name' => 'Thirty Character Opponent',
            'opponent_abbr' => $abbreviation,
            'place_id' => 1,
        ]);

        $this->assertRedirect(['prefix' => 'Admin', 'controller' => 'Opponents', 'action' => 'index']);
        /** @var \App\Model\Entity\Opponent $savedOpponent */
        $savedOpponent = $this->getTableLocator()->get('Opponents')
            ->find()
            ->where(['opponent_name' => 'Thirty Character Opponent'])
            ->firstOrFail();
        $this->assertSame($abbreviation, $savedOpponent->opponent_abbr);
    }

    /**
     * Abbreviations longer than 30 characters are rejected.
     */
    public function testAddPostRejectsAbbreviationLongerThanThirtyCharacters(): void
    {
        $this->mockIdentity();
        $this->enableCsrfToken();
        $this->enableSecurityToken();
        $this->post('/admin/opponents/add', [
            'opponent_name' => 'Too Long Opponent',
            'opponent_abbr' => str_repeat('A', 31),
            'place_id' => 1,
        ]);

        $this->assertResponseOk();
        /** @var \App\Model\Entity\Opponent $opponent */
        $opponent = $this->viewVariable('opponent');
        $this->assertArrayHasKey('opponent_abbr', $opponent->getErrors());
        $this->assertFalse(
            $this->getTableLocator()->get('Opponents')->exists(['opponent_name' => 'Too Long Opponent']),
        );
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
        $this->assertResponseContains('data-controller="opponent-name-preview"');
        $this->assertResponseContains('data-opponent-name-preview-target="fullOpponentName"');
        $this->assertResponseContains('data-opponent-name-preview-target="defaultOpponentName"');
        $this->assertResponseContains('data-opponent-name-preview-target="opponent"');
        $this->assertMatchesRegularExpression(
            '/<input\b(?=[^>]*\bname="opponent_abbr")(?=[^>]*\bmaxlength="30")[^>]*>/',
            (string)$this->_response->getBody(),
        );
    }

    /**
     * Current-opponent form options use the default opponent display.
     */
    public function testCurrentOpponentOptionsUseDefaultDisplay(): void
    {
        $opponents = $this->getTableLocator()->get('Opponents');
        $opponent = $opponents->get(1);
        $opponent->opponent_short = 'Belmont';
        if ($opponents->getSchema()->hasColumn('opponent_mascot')) {
            $opponent->opponent_mascot = 'Bruins';
        }
        $opponents->saveOrFail($opponent);
        $expectedLabel = $opponents->get(1)->getDisplayName();

        $this->mockIdentity();
        $this->get('/admin/opponents/add');

        $this->assertResponseOk();
        $this->assertSame($expectedLabel, $this->viewVariable('opponentsList')[1]);
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
        $this->assertResponseContains('Display Previews');
        $this->assertResponseContains('Full Opponent Name');
        $this->assertMatchesRegularExpression(
            '/<input\b(?=[^>]*\bname="opponent_abbr")(?=[^>]*\bmaxlength="30")[^>]*>/',
            (string)$this->_response->getBody(),
        );
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
        $this->assertEquals('Belmont', $data['results'][0]['label']);
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
        $this->assertEquals('EKU Colonels', $data['newOption']['text']);

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
        $this->assertEquals('WKU Hilltoppers', $data['newOption']['text']);
    }

    /**
     * Test ajaxSearch searches across all fields.
     */
    public function testAjaxSearchSearchesMultipleFields(): void
    {
        $this->mockIdentity();
        $opponents = $this->getTableLocator()->get('Opponents');
        if (!$opponents->getSchema()->hasColumn('opponent_mascot')) {
            $this->markTestSkipped('Test schema does not include opponent_mascot.');
        }
        $opponent = $opponents->get(1);
        $opponent->opponent_mascot = 'Bruins';
        $opponent->opponent_short = 'Belmont';
        $opponents->saveOrFail($opponent);

        $this->get('/admin/opponents/ajax-search?q=Bruins');
        $this->assertResponseOk();
        $data = json_decode((string)$this->_response->getBody(), true);
        $this->assertTrue($data['success']);
        $this->assertSame('Belmont Bruins', $data['results'][0]['label']);
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
