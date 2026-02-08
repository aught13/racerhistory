<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller;

use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

/**
 * App\Controller\GamesController Test Case
 */
class GamesControllerTest extends TestCase
{
    use IntegrationTestTrait;

    protected array $fixtures = [
        'app.Sports',
        'app.Teams',
        'app.Seasons',
        'app.TeamSeasons',
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
        'app.StatBasketGameBox',
        'app.StatBasketGamePerson',
        'app.StatBasketGameTeam',
        'app.StatBasketGameOpponent',
        'app.TeamSeasonRosters',
        'app.Persons',
    ];

    public function testIndex(): void
    {
        $this->get('/games');
        $this->assertResponseOk();
        $this->assertResponseContains('Games');
        $this->assertResponseContains('Men\'s Basketball game results');
    }

    public function testIndexDisplaysGames(): void
    {
        $this->get('/games');
        $this->assertResponseOk();

        $games = $this->viewVariable('games');
        $this->assertIsArray($games);
    }

    public function testView(): void
    {
        $this->get('/games/1');
        $this->assertResponseOk();
        $this->assertResponseContains('Box Score');
    }

    public function testViewWithInvalidId(): void
    {
        $this->get('/games/9999');
        $this->assertResponseError();
        $this->assertResponseCode(404);
    }

    public function testViewSetsVariables(): void
    {
        $this->get('/games/1');
        $this->assertResponseOk();

        $game = $this->viewVariable('game');
        $this->assertNotNull($game);

        $statsElement = $this->viewVariable('statsElement');
        $this->assertTrue($statsElement === null || is_string($statsElement));

        $teamBoxStats = $this->viewVariable('teamBoxStats');
        $this->assertTrue(is_array($teamBoxStats) || $teamBoxStats === null);

        $images = $this->viewVariable('images');
        $this->assertIsArray($images);

        $blogPosts = $this->viewVariable('blogPosts');
        $this->assertIsArray($blogPosts);
    }

    public function testStatsFrame(): void
    {
        $this->get('/games/stats/1');
        $this->assertResponseOk();
        $this->assertResponseContains('Player Stats');
    }

    public function testAuthorizationSkipped(): void
    {
        // Public pages should not require authentication
        $this->get('/games');
        $this->assertResponseOk();

        $this->get('/games/1');
        $this->assertResponseOk();
    }
}
