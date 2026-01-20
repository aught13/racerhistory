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
    }

    public function testIndexDisplaysPeople(): void
    {
        $this->get('/people');
        $this->assertResponseOk();

        $people = $this->viewVariable('people');
        $this->assertIsArray($people);
        $this->assertNotEmpty($people);
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
