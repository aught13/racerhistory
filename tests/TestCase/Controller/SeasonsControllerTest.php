<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller;

use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

/**
 * App\Controller\SeasonsController Test Case
 */
class SeasonsControllerTest extends TestCase
{
    use IntegrationTestTrait;

    protected array $fixtures = [
        'app.Sports',
        'app.Teams',
        'app.Seasons',
        'app.TeamSeasons',
        'app.TeamSeasonRosters',
        'app.Persons',
        'app.Games',
        'app.GameTypes',
        'app.Opponents',
        'app.Places',
        'app.Sites',
        'app.Images',
        'app.ImageTags',
        'app.ImagesImageTags',
        'app.BlogPosts',
        'app.BlogTags',
        'app.BlogPostsBlogTags',
    ];

    public function testIndex(): void
    {
        $this->get('/seasons');
        $this->assertResponseOk();
        $this->assertResponseContains('Seasons');
        $this->assertResponseContains('Men\'s Basketball team seasons');
    }

    public function testIndexDisplaysTeamSeasons(): void
    {
        $this->get('/seasons');
        $this->assertResponseOk();

        // Check that view variable is set
        $teamSeasons = $this->viewVariable('teamSeasons');
        $this->assertIsArray($teamSeasons);
    }

    public function testView(): void
    {
        $this->get('/seasons/1');
        $this->assertResponseOk();
        $this->assertResponseContains('Season');
    }

    public function testViewWithInvalidId(): void
    {
        $this->get('/seasons/9999');
        $this->assertResponseError();
        $this->assertResponseCode(404);
    }

    public function testViewSetsVariables(): void
    {
        $this->get('/seasons/1');
        $this->assertResponseOk();

        $teamSeason = $this->viewVariable('teamSeason');
        $this->assertNotNull($teamSeason);

        $images = $this->viewVariable('images');
        $this->assertIsArray($images);

        $blogPosts = $this->viewVariable('blogPosts');
        $this->assertIsArray($blogPosts);

        $games = $this->viewVariable('games');
        $this->assertIsArray($games);

        $roster = $this->viewVariable('roster');
        $this->assertIsArray($roster);
    }

    public function testAuthorizationSkipped(): void
    {
        // Public pages should not require authentication
        $this->get('/seasons');
        $this->assertResponseOk();

        $this->get('/seasons/1');
        $this->assertResponseOk();
    }
}
