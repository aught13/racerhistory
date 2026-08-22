<?php
declare(strict_types=1);

namespace App\Test\TestCase\View\Element;

use App\View\AppView;
use Cake\Core\Configure;
use Cake\TestSuite\TestCase;

class AdsBlockElementTest extends TestCase
{
    private AppView $view;

    /**
     * @var mixed
     */
    private $originalSiteOptions;

    /**
     * Prepare an AppView instance and preserve prior SiteOptions config.
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->view = new AppView();
        $this->originalSiteOptions = Configure::read('SiteOptions');
    }

    /**
     * Restore SiteOptions config after each test.
     */
    public function tearDown(): void
    {
        Configure::delete('SiteOptions');

        if ($this->originalSiteOptions !== null) {
            Configure::write('SiteOptions', $this->originalSiteOptions);
        }

        unset($this->view);
        parent::tearDown();
    }

    /**
     * Inactive slots should render nothing.
     */
    public function testElementReturnsEmptyWhenSlotInactive(): void
    {
        Configure::write('SiteOptions.ad_below_nav_active', false);
        Configure::write('SiteOptions.ad_below_nav_html', '<div>Ad</div>');
        Configure::write('SiteOptions.ad_below_nav_google_mode', false);

        $output = $this->view->element('Ads/block', ['slot' => 'below_nav']);

        $this->assertSame('', trim($output));
    }

    /**
     * Active non-Google slots should render expected section markup.
     */
    public function testElementRendersStandardSlotMarkup(): void
    {
        Configure::write('SiteOptions.ad_below_nav_active', true);
        Configure::write('SiteOptions.ad_below_nav_html', '<div class="ad-content">Ad</div>');
        Configure::write('SiteOptions.ad_below_nav_google_mode', false);

        $output = $this->view->element('Ads/block', ['slot' => 'below_nav']);

        $this->assertStringContainsString('rh-ad-slot--below-nav', $output);
        $this->assertStringContainsString('data-google-mode="0"', $output);
        $this->assertStringContainsString('<div class="ad-content">Ad</div>', $output);
    }

    /**
     * Google mode should render slot classes without inline queue push script.
     */
    public function testGoogleModeRendersClassWithoutInlinePushScript(): void
    {
        Configure::write('SiteOptions.ad_below_nav_active', true);
        Configure::write('SiteOptions.ad_below_nav_html', '<ins class="adsbygoogle"></ins>');
        Configure::write('SiteOptions.ad_below_nav_google_mode', true);

        $output = $this->view->element('Ads/block', ['slot' => 'below_nav']);

        $this->assertStringContainsString('rh-ad-slot--google', $output);
        $this->assertStringContainsString('data-google-mode="1"', $output);
        $this->assertStringNotContainsString('(window.adsbygoogle = window.adsbygoogle || []).push({});', $output);
    }
}
