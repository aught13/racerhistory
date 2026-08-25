<?php
declare(strict_types=1);

namespace App\Test\TestCase\View\Helper;

use App\View\Helper\AdHelper;
use Cake\Core\Configure;
use Cake\TestSuite\TestCase;
use Cake\View\View;

class AdHelperTest extends TestCase
{
    private AdHelper $helper;

    /**
     * @var mixed
     */
    private $originalSiteOptions;

    /**
     * Initialize helper and preserve prior runtime site options.
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->helper = new AdHelper(new View());
        $this->originalSiteOptions = Configure::read('SiteOptions');
    }

    /**
     * Restore runtime options after each test.
     */
    public function tearDown(): void
    {
        Configure::delete('SiteOptions');
        if ($this->originalSiteOptions !== null) {
            Configure::write('SiteOptions', $this->originalSiteOptions);
        }

        unset($this->helper);
        parent::tearDown();
    }

    /**
     * Missing slots should return inactive payload defaults.
     */
    public function testSlotReturnsInactiveDefaultsWhenNotConfigured(): void
    {
        Configure::write('SiteOptions', []);

        $payload = $this->helper->slot('below_nav');

        $this->assertFalse($payload['active']);
        $this->assertSame('below-nav', $payload['slot_class']);
        $this->assertSame('custom', $payload['mode']);
        $this->assertFalse($payload['is_google']);
    }

    /**
     * Custom mode slots should retain html and derive slot class names.
     */
    public function testSlotReturnsCustomPayload(): void
    {
        Configure::write('SiteOptions.ad_below_nav_active', true);
        Configure::write('SiteOptions.ad_below_nav_google_mode', false);
        Configure::write('SiteOptions.ad_below_nav_html', '<div class="banner">Banner</div>');

        $payload = $this->helper->slot('below_nav');

        $this->assertTrue($payload['active']);
        $this->assertSame('below-nav', $payload['slot_class']);
        $this->assertSame('custom', $payload['mode']);
        $this->assertFalse($payload['is_google']);
        $this->assertStringContainsString('banner', $payload['html']);
    }

    /**
     * Google mode slots should expose parsed ad attributes.
     */
    public function testSlotReturnsGooglePayload(): void
    {
        Configure::write('SiteOptions.ad_below_nav_active', true);
        Configure::write('SiteOptions.ad_below_nav_google_mode', true);
        Configure::write(
            'SiteOptions.ad_below_nav_html',
            '<ins class="adsbygoogle" data-ad-client="ca-pub-321" data-ad-slot="1234567890" data-ad-format="auto"></ins>',
        );

        $payload = $this->helper->slot('below_nav');

        $this->assertTrue($payload['active']);
        $this->assertSame('google', $payload['mode']);
        $this->assertTrue($payload['is_google']);
        $this->assertSame('1234567890', $payload['google_slot_id']);
        $this->assertSame('ca-pub-321', $payload['google_client']);
        $this->assertSame('auto', $payload['google_format']);
    }
}
