<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller\Admin;

use App\Test\TestCase\Support\AuthTestTrait;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

/**
 * @link \App\Controller\Admin\PersonsController
 */
class PersonsControllerTest extends TestCase
{
    use IntegrationTestTrait;
    use AuthTestTrait;

    protected array $fixtures = [
        'app.Persons',
        'app.Images',
        'app.Users',
        'app.Sports',
        'app.Teams',
        'app.Seasons',
        'app.TeamSeasons',
        'app.TeamSeasonRosters',
        'app.Games',
        'app.Opponents',
        'app.StatBasketGamePerson',
        'app.StatBasketSeasonPerson',
        'app.Places',
    ];

    /**
     * Sets up the test case.
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->enableCsrfToken();
        $this->enableSecurityToken();
    }

    /**
     * Tests index requires auth.
     */
    public function testIndexRequiresAuth(): void
    {
        $this->get('/admin/persons');
        $this->assertRedirectContains('/users/login');
    }

    /**
     * Tests index authenticated.
     */
    public function testIndexAuthenticated(): void
    {
        $this->mockIdentity();
        $this->get('/admin/persons');
        $this->assertResponseOk();
        // Server-side DT: table shell present, no PHP-rendered rows
        $this->assertResponseContains('persons-table');
        $this->assertResponseContains('data-datatables-url');
        // Total count label rendered
        $this->assertResponseContains('total');
    }

    /**
     * Tests datatables returns json.
     */
    public function testDatatablesReturnsJson(): void
    {
        $this->mockIdentity();
        $this->get('/admin/persons/datatables?draw=1&start=0&length=25');
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
        $this->get('/admin/persons/datatables?draw=2&start=0&length=25&search[value]=John');
        $this->assertResponseOk();
        $body = json_decode((string)$this->_response->getBody(), true);
        $this->assertSame(2, $body['draw']);
        // Filtered count should be <= total
        $this->assertLessThanOrEqual($body['recordsTotal'], $body['recordsFiltered']);
        // Returned rows should match the search term
        foreach ($body['data'] as $row) {
            $nameText = strtolower($row['first'] . ' ' . $row['last'] . ' ' . $row['display']);
            $this->assertStringContainsString('john', $nameText);
        }
    }

    /**
     * Tests datatables respects length cap.
     */
    public function testDatatablesRespectsLengthCap(): void
    {
        $this->mockIdentity();
        $this->get('/admin/persons/datatables?draw=3&start=0&length=9999');
        $this->assertResponseOk();
        $body = json_decode((string)$this->_response->getBody(), true);
        // Should cap at 500
        $this->assertLessThanOrEqual(500, count($body['data']));
    }

    /**
     * Tests datatables requires auth.
     */
    public function testDatatablesRequiresAuth(): void
    {
        $this->get('/admin/persons/datatables');
        $this->assertRedirectContains('/users/login');
    }

    /**
     * Tests datatables ordering.
     */
    public function testDatatablesOrdering(): void
    {
        $this->mockIdentity();
        $this->get('/admin/persons/datatables?draw=4&start=0&length=50&order[0][column]=3&order[0][dir]=desc');
        $this->assertResponseOk();
        $body = json_decode((string)$this->_response->getBody(), true);
        $this->assertSame(4, $body['draw']);
        if (count($body['data']) > 1) {
            // Last names should be in descending order
            $lasts = array_column($body['data'], 'last');
            $sorted = $lasts;
            arsort($sorted);
            $this->assertSame(array_values($sorted), array_values($lasts));
        }
    }

    /**
     * Tests view.
     */
    public function testView(): void
    {
        $this->mockIdentity();
        $this->get('/admin/persons/view/1');
        $this->assertResponseOk();
        $this->assertResponseContains('Sample biography for John Doe.');
        $this->assertResponseContains('data-controller="back-navigation"');
        $this->assertResponseContains('data-action="click->back-navigation#goBack"');
    }

    /**
     * Tests view shows person image when set.
     */
    public function testViewShowsPersonImageWhenSet(): void
    {
        $this->mockIdentity();
        // Create a person with a person_image id referencing fixture image id 1
        $persons = $this->getTableLocator()->get('Persons');
        $person = $persons->newEmptyEntity();
        $person = $persons->patchEntity($person, [
            'first' => 'Pic', 'last' => 'Owner', 'display' => 'Pic Owner', 'person_image' => 1,
        ]);
        $saved = $persons->save($person);
        if ($saved === false) {
            $errors = $person->getErrors();
            $this->fail('Failed to save person: ' . json_encode($errors));
        }
        $this->get('/admin/persons/view/' . $person->id);
        $this->assertResponseOk();
        $body = (string)$this->_response->getBody();
        $this->assertStringContainsString('<img', $body);
        $this->assertStringContainsString('/img/storage/', $body);
    }

    /**
     * Tests add get.
     */
    public function testAddGet(): void
    {
        $this->mockIdentity();
        $this->get('/admin/persons/add');
        $this->assertResponseOk();
    }

    /**
     * Tests add post valid.
     */
    public function testAddPostValid(): void
    {
        $this->mockIdentity();
        $data = [
            'first' => 'Alan',
            'last' => 'Turing',
            'display' => 'Alan Turing',
        ];
        $this->post('/admin/persons/add', $data);
        $this->assertRedirect('/admin/persons');
        $this->assertFlashMessage('The person has been saved.');
    }

    /**
     * Tests add post invalid.
     */
    public function testAddPostInvalid(): void
    {
        $this->mockIdentity();
        $this->enableRetainFlashMessages();
        $data = [ 'first' => '', 'last' => '' ];
        $this->post('/admin/persons/add', $data);
        $this->assertResponseOk();
        $this->assertFlashMessage('The person could not be saved. Please, try again.');
    }

    /**
     * Tests edit get.
     */
    public function testEditGet(): void
    {
        $this->mockIdentity();
        $this->get('/admin/persons/edit/1');
        $this->assertResponseOk();
    }

    /**
     * Tests edit post.
     */
    public function testEditPost(): void
    {
        $this->mockIdentity();
        $data = [ 'display' => 'Updated Display' ];
        $this->post('/admin/persons/edit/1', $data);
        $this->assertRedirect('/admin/persons');
        $this->assertFlashMessage('The person has been saved.');
    }

    /**
     * Tests delete.
     */
    public function testDelete(): void
    {
        $this->mockIdentity();
        $this->post('/admin/persons/delete/1');
        $this->assertRedirect('/admin/persons');
    }

    /**
     * Tests bulk delete none selected.
     */
    public function testBulkDeleteNoneSelected(): void
    {
        $this->mockIdentity();
        $this->enableRetainFlashMessages();
        $this->post('/admin/persons/bulkDelete', ['person_ids' => ['']]);
        $this->assertRedirect('/admin/persons');
        $this->assertFlashMessage('No persons selected for deletion.');
    }

    /**
     * Tests bulk delete some.
     */
    public function testBulkDeleteSome(): void
    {
        $this->mockIdentity();
        $this->post('/admin/persons/bulkDelete', ['person_ids' => ['1']]);
        $this->assertRedirect('/admin/persons');
    }

    /**
     * Tests bulk dispatcher invalid.
     */
    public function testBulkDispatcherInvalid(): void
    {
        $this->mockIdentity();
        $this->enableRetainFlashMessages();
        $this->post('/admin/persons/bulk', ['bulk_action' => 'nonsense']);
        $this->assertRedirect('/admin/persons');
        $this->assertFlashMessage('Invalid bulk action.');
    }

    /**
     * Tests bulk dispatcher delete.
     */
    public function testBulkDispatcherDelete(): void
    {
        $this->mockIdentity();
        $this->post('/admin/persons/bulk', ['bulk_action' => 'delete', 'person_ids' => ['1']]);
        $this->assertRedirect('/admin/persons');
    }

    /**
     * Tests ajax add invalid method.
     */
    public function testAjaxAddInvalidMethod(): void
    {
        $this->mockIdentity();
        $this->get('/admin/persons/ajaxAdd');
        $this->assertResponseOk();
        $response = json_decode((string)$this->_response->getBody(), true);
        $this->assertFalse($response['success']);
    }

    /**
     * Tests ajax add valid.
     */
    public function testAjaxAddValid(): void
    {
        $this->mockIdentity();
        $data = [ 'first' => 'Grace', 'last' => 'Hopper', 'display' => 'Grace Hopper' ];
        $this->post('/admin/persons/ajaxAdd', $data);
        $this->assertResponseOk();
        $body = json_decode((string)$this->_response->getBody(), true);
        $this->assertTrue($body['success']);
        $this->assertEquals('The person has been saved.', $body['message']);
    }

    /**
     * Tests ajax search.
     */
    public function testAjaxSearch(): void
    {
        $this->mockIdentity();
        $this->get('/admin/persons/ajaxSearch?q=John');
        $this->assertResponseOk();
        $data = json_decode((string)$this->_response->getBody(), true);
        $this->assertTrue($data['success']);
        $this->assertArrayHasKey('results', $data);
    }

    /**
     * Tests view with roster entries.
     */
    public function testViewWithRosterEntries(): void
    {
        $this->mockIdentity();
        $this->get('/admin/persons/view/1');
        $this->assertResponseOk();
        $body = (string)$this->_response->getBody();

        // Check for roster entries section
        $this->assertStringContainsString('Roster Entries', $body);
    }

    /**
     * Tests view with basketball stats.
     */
    public function testViewWithBasketballStats(): void
    {
        $this->mockIdentity();
        $this->get('/admin/persons/view/1');
        $this->assertResponseOk();
        $body = (string)$this->_response->getBody();

        // Check for game stats table
        $this->assertStringContainsString('Game Stats', $body);
        $this->assertStringContainsString('Season Totals', $body);
    }

    /**
     * Tests view with career stats.
     */
    public function testViewWithCareerStats(): void
    {
        $this->mockIdentity();
        $this->get('/admin/persons/view/1');
        $this->assertResponseOk();
        $body = (string)$this->_response->getBody();

        // Check for career statistics section
        $this->assertStringContainsString('Career Statistics', $body);
        $this->assertStringContainsString('Career Totals', $body);
    }

    /**
     * Tests view without roster entries.
     */
    public function testViewWithoutRosterEntries(): void
    {
        $this->mockIdentity();
        // Create a new person with no roster entries
        $persons = $this->getTableLocator()->get('Persons');
        $person = $persons->newEmptyEntity();
        $person = $persons->patchEntity($person, [
            'first' => 'No',
            'last' => 'Stats',
            'display' => 'No Stats Person',
        ]);
        $persons->save($person);

        $this->get('/admin/persons/view/' . $person->id);
        $this->assertResponseOk();
        $body = (string)$this->_response->getBody();

        // Should not show roster/stats sections
        $this->assertStringNotContainsString('Roster Entries', $body);
        $this->assertStringNotContainsString('Career Statistics', $body);
    }

    /**
     * Tests view with supported sport but no stats shows fallbacks.
     */
    public function testViewWithSupportedSportButNoStatsShowsFallbacks(): void
    {
        $this->mockIdentity();

        // Create a new person
        $persons = $this->getTableLocator()->get('Persons');
        $person = $persons->newEmptyEntity();
        $person = $persons->patchEntity($person, [
            'first' => 'Empty',
            'last' => 'Stats',
            'display' => 'Empty Stats Person',
        ]);
        $this->assertNotFalse($persons->save($person));

        // Create a roster for basketball team season id 1 without any stat rows
        $rosters = $this->getTableLocator()->get('TeamSeasonRosters');
        $roster = $rosters->newEmptyEntity();
        $roster = $rosters->patchEntity($roster, [
            'team_season_id' => 1,
            'person_id' => $person->id,
            'roster_year' => '2024',
        ]);
        $this->assertNotFalse($rosters->save($roster));

        // Ensure there are no game or season person stats for this roster id
        $seasonStats = $this->getTableLocator()->get('StatBasketSeasonPerson');
        $gameStats = $this->getTableLocator()->get('StatBasketGamePerson');
        $this->assertSame(0, $seasonStats->find()->where(['team_season_roster_id' => $roster->id])->count());
        $this->assertSame(0, $gameStats->find()->where(['team_season_roster_id' => $roster->id])->count());

        // View the person page
        $this->get('/admin/persons/view/' . $person->id);
        $this->assertResponseOk();
        $body = (string)$this->_response->getBody();

        // Should show roster section
        $this->assertStringContainsString('Roster Entries', $body);

        // Should render friendly fallbacks for stats and career
        $this->assertStringContainsString('No stats available for this roster yet.', $body);
        $this->assertStringContainsString('No career statistics have been recorded yet.', $body);
    }

    /**
     * Tests view multi sport fallbacks supported vs unsupported.
     */
    public function testViewMultiSportFallbacksSupportedVsUnsupported(): void
    {
        $this->mockIdentity();

        // Create a new person
        $persons = $this->getTableLocator()->get('Persons');
        $person = $persons->newEmptyEntity();
        $person = $persons->patchEntity($person, [
            'first' => 'Multi',
            'last' => 'Sport',
            'display' => 'Multi Sport Person',
        ]);
        $this->assertNotFalse($persons->save($person));

        // Basketball roster on existing team season id 1 (no stats present)
        $rosters = $this->getTableLocator()->get('TeamSeasonRosters');
        $basketRoster = $rosters->newEmptyEntity();
        $basketRoster = $rosters->patchEntity($basketRoster, [
            'team_season_id' => 1,
            'person_id' => $person->id,
            'roster_year' => '2024',
        ]);
        $this->assertNotFalse($rosters->save($basketRoster));

        // Ensure no basketball stats rows exist for this roster
        $seasonStats = $this->getTableLocator()->get('StatBasketSeasonPerson');
        $gameStats = $this->getTableLocator()->get('StatBasketGamePerson');
        $this->assertSame(0, $seasonStats->find()->where(['team_season_roster_id' => $basketRoster->id])->count());
        $this->assertSame(0, $gameStats->find()->where(['team_season_roster_id' => $basketRoster->id])->count());

        // Create a non-basketball team season (unsupported sport for stats)
        $teamSeasons = $this->getTableLocator()->get('TeamSeasons');
        $newSeason = $teamSeasons->newEmptyEntity();
        // Use team_id = 3 from TeamsFixture (Football), season_id = 1 existing
        $newSeason = $teamSeasons->patchEntity($newSeason, [
            'team_id' => 3,
            'season_id' => 1,
            'semester' => 1,
            'team_season_image' => 1,
        ]);
        $this->assertNotFalse($teamSeasons->save($newSeason));

        // Create roster entry for unsupported sport
        $footballRoster = $rosters->newEmptyEntity();
        $footballRoster = $rosters->patchEntity($footballRoster, [
            'team_season_id' => $newSeason->id,
            'person_id' => $person->id,
            'roster_year' => '2024',
        ]);
        $this->assertNotFalse($rosters->save($footballRoster));

        // View the person page
        $this->get('/admin/persons/view/' . $person->id);
        $this->assertResponseOk();
        $body = (string)$this->_response->getBody();

        // Shows roster section overall
        $this->assertStringContainsString('Roster Entries', $body);

        // For supported sport (basketball) with no stats, fallbacks appear
        $this->assertStringContainsString('No stats available for this roster yet.', $body);
        $this->assertStringContainsString('No career statistics have been recorded yet.', $body);

        // Per-roster stats fallback should render for each roster (2 total)
        $this->assertSame(2, substr_count($body, 'No stats available for this roster yet.'));
        // Career fallback should render only for supported sports (basketball -> 1 total)
        $this->assertSame(1, substr_count($body, 'No career statistics have been recorded yet.'));
    }

    /**
     * Tests add form contains birth place and previous fields.
     */
    public function testAddFormContainsBirthPlaceAndPreviousFields(): void
    {
        $this->mockIdentity();
        $this->get('/admin/persons/add');
        $this->assertResponseOk();
        $body = (string)$this->_response->getBody();
        $this->assertStringContainsString('birth-place-search', $body);
        $this->assertStringContainsString('birth-place-id-field', $body);
        $this->assertStringContainsString('Previous School', $body);
        $this->assertStringContainsString('person_previous', $body);
    }

    /**
     * Tests edit form contains birth place and previous fields.
     */
    public function testEditFormContainsBirthPlaceAndPreviousFields(): void
    {
        $this->mockIdentity();
        $this->get('/admin/persons/edit/1');
        $this->assertResponseOk();
        $body = (string)$this->_response->getBody();
        $this->assertStringContainsString('birth-place-search', $body);
        $this->assertStringContainsString('birth-place-id-field', $body);
        $this->assertStringContainsString('Previous School', $body);
        $this->assertStringContainsString('person_previous', $body);
    }

    /**
     * Tests edit shows existing birth place.
     */
    public function testEditShowsExistingBirthPlace(): void
    {
        $this->mockIdentity();
        $this->get('/admin/persons/edit/1');
        $this->assertResponseOk();
        $body = (string)$this->_response->getBody();
        // Person 1 has birth_place_id=1 (Murray, KY)
        $this->assertStringContainsString('Murray', $body);
    }

    /**
     * Tests edit shows existing previous school.
     */
    public function testEditShowsExistingPreviousSchool(): void
    {
        $this->mockIdentity();
        $this->get('/admin/persons/edit/1');
        $this->assertResponseOk();
        $body = (string)$this->_response->getBody();
        // Person 1 has person_previous='Central High'
        $this->assertStringContainsString('Central High', $body);
    }

    /**
     * Tests add post with new fields.
     */
    public function testAddPostWithNewFields(): void
    {
        $this->mockIdentity();
        $data = [
            'first' => 'Test',
            'last' => 'Player',
            'display' => 'Test Player',
            'birth_place_id' => 1,
            'person_previous' => 'Springfield High',
        ];
        $this->post('/admin/persons/add', $data);
        $this->assertRedirect('/admin/persons');

        $persons = $this->getTableLocator()->get('Persons');
        $person = $persons->find()->where(['first' => 'Test', 'last' => 'Player'])->firstOrFail();
        $this->assertSame(1, $person->birth_place_id);
        $this->assertSame('Springfield High', $person->person_previous);
    }

    /**
     * Tests edit post with new fields.
     */
    public function testEditPostWithNewFields(): void
    {
        $this->mockIdentity();
        $data = [
            'display' => 'John Doe Updated',
            'birth_place_id' => 1,
            'person_previous' => 'Updated High',
        ];
        $this->post('/admin/persons/edit/1', $data);
        $this->assertRedirect('/admin/persons');

        $persons = $this->getTableLocator()->get('Persons');
        $person = $persons->get(1);
        $this->assertSame(1, $person->birth_place_id);
        $this->assertSame('Updated High', $person->person_previous);
    }

    /**
     * Tests ajax add with new fields.
     */
    public function testAjaxAddWithNewFields(): void
    {
        $this->mockIdentity();
        $data = [
            'first' => 'Ajax',
            'last' => 'Person',
            'display' => 'Ajax Person',
            'birth_place_id' => 1,
            'person_previous' => 'Ajax High',
        ];
        $this->post('/admin/persons/ajaxAdd', $data);
        $this->assertResponseOk();
        $body = json_decode((string)$this->_response->getBody(), true);
        $this->assertTrue($body['success']);

        $persons = $this->getTableLocator()->get('Persons');
        $person = $persons->find()->where(['first' => 'Ajax', 'last' => 'Person'])->firstOrFail();
        $this->assertSame(1, $person->birth_place_id);
        $this->assertSame('Ajax High', $person->person_previous);
    }

    /**
     * Test admin persons pages include turbo-frame for SPA navigation.
     */
    public function testAdminPagesContainTurboFrame(): void
    {
        $this->mockIdentity();
        $this->get('/admin/persons');
        $this->assertResponseOk();
        $this->assertResponseContains('<turbo-frame id="admin-content"');
    }

    /**
     * Test that Person add/edit forms are NOT wrapped in a nested turbo-frame.
     *
     * A nested frame without target="_top" causes "Content missing" after redirect
     * because Turbo tries to find the frame ID on the target page.
     */
    public function testAddAndEditFormsHaveNoNestedTurboFrame(): void
    {
        $this->mockIdentity();

        $this->get('/admin/persons/add');
        $this->assertResponseOk();
        $body = (string)$this->_response->getBody();
        $this->assertSame(
            1,
            substr_count($body, '<turbo-frame id="'),
            'Person add form must not be wrapped in a nested turbo-frame',
        );

        $this->get('/admin/persons/edit/1');
        $this->assertResponseOk();
        $body = (string)$this->_response->getBody();
        $this->assertSame(
            1,
            substr_count($body, '<turbo-frame id="'),
            'Person edit form must not be wrapped in a nested turbo-frame',
        );
    }
}
