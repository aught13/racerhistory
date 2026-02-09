<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller;

use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

/**
 * App\Controller\PeopleController Test Case
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

    public function testIndex(): void
    {
        $this->get('/people');
        $this->assertResponseOk();
        $this->assertResponseContains('People');
        $this->assertResponseContains('Players, coaches, and staff');
        $this->assertResponseContains('Teams');
        $this->assertResponseContains('Years Active');
    }

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

        $rows = array_column($payload['data'], 1);
        $this->assertNotEmpty($rows);
        $this->assertStringContainsString('Los Angeles Lakers', $rows[0]);

        $years = array_column($payload['data'], 2);
        $this->assertNotEmpty($years);
        $this->assertStringContainsString('/seasons/1', $years[0]);
    }

    public function testIndexJsonSearchBuilderFiltersTeams(): void
    {
        $query = http_build_query([
            'format' => 'json',
            'draw' => 1,
            'start' => 0,
            'length' => 50,
            'searchBuilder' => [
                'logic' => 'AND',
                'criteria' => [
                    [
                        'data' => '1',
                        'origData' => '1',
                        'condition' => 'contains',
                        'value1' => 'Lakers',
                    ],
                ],
            ],
        ]);

        $this->get('/people?' . $query);
        $this->assertResponseOk();

        $payload = json_decode((string)$this->_response->getBody(), true);
        $this->assertIsArray($payload);
        $this->assertGreaterThan(0, $payload['recordsFiltered']);
        $this->assertNotEmpty($payload['data']);
        $this->assertStringContainsString('Lakers', $payload['data'][0][1]);
    }

    public function testView(): void
    {
        $this->get('/people/1');
        $this->assertResponseOk();
    }

    public function testViewWithInvalidId(): void
    {
        $this->get('/people/9999');
        $this->assertResponseError();
        $this->assertResponseCode(404);
    }

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

        $gameStats = $this->viewVariable('gameStats');
        $this->assertIsArray($gameStats);
    }

    public function testAuthorizationSkipped(): void
    {
        // Public pages should not require authentication
        $this->get('/people');
        $this->assertResponseOk();

        $this->get('/people/1');
        $this->assertResponseOk();
    }
}
