<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller;

use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

/**
 * App\Controller\PeopleController Test Case
 *
 * @link \App\Controller\PeopleController
 */
class PeopleControllerTest extends TestCase
{
    use IntegrationTestTrait;

    protected array $fixtures = [
        'app.Persons',
        'app.TeamSeasonRosters',
        'app.TeamSeasons',
        'app.Teams',
        'app.Sports',
        'app.Seasons',
        'app.Images',
        'app.ImageTags',
        'app.ImagesImageTags',
        'app.BlogPosts',
        'app.BlogTags',
        'app.BlogPostsBlogTags',
        'app.StatBasketGamePerson',
        'app.Games',
        'app.GameTypes',
        'app.Opponents',
        'app.Places',
        'app.Sites',
    ];

    /**
     * Tests index.
     */
    public function testIndex(): void
    {
        $this->get('/people');
        $this->assertResponseOk();
        $this->assertResponseContains('People');
        $this->assertResponseContains('Players, coaches, and staff');
        $this->assertResponseContains('Teams');
        $this->assertResponseContains('Years Active');
        $this->assertResponseContains('data-controller="people-index"');
    }

    /**
     * Tests index displays people.
     */
    public function testIndexDisplaysPeople(): void
    {
        $this->get('/people');
        $this->assertResponseOk();

        $people = $this->viewVariable('people');
        $this->assertIsArray($people);
        $this->assertSame([], $people);

        $peopleRows = $this->viewVariable('peopleRows');
        $this->assertIsArray($peopleRows);
        $this->assertSame([], $peopleRows);

        $peopleCount = $this->viewVariable('peopleCount');
        $this->assertIsInt($peopleCount);
        $this->assertGreaterThan(0, $peopleCount);
    }

    /**
     * Tests index json returns people rows.
     */
    public function testIndexJsonReturnsPeopleRows(): void
    {
        $this->get('/people?format=json&draw=1&start=0&length=50');
        $this->assertResponseOk();
        $this->assertHeaderContains('Content-Type', 'application/json');

        $payload = json_decode((string)$this->_response->getBody(), true);
        $this->assertIsArray($payload);
        $this->assertSame(1, $payload['draw']);
        $this->assertArrayHasKey('data', $payload);
        $this->assertNotEmpty($payload['data']);

        $firstRow = $payload['data'][0];
        $this->assertCount(3, $firstRow);

        $nameHtml = $firstRow[0];
        $this->assertStringContainsString('<a href=', $nameHtml);
        $this->assertStringContainsString('/people/', $nameHtml);

        $rows = array_column($payload['data'], 1);
        $this->assertNotEmpty($rows);
        $this->assertStringContainsString('LAL', $rows[0]);

        $years = array_column($payload['data'], 2);
        $this->assertNotEmpty($years);
        $this->assertStringContainsString('/seasons/1', $years[0]);
    }

    /**
     * Tests index json honors descending name order.
     */
    public function testIndexJsonHonorsDescendingNameOrder(): void
    {
        $this->get('/people?format=json&draw=2&start=0&length=50&order[0][column]=0&order[0][dir]=desc');
        $this->assertResponseOk();
        $this->assertHeaderContains('Content-Type', 'application/json');

        $payload = json_decode((string)$this->_response->getBody(), true);
        $this->assertIsArray($payload);
        $this->assertArrayHasKey('data', $payload);
        $this->assertCount(2, $payload['data']);

        $firstNameHtml = $payload['data'][0][0] ?? '';
        $secondNameHtml = $payload['data'][1][0] ?? '';

        $this->assertStringContainsString('Jane Smith', $firstNameHtml);
        $this->assertStringContainsString('John Doe', $secondNameHtml);
    }

    /**
     * Tests view.
     */
    public function testView(): void
    {
        $this->get('/people/1');
        $this->assertResponseOk();
        $this->assertResponseContains('Seasons');
        $this->assertResponseContains('Game Log');
        $this->assertResponseContains('Stories');
        $this->assertResponseContains('data-controller="person-game-log-tabs"');
        $this->assertResponseContains('<meta property="og:image" content="/img/storage/');
        $this->assertResponseContains('<meta property="twitter:image" content="/img/storage/');
    }

    /**
     * Tests view with invalid id.
     */
    public function testViewWithInvalidId(): void
    {
        $this->get('/people/9999');
        $this->assertResponseError();
        $this->assertResponseCode(404);
    }

    /**
     * Tests view sets variables.
     */
    public function testViewSetsVariables(): void
    {
        $this->get('/people/1');
        $this->assertResponseOk();

        $person = $this->viewVariable('person');
        $this->assertNotNull($person);

        $images = $this->viewVariable('images');
        $this->assertIsArray($images);

        $blogPosts = $this->viewVariable('blogPosts');
        $this->assertIsArray($blogPosts);

        $rosterEntries = $this->viewVariable('rosterEntries');
        $this->assertIsArray($rosterEntries);

        $rostersBySport = $this->viewVariable('rostersBySport');
        $this->assertIsArray($rostersBySport);

        $careerStatsBySport = $this->viewVariable('careerStatsBySport');
        $this->assertIsArray($careerStatsBySport);

        $gameStats = $this->viewVariable('gameStats');
        $this->assertIsArray($gameStats);

        $gameLogGroups = $this->viewVariable('gameLogGroups');
        $this->assertIsArray($gameLogGroups);
    }

    /**
     * Tests game log renders frame.
     */
    public function testGameLogRendersFrame(): void
    {
        $this->get('/people/game-log/1/1');
        $this->assertResponseOk();
        $this->assertResponseContains('turbo-frame');
        $this->assertResponseContains('person-game-log-frame-1-1');
    }

    /**
     * Tests game log for missing roster returns404.
     */
    public function testGameLogForMissingRosterReturns404(): void
    {
        $this->get('/people/game-log/1/9999');
        $this->assertResponseError();
        $this->assertResponseCode(404);
    }

    /**
     * Tests authorization skipped.
     */
    public function testAuthorizationSkipped(): void
    {
        // Public pages should not require authentication
        $this->get('/people');
        $this->assertResponseOk();

        $this->get('/people/1');
        $this->assertResponseOk();
    }
}
