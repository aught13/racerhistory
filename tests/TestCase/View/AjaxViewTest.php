<?php
declare(strict_types=1);

namespace App\Test\TestCase\View;

use App\View\AjaxView;
use App\View\AppView;
use Cake\TestSuite\TestCase;

/**
 * App\View\AjaxView Test Case
 */
class AjaxViewTest extends TestCase
{
    /**
     * Test subject
     *
     * @var \App\View\AjaxView
     */
    protected $AjaxView;

    /**
     * setUp method
     *
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->AjaxView = new AjaxView();
    }

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown(): void
    {
        unset($this->AjaxView);
        parent::tearDown();
    }

    /**
     * Test initialize method
     *
     * @return void
     */
    public function testInitialize(): void
    {
        $this->AjaxView->initialize();

        // Test that the view initializes without errors
        $this->assertInstanceOf(AjaxView::class, $this->AjaxView);
    }

    /**
     * Test that AjaxView extends AppView
     *
     * @return void
     */
    public function testExtendsAppView(): void
    {
        $this->assertInstanceOf(AppView::class, $this->AjaxView);
    }

    /**
     * Test that default layout is set to 'ajax'
     *
     * @return void
     */
    public function testDefaultLayoutIsAjax(): void
    {
        $layout = $this->AjaxView->getLayout();
        $this->assertEquals('ajax', $layout);
    }
}
