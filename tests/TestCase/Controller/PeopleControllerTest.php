<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller;

use App\Test\TestCase\Support\TaggingServiceFactoryTestTrait;
use Cake\ORM\TableRegistry;
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
    use TaggingServiceFactoryTestTrait;

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
     * Ensure the directory shows each multi-sport team and scopes membership by roster.
     */
    public function testIndexJsonScopesMultiSportTeamMembership(): void
    {
        $teams = $this->getTableLocator()->get('Teams');
        $footballTeam = $teams->get(3);
        $footballTeam->abbr = 'LAL';
        $teams->saveOrFail($footballTeam);

        $teamSeason = $this->getTableLocator()->get('TeamSeasons')->newEntity([
            'team_id' => 3,
            'season_id' => 2,
            'semester' => 1,
        ]);
        $this->getTableLocator()->get('TeamSeasons')->saveOrFail($teamSeason);
        $this->getTableLocator()->get('TeamSeasonRosters')->saveOrFail(
            $this->getTableLocator()->get('TeamSeasonRosters')->newEntity([
                'team_season_id' => $teamSeason->id,
                'person_id' => 1,
                'roster_year' => '2025',
            ]),
        );

        $this->get('/people?format=json&sport=all&team=all&draw=1&start=0&length=50');
        $this->assertResponseOk();
        $allPayload = json_decode((string)$this->_response->getBody(), true);
        $allTeamLabels = implode(', ', array_column($allPayload['data'], 1));
        $this->assertStringContainsString('LAL (Basketball)', $allTeamLabels);
        $this->assertStringContainsString('LAL (Football)', $allTeamLabels);

        $this->get('/people?format=json&sport=football&team=3&draw=2&start=0&length=50');
        $this->assertResponseOk();
        $footballPayload = json_decode((string)$this->_response->getBody(), true);
        $this->assertSame(1, $footballPayload['recordsTotal']);
        $this->assertCount(1, $footballPayload['data']);
        $this->assertSame('LAL', (string)$footballPayload['data'][0][1]);
        $this->assertStringNotContainsString('LAL (Basketball)', (string)$footballPayload['data'][0][1]);
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
     * Verify malformed DataTables filters are ignored safely.
     *
     * @return void
     */
    public function testIndexJsonIgnoresMalformedSearchAndOrderParameters(): void
    {
        $this->get('/people?format=json&draw=5&search=invalid&order=invalid');

        $this->assertResponseOk();
        $payload = json_decode((string)$this->_response->getBody(), true);
        $this->assertSame(5, $payload['draw']);
        $this->assertSame($payload['recordsTotal'], $payload['recordsFiltered']);
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
     * Biography posts appear between the profile and seasons, and person links go to blog pages.
     */
    public function testViewSeparatesBiographyAndStoryLinks(): void
    {
        $this->blogPostTaggingService()->replaceTags(1, ['person-1', 'bio']);

        $postsTable = TableRegistry::getTableLocator()->get('BlogPosts');
        $story = $postsTable->newEntity([
            'title' => 'Person Story',
            'slug' => 'person-story',
            'excerpt' => 'A story about this person.',
            'body' => 'Story body',
            'status' => 'published',
            'is_published' => true,
            'published_at' => '2025-01-02 12:00:00',
        ]);
        $postsTable->saveOrFail($story);
        $this->blogPostTaggingService()->replaceTags((int)$story->id, ['person-1']);

        $this->get('/people/1');

        $this->assertResponseOk();
        $bioPosts = $this->viewVariable('bioPosts');
        $stories = $this->viewVariable('stories');
        $this->assertCount(1, $bioPosts);
        $this->assertSame('first-post', (string)$bioPosts[0]->slug);
        $this->assertCount(1, $stories);
        $this->assertSame('person-story', (string)$stories[0]->slug);

        $body = (string)$this->_response->getBody();
        $this->assertStringContainsString('href="/blog/first-post"', $body);
        $this->assertStringContainsString('href="/blog/person-story"', $body);
        $this->assertStringNotContainsString('data-person-blog-popover', $body);

        $bioPosition = strpos($body, '>Biography</h2>');
        $seasonsPosition = strpos($body, '>Seasons ·');
        $careerPosition = strpos($body, '>Career Statistics</h2>');
        $this->assertNotFalse($seasonsPosition);
        $this->assertNotFalse($bioPosition);
        $this->assertNotFalse($careerPosition);
        $this->assertLessThan($seasonsPosition, $bioPosition);
        $this->assertLessThan($careerPosition, $seasonsPosition);
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

        $bioPosts = $this->viewVariable('bioPosts');
        $this->assertIsArray($bioPosts);

        $stories = $this->viewVariable('stories');
        $this->assertIsArray($stories);

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
     * Scope a multi-sport profile by default and allow full history locally.
     */
    public function testProfileScopeAndFullHistoryOverride(): void
    {
        $teamSeason = $this->getTableLocator()->get('TeamSeasons')->newEntity([
            'team_id' => 3,
            'season_id' => 2,
            'semester' => 1,
        ]);
        $this->getTableLocator()->get('TeamSeasons')->saveOrFail($teamSeason);
        $this->getTableLocator()->get('TeamSeasonRosters')->saveOrFail(
            $this->getTableLocator()->get('TeamSeasonRosters')->newEntity([
                'team_season_id' => $teamSeason->id,
                'person_id' => 1,
                'roster_year' => '2025',
            ]),
        );

        $this->get('/people/1?sport=football&team=3');
        $this->assertResponseOk();
        $rosterEntries = $this->viewVariable('rosterEntries');
        $this->assertCount(1, $rosterEntries);
        $this->assertSame(3, (int)$rosterEntries[0]->team_season->team_id);
        $this->assertFalse($this->viewVariable('showAllHistory'));

        $this->get('/people/1?sport=basketball&team=1&show_all_history=1');
        $this->assertResponseOk();
        $allRosterEntries = $this->viewVariable('rosterEntries');
        $this->assertCount(2, $allRosterEntries);
        $this->assertTrue($this->viewVariable('showAllHistory'));
        $this->assertResponseContains('show_all_history=1');
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
        $this->assertResponseContains('data-person-game-log-table');
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
