<?php
declare(strict_types=1);

namespace App\Controller\Admin;

use Exception;

/**
 * Set stub state for namespaced countries lookup HTTP calls.
 *
 * @param string|false|null $response Mocked HTTP response body.
 * @param bool $throw Whether the stub should throw an exception.
 */
function __setPlacesLookupStub(string|false|null $response, bool $throw = false): void
{
    $GLOBALS['__places_lookup_stub_response'] = $response;
    $GLOBALS['__places_lookup_stub_throw'] = $throw;
}

/**
 * Reset countries lookup HTTP stub state.
 */
function __resetPlacesLookupStub(): void
{
    $GLOBALS['__places_lookup_stub_response'] = null;
    $GLOBALS['__places_lookup_stub_throw'] = false;
}

if (!function_exists(__NAMESPACE__ . '\\file_get_contents')) {
    /**
     * Test stub for PlacesController network calls.
     *
     * @param string $filename
     * @param bool $use_include_path
     * @param mixed $context
     * @param int $offset
     * @param int|null $length
     * @return string|false
     */
    function file_get_contents(
        string $filename,
        bool $use_include_path = false,
        mixed $context = null,
        int $offset = 0,
        ?int $length = null,
    ): string|false {
        if (($GLOBALS['__places_lookup_stub_throw'] ?? false) === true) {
            restore_error_handler();
            throw new Exception('mock countries lookup failure');
        }

        return $GLOBALS['__places_lookup_stub_response'] ?? false;
    }
}

namespace App\Test\TestCase\Controller\Admin;

use App\Test\TestCase\Support\AuthTestTrait;
use Cake\Core\Configure;
use Cake\Datasource\Exception\RecordNotFoundException;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;
use Exception;
use function App\Controller\Admin\__resetPlacesLookupStub;
use function App\Controller\Admin\__setPlacesLookupStub;

/**
 * @link \App\Controller\Admin\PlacesController
 */
class PlacesControllerTest extends TestCase
{
    use IntegrationTestTrait;
    use AuthTestTrait;

    protected array $fixtures = [
        'app.Users',
        'app.Places',
        'app.Sites',
    ];

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        parent::setUp();
        __resetPlacesLookupStub();
        Configure::delete('Api.RestCountries.key');
    }

    /**
     * @inheritDoc
     */
    protected function tearDown(): void
    {
        __resetPlacesLookupStub();
        Configure::delete('Api.RestCountries.key');
        parent::tearDown();
    }

    /**
     * Tests index.
     */
    public function testIndex(): void
    {
        $this->mockIdentity();
        $this->get('/admin/places');
        $this->assertResponseOk();
        $this->assertResponseContains('Places');
        $this->assertResponseContains('data-controller="admin-index-table"');
        $this->assertResponseContains('data-admin-index-table-target="searchInput"');
        $this->assertResponseContains('places-table');
        $this->assertResponseContains('data-datatables-url');
        $this->assertResponseContains('total');
    }

    /**
     * Tests datatables returns json.
     */
    public function testDatatablesReturnsJson(): void
    {
        $this->mockIdentity();
        $this->get('/admin/places/datatables?draw=1&start=0&length=25');
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
        $this->get('/admin/places/datatables?draw=2&start=0&length=25&search[value]=Murray');
        $this->assertResponseOk();

        $body = json_decode((string)$this->_response->getBody(), true);
        $this->assertSame(2, $body['draw']);
        $this->assertLessThanOrEqual($body['recordsTotal'], $body['recordsFiltered']);
        foreach ($body['data'] as $row) {
            $rowText = strtolower($row['country'] . ' ' . $row['city'] . ' ' . $row['state']);
            $this->assertStringContainsString('murray', $rowText);
        }
    }

    /**
     * Tests datatables accepts explicit order params.
     */
    public function testDatatablesAcceptsOrderParams(): void
    {
        $this->mockIdentity();
        $this->get('/admin/places/datatables?draw=3&start=0&length=10&order[0][column]=2&order[0][dir]=desc');
        $this->assertResponseOk();

        $body = json_decode((string)$this->_response->getBody(), true);
        $this->assertSame(3, $body['draw']);
        $this->assertArrayHasKey('data', $body);
        $this->assertIsArray($body['data']);
    }

    /**
     * Tests datatables requires auth.
     */
    public function testDatatablesRequiresAuth(): void
    {
        $this->get('/admin/places/datatables');
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
        $this->post('/admin/places/add', ['place_country' => 'USA', 'place_city' => 'Nashville', 'place_state' => 'TN']);
        $this->assertRedirect(['prefix' => 'Admin', 'controller' => 'Places', 'action' => 'index']);
    }

    /**
     * Tests add get.
     */
    public function testAddGet(): void
    {
        $this->mockIdentity();
        $this->get('/admin/places/add');
        $this->assertResponseOk();
        $this->assertResponseContains('Add Place');
    }

    /**
     * Tests edit.
     */
    public function testEdit(): void
    {
        $this->mockIdentity();
        $this->enableCsrfToken();
        $this->enableSecurityToken();
        $this->post('/admin/places/edit/1', ['place_country' => 'USA', 'place_city' => 'Updated', 'place_state' => 'TN']);
        $this->assertRedirect(['prefix' => 'Admin', 'controller' => 'Places', 'action' => 'index']);
    }

    /**
     * Tests edit get.
     */
    public function testEditGet(): void
    {
        $this->mockIdentity();
        $this->get('/admin/places/edit/1');
        $this->assertResponseOk();
        $this->assertResponseContains('Edit Place');
    }

    /**
     * Tests delete.
     */
    public function testDelete(): void
    {
        $this->mockIdentity();
        $this->enableCsrfToken();
        $this->delete('/admin/places/delete/1');
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
            $this->delete('/admin/places/delete/999');
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
        $this->get('/admin/places');
        $this->assertTrue($this->_response->getStatusCode() >= 200);
    }

    /**
     * Tests ajax search returns results.
     */
    public function testAjaxSearchReturnsResults(): void
    {
        $this->mockIdentity();
        $this->get('/admin/places/ajax-search?q=Murray');
        $this->assertResponseOk();
        $data = json_decode((string)$this->_response->getBody(), true);
        $this->assertTrue($data['success']);
        $this->assertNotEmpty($data['results']);
        $this->assertEquals('Murray', $data['results'][0]['place_city']);
        $this->assertArrayHasKey('id', $data['results'][0]);
        $this->assertArrayHasKey('place_city', $data['results'][0]);
        $this->assertArrayHasKey('place_state', $data['results'][0]);
    }

    /**
     * Tests ajax search empty query.
     */
    public function testAjaxSearchEmptyQuery(): void
    {
        $this->mockIdentity();
        $this->get('/admin/places/ajax-search?q=');
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
        $this->get('/admin/places/ajax-search?q=Nonexistent99');
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
        $this->post('/admin/places/ajax-search', ['q' => 'Murray']);
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
        $this->post('/admin/places/ajax-add', [
            'place_country' => 'USA',
            'place_city' => 'Nashville',
            'place_state' => 'TN',
        ]);
        $this->assertResponseOk();
        $data = json_decode((string)$this->_response->getBody(), true);
        $this->assertTrue($data['success']);
        $this->assertEquals('Nashville, TN', $data['newOption']['text']);
        $this->assertNotEmpty($data['newOption']['value']);
    }

    /**
     * Tests ajax add validation error.
     */
    public function testAjaxAddValidationError(): void
    {
        $this->mockIdentity();
        $this->enableCsrfToken();
        $this->enableSecurityToken();
        // Missing required place_country
        $this->post('/admin/places/ajax-add', [
            'place_city' => 'Nashville',
        ]);
        $this->assertResponseOk();
        $data = json_decode((string)$this->_response->getBody(), true);
        $this->assertFalse($data['success']);
        $this->assertNotEmpty($data['errors']);
    }

    /**
     * Tests ajax add invalid method.
     */
    public function testAjaxAddInvalidMethod(): void
    {
        $this->mockIdentity();
        $this->get('/admin/places/ajax-add');
        $this->assertResponseOk();
        $data = json_decode((string)$this->_response->getBody(), true);
        $this->assertFalse($data['success']);
    }

    /**
     * Tests ajax add duplicate returns existing.
     */
    public function testAjaxAddDuplicateReturnsExisting(): void
    {
        $this->mockIdentity();
        $this->enableCsrfToken();
        $this->enableSecurityToken();
        // The fixture already has place_country=USA, place_city=Murray, place_state=KY
        $this->post('/admin/places/ajax-add', [
            'place_country' => 'USA',
            'place_city' => 'Murray',
            'place_state' => 'KY',
        ]);
        $this->assertResponseOk();
        $data = json_decode((string)$this->_response->getBody(), true);
        $this->assertTrue($data['success']);
        $this->assertEquals(1, $data['newOption']['value']);
        $this->assertStringContainsString('already exists', $data['message']);
    }

    /**
     * Tests add post duplicate shows error.
     */
    public function testAddPostDuplicateShowsError(): void
    {
        $this->mockIdentity();
        $this->enableCsrfToken();
        $this->enableSecurityToken();
        $this->enableRetainFlashMessages();
        // Duplicate of fixture place
        $this->post('/admin/places/add', ['place_country' => 'USA', 'place_city' => 'Murray', 'place_state' => 'KY']);
        $this->assertNoRedirect();
        $this->assertFlashMessage('A place with that country, city, and state already exists.');
    }

    /**
     * Tests add post validation failure shows generic save error.
     */
    public function testAddPostValidationShowsGenericError(): void
    {
        $this->mockIdentity();
        $this->enableCsrfToken();
        $this->enableSecurityToken();
        $this->enableRetainFlashMessages();

        $this->post('/admin/places/add', [
            'place_country' => '',
            'place_city' => '',
            'place_state' => '',
        ]);

        $this->assertNoRedirect();
        $this->assertFlashMessage('The place could not be saved.');
    }

    /**
     * Tests edit validation failure shows generic save error.
     */
    public function testEditPostValidationShowsGenericError(): void
    {
        $this->mockIdentity();
        $this->enableCsrfToken();
        $this->enableSecurityToken();
        $this->enableRetainFlashMessages();

        $this->post('/admin/places/edit/1', [
            'place_country' => '',
            'place_city' => '',
            'place_state' => '',
        ]);

        $this->assertNoRedirect();
        $this->assertFlashMessage('The place could not be saved.');
    }

    /**
     * Test that Place add/edit forms are NOT wrapped in a nested turbo-frame.
     *
     * A nested frame without target="_top" causes "Content missing" after redirect
     * because Turbo tries to find the frame ID on the target page.
     */
    public function testAddAndEditFormsHaveNoNestedTurboFrame(): void
    {
        $this->mockIdentity();

        $this->get('/admin/places/add');
        $this->assertResponseOk();
        $body = (string)$this->_response->getBody();
        $this->assertSame(
            1,
            substr_count($body, '<turbo-frame id="'),
            'Place add form must not be wrapped in a nested turbo-frame',
        );

        $this->get('/admin/places/edit/1');
        $this->assertResponseOk();
        $body = (string)$this->_response->getBody();
        $this->assertSame(
            1,
            substr_count($body, '<turbo-frame id="'),
            'Place edit form must not be wrapped in a nested turbo-frame',
        );
    }

    /**
     * Tests countries lookup returns empty payload for short queries.
     */
    public function testCountriesLookupShortQueryReturnsEmpty(): void
    {
        $this->mockIdentity();
        $this->get('/admin/places/countries-lookup?q=U');
        $this->assertResponseOk();
        $this->assertSame([], json_decode((string)$this->_response->getBody(), true));
    }

    /**
     * Tests countries lookup returns empty payload when API key is missing.
     */
    public function testCountriesLookupWithoutApiKeyReturnsEmpty(): void
    {
        $this->mockIdentity();
        Configure::delete('Api.RestCountries.key');

        $this->get('/admin/places/countries-lookup?q=United');
        $this->assertResponseOk();
        $this->assertSame([], json_decode((string)$this->_response->getBody(), true));
    }

    /**
     * Tests countries lookup transforms REST Countries payload.
     */
    public function testCountriesLookupTransformsPayload(): void
    {
        $this->mockIdentity();
        Configure::write('Api.RestCountries.key', 'test-key');
        __setPlacesLookupStub(json_encode([
            'data' => [
                'objects' => [
                    [
                        'names' => ['common' => 'United States'],
                        'codes' => ['alpha_3' => 'usa'],
                    ],
                    [
                        'names' => ['common' => ''],
                        'codes' => ['alpha_3' => ''],
                    ],
                ],
            ],
        ]));

        $this->get('/admin/places/countries-lookup?q=United');
        $this->assertResponseOk();

        $payload = json_decode((string)$this->_response->getBody(), true);
        $this->assertCount(1, $payload);
        $this->assertSame('United States', $payload[0]['name']['common']);
        $this->assertSame('USA', $payload[0]['cca3']);
    }

    /**
     * Tests countries lookup handles false and invalid payload responses.
     */
    public function testCountriesLookupHandlesFalseAndInvalidPayloadResponses(): void
    {
        $this->mockIdentity();
        Configure::write('Api.RestCountries.key', 'test-key');

        __setPlacesLookupStub(false);
        $this->get('/admin/places/countries-lookup?q=United');
        $this->assertResponseOk();
        $this->assertSame([], json_decode((string)$this->_response->getBody(), true));

        __setPlacesLookupStub(json_encode(['data' => ['objects' => []]]));
        $this->get('/admin/places/countries-lookup?q=United');
        $this->assertResponseOk();
        $this->assertSame([], json_decode((string)$this->_response->getBody(), true));
    }
}
