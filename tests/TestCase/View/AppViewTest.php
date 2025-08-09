<?php
declare(strict_types=1);

namespace App\Test\TestCase\View;

use App\View\AppView;
use Cake\TestSuite\TestCase;

/**
 * App\View\AppView Test Case
 */
class AppViewTest extends TestCase
{
    /**
     * Test subject
     *
     * @var \App\View\AppView
     */
    protected $AppView;

    /**
     * setUp method
     *
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->AppView = new AppView();
    }

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown(): void
    {
        unset($this->AppView);
        parent::tearDown();
    }

    /**
     * Test initialize method
     *
     * @return void
     */
    public function testInitialize(): void
    {
        $this->AppView->initialize();

        // Test that the view initializes without errors
        $this->assertInstanceOf(AppView::class, $this->AppView);
    }

    /**
     * Test that AppView extends CakePHP's View class
     *
     * @return void
     */
    public function testExtendsView(): void
    {
        $this->assertInstanceOf(\Cake\View\View::class, $this->AppView);
    }
}
