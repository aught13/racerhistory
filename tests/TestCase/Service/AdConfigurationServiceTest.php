<?php
declare(strict_types=1);

namespace App\Test\TestCase\Service;

use App\Service\AdConfigurationService;
use App\Service\SiteOptionsService;
use Cake\TestSuite\TestCase;

class AdConfigurationServiceTest extends TestCase
{
    /**
     * Empty slot names should return an inactive payload.
     */
    public function testGetSlotConfigurationReturnsInactiveForEmptySlot(): void
    {
        $siteOptionsService = $this->createSiteOptionsServiceMock([]);
        $service = new AdConfigurationService($siteOptionsService);

        $result = $service->getSlotConfiguration('');

        $this->assertFalse($result['active']);
        $this->assertSame('', $result['slot']);
        $this->assertSame('custom', $result['mode']);
    }

    /**
     * Active custom mode slots should retain raw html and custom mode.
     */
    public function testGetSlotConfigurationReturnsCustomModePayload(): void
    {
        $siteOptionsService = $this->createSiteOptionsServiceMock([
            'ad_below_nav_active' => true,
            'ad_below_nav_html' => '<div class="ad-content">Custom Ad</div>',
            'ad_below_nav_google_mode' => false,
        ]);
        $service = new AdConfigurationService($siteOptionsService);

        $result = $service->getSlotConfiguration('below_nav');

        $this->assertTrue($result['active']);
        $this->assertSame('below_nav', $result['slot']);
        $this->assertSame('custom', $result['mode']);
        $this->assertStringContainsString('Custom Ad', $result['html']);
        $this->assertSame('', $result['google_slot_id']);
    }

    /**
     * Google mode slots should parse google metadata from slot html.
     */
    public function testGetSlotConfigurationParsesGoogleAttributes(): void
    {
        $siteOptionsService = $this->createSiteOptionsServiceMock([
            'ad_below_nav_active' => true,
            'ad_below_nav_google_mode' => true,
            'ad_below_nav_html' =>
                '<ins class="adsbygoogle" data-ad-client="ca-pub-123" data-ad-slot="9876543210" data-ad-format="auto" data-full-width-responsive="true"></ins>',
        ]);
        $service = new AdConfigurationService($siteOptionsService);

        $result = $service->getSlotConfiguration('below_nav');

        $this->assertTrue($result['active']);
        $this->assertSame('google', $result['mode']);
        $this->assertSame('9876543210', $result['google_slot_id']);
        $this->assertSame('ca-pub-123', $result['google_client']);
        $this->assertSame('auto', $result['google_format']);
        $this->assertSame('true', $result['google_full_width_responsive']);
    }

    /**
     * Legacy AdSense snippets should use the managed Google rendering path.
     */
    public function testGetSlotConfigurationDetectsLegacyGoogleMarkup(): void
    {
        $siteOptionsService = $this->createSiteOptionsServiceMock([
            'ad_below_nav_active' => true,
            'ad_below_nav_google_mode' => false,
            'ad_below_nav_html' =>
                '<script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=ca-pub-123"></script>'
                . '<ins class="adsbygoogle" data-ad-client="ca-pub-123" data-ad-slot="9876543210"></ins>'
                . '<script>(adsbygoogle = window.adsbygoogle || []).push({});</script>',
        ]);
        $service = new AdConfigurationService($siteOptionsService);

        $result = $service->getSlotConfiguration('below_nav');

        $this->assertSame('google', $result['mode']);
        $this->assertSame('9876543210', $result['google_slot_id']);
    }

    /**
     * Numeric publisher ids should be normalized to ca-pub-* when client is absent.
     */
    public function testGetSlotConfigurationNormalizesPublisherIdFallback(): void
    {
        $siteOptionsService = $this->createSiteOptionsServiceMock([
            'ad_below_nav_active' => true,
            'ad_below_nav_google_mode' => true,
            'ad_publisher_id' => '4154',
            'ad_below_nav_html' =>
                '<ins class="adsbygoogle" data-ad-slot="1234567890"></ins>',
        ]);
        $service = new AdConfigurationService($siteOptionsService);

        $result = $service->getSlotConfiguration('below_nav');

        $this->assertSame('google', $result['mode']);
        $this->assertSame('ca-pub-4154', $result['google_client']);
    }

    /**
     * Google mode should safely fall back to custom when no numeric slot id exists.
     */
    public function testGetSlotConfigurationFallsBackWhenSlotIdMissing(): void
    {
        $siteOptionsService = $this->createSiteOptionsServiceMock([
            'ad_below_nav_active' => true,
            'ad_below_nav_google_mode' => true,
            'ad_below_nav_html' =>
                '<ins class="adsbygoogle" data-ad-client="ca-pub-123"></ins>',
        ]);
        $service = new AdConfigurationService($siteOptionsService);

        $result = $service->getSlotConfiguration('below_nav');

        $this->assertSame('custom', $result['mode']);
        $this->assertSame('', $result['google_slot_id']);
        $this->assertStringContainsString('adsbygoogle', $result['html']);
    }

    /**
     * @param array<string,mixed> $settings
     */
    private function createSiteOptionsServiceMock(array $settings): SiteOptionsService
    {
        $siteOptionsService = $this->getMockBuilder(SiteOptionsService::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getRuntimeSettings'])
            ->getMock();
        $siteOptionsService->method('getRuntimeSettings')->willReturn($settings);

        return $siteOptionsService;
    }
}
