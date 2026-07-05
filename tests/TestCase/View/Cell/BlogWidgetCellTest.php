<?php
declare(strict_types=1);

namespace App\Test\TestCase\View\Cell;

use App\View\Cell\BlogWidgetCell;
use Cake\Event\EventManager;
use Cake\Http\Response;
use Cake\Http\ServerRequest;
use Cake\TestSuite\TestCase;

class BlogWidgetCellTest extends TestCase
{
    protected array $fixtures = [
        'app.BlogPosts',
        'app.BlogTags',
        'app.BlogPostsBlogTags',
    ];

    private BlogWidgetCell $cell;

    /**
     * Test setUp
     *
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();
        $request = new ServerRequest();
        $response = new Response();
        $this->cell = new BlogWidgetCell($request, $response, $this->getMockBuilder(EventManager::class)->getMock());
    }

    /**
     * Test tearDown
     *
     * @return void
     */
    public function tearDown(): void
    {
        unset($this->cell);
        parent::tearDown();
    }

    /**
     * Test homeFeed
     *
     * @return void
     */
    public function testHomeFeed(): void
    {
        $this->cell->homeFeed();
        $this->assertTrue($this->cell->viewBuilder()->hasVar('hero'));
        $this->assertTrue($this->cell->viewBuilder()->hasVar('gridPosts'));
    }

    /**
     * Test sidebar
     *
     * @return void
     */
    public function testSidebar(): void
    {
        $this->cell->sidebar();
        $this->assertTrue($this->cell->viewBuilder()->hasVar('recentPosts'));
    }
}
