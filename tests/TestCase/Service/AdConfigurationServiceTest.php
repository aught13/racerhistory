<?php
declare(strict_types=1);

namespace App\Test\TestCase\Service;

use App\Model\Entity\Advertisement;
use App\Service\AdConfigurationService;
use App\Service\AdvertisingService;
use Cake\TestSuite\TestCase;

class AdConfigurationServiceTest extends TestCase
{
    /**
     * Empty slot names should return an inactive payload.
     */
    public function testGetSlotConfigurationReturnsInactiveForEmptySlot(): void
    {
        $service = new AdConfigurationService($this->createAdvertisingServiceMock([]));

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
        $service = new AdConfigurationService($this->createAdvertisingServiceMock([
            'ad_below_nav_active' => true,
            'ad_below_nav_html' => '<div class="ad-content">Custom Ad</div>',
            'ad_below_nav_google_mode' => false,
        ]));

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
        $service = new AdConfigurationService($this->createAdvertisingServiceMock([
            'ad_below_nav_active' => true,
            'ad_below_nav_google_mode' => true,
            'ad_below_nav_html' =>
                '<ins class="adsbygoogle" data-ad-client="ca-pub-123" data-ad-slot="9876543210" data-ad-format="auto" data-full-width-responsive="true"></ins>',
        ]));

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
        $service = new AdConfigurationService($this->createAdvertisingServiceMock([
            'ad_below_nav_active' => true,
            'ad_below_nav_google_mode' => false,
            'ad_below_nav_html' =>
                '<script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=ca-pub-123"></script>'
                . '<ins class="adsbygoogle" data-ad-client="ca-pub-123" data-ad-slot="9876543210"></ins>'
                . '<script>(adsbygoogle = window.adsbygoogle || []).push({});</script>',
        ]));

        $result = $service->getSlotConfiguration('below_nav');

        $this->assertSame('google', $result['mode']);
        $this->assertSame('9876543210', $result['google_slot_id']);
    }

    /**
     * Numeric publisher ids should be normalized to ca-pub-* when client is absent.
     */
    public function testGetSlotConfigurationNormalizesPublisherIdFallback(): void
    {
        $service = new AdConfigurationService($this->createAdvertisingServiceMock([
            'ad_below_nav_active' => true,
            'ad_below_nav_google_mode' => true,
            'ad_publisher_id' => '4154',
            'ad_below_nav_html' =>
                '<ins class="adsbygoogle" data-ad-slot="1234567890"></ins>',
        ]));

        $result = $service->getSlotConfiguration('below_nav');

        $this->assertSame('google', $result['mode']);
        $this->assertSame('ca-pub-4154', $result['google_client']);
    }

    /**
     * Google mode should safely fall back to custom when no numeric slot id exists.
     */
    public function testGetSlotConfigurationFallsBackWhenSlotIdMissing(): void
    {
        $service = new AdConfigurationService($this->createAdvertisingServiceMock([
            'ad_below_nav_active' => true,
            'ad_below_nav_google_mode' => true,
            'ad_below_nav_html' =>
                '<ins class="adsbygoogle" data-ad-client="ca-pub-123"></ins>',
        ]));

        $result = $service->getSlotConfiguration('below_nav');

        $this->assertSame('custom', $result['mode']);
        $this->assertSame('', $result['google_slot_id']);
        $this->assertStringContainsString('adsbygoogle', $result['html']);
    }

    /**
     * GPT mode should retain a string unit path and normalize responsive sizes.
     */
    public function testGetSlotConfigurationReturnsGptPayload(): void
    {
        $service = new AdConfigurationService($this->createAdvertisingServiceMock([
            'ad_below_nav_active' => true,
            'ad_below_nav_mode' => 'gpt',
            'ad_below_nav_gpt_unit_path' => '/123456/racerhistory/home',
            'ad_below_nav_sizes_desktop' => '[[970, 250], ["728", "90"], "bad"]',
            'ad_below_nav_sizes_mobile' => '["300x250", [0, 50]]',
        ]));

        $result = $service->getSlotConfiguration('below_nav');

        $this->assertTrue($result['active']);
        $this->assertSame('gpt', $result['mode']);
        $this->assertSame('/123456/racerhistory/home', $result['gpt_unit_path']);
        $this->assertSame([[970, 250], [728, 90]], $result['sizes_desktop']);
        $this->assertSame([[300, 250]], $result['sizes_mobile']);
        $this->assertSame('', $result['google_slot_id']);
    }

    /**
     * Human-entered comma-separated sizes should normalize without JSON input.
     */
    public function testGetSlotConfigurationParsesHumanSizePresets(): void
    {
        $service = new AdConfigurationService($this->createAdvertisingServiceMock([
            'ad_below_nav_active' => true,
            'ad_below_nav_mode' => 'gpt',
            'ad_below_nav_gpt_unit_path' => '/123456/racerhistory/home',
            'ad_below_nav_sizes_desktop' => '970x250, 728x90',
            'ad_below_nav_sizes_mobile' => '300x250',
        ]));

        $result = $service->getSlotConfiguration('below_nav');

        $this->assertSame([[970, 250], [728, 90]], $result['sizes_desktop']);
        $this->assertSame([[300, 250]], $result['sizes_mobile']);
    }

    /**
     * Empty GPT structural options should fall back to custom HTML safely.
     */
    public function testGetSlotConfigurationFallsBackWhenGptPathIsEmpty(): void
    {
        $service = new AdConfigurationService($this->createAdvertisingServiceMock([
            'ad_below_nav_active' => true,
            'ad_below_nav_mode' => 'gpt',
            'ad_below_nav_html' => '<div class="house-ad">House ad</div>',
            'ad_below_nav_sizes_desktop' => '',
            'ad_below_nav_sizes_mobile' => 'not-json',
        ]));

        $result = $service->getSlotConfiguration('below_nav');

        $this->assertSame('custom', $result['mode']);
        $this->assertSame([], $result['sizes_desktop']);
        $this->assertSame([], $result['sizes_mobile']);
        $this->assertStringContainsString('House ad', $result['html']);
    }

    /**
     * Multiple active campaigns in one placement produce multiple delivery payloads.
     */
    public function testGetSlotConfigurationsReturnsEveryCampaign(): void
    {
        $advertisements = [
            new Advertisement([
                'id' => 1,
                'placement' => 'below_nav',
                'is_active' => true,
                'mode' => 'custom',
                'html' => '<div>Site sponsor</div>',
            ]),
            new Advertisement([
                'id' => 2,
                'placement' => 'below_nav',
                'is_active' => true,
                'mode' => 'custom',
                'html' => '<div>Content sponsor</div>',
            ]),
        ];
        $advertisingService = $this->getMockBuilder(AdvertisingService::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getAdvertisementsForPlacement', 'getSettings'])
            ->getMock();
        $advertisingService->method('getAdvertisementsForPlacement')->willReturn($advertisements);
        $advertisingService->method('getSettings')->willReturn([
            'global_script' => '',
            'publisher_id' => '',
        ]);
        $service = new AdConfigurationService($advertisingService);

        $configurations = $service->getSlotConfigurations('below_nav', ['sportKey' => 'basketball']);

        $this->assertCount(2, $configurations);
        $this->assertStringContainsString('Site sponsor', $configurations[0]['html']);
        $this->assertStringContainsString('Content sponsor', $configurations[1]['html']);
    }

    /**
     * Global provider script settings are returned without Site Options.
     */
    public function testGetGlobalScript(): void
    {
        $advertisingService = $this->getMockBuilder(AdvertisingService::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getSettings'])
            ->getMock();
        $advertisingService->method('getSettings')->willReturn([
            'global_script' => '<script src="/provider.js"></script>',
            'publisher_id' => '',
        ]);
        $service = new AdConfigurationService($advertisingService);

        $this->assertSame('<script src="/provider.js"></script>', $service->getGlobalScript());
    }

    /**
     * @param array<string,mixed> $settings
     */
    private function createAdvertisingServiceMock(array $settings): AdvertisingService
    {
        $service = $this->getMockBuilder(AdvertisingService::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getAdvertisementsForPlacement', 'getSettings'])
            ->getMock();

        $mode = strtolower(trim((string)($settings['ad_below_nav_mode'] ?? '')));
        if ($mode === '' && !empty($settings['ad_below_nav_google_mode'])) {
            $mode = 'google';
        }
        $advertisement = new Advertisement([
            'id' => 1,
            'title' => 'Test advertisement',
            'placement' => 'below_nav',
            'target_type' => 'site',
            'is_active' => (bool)($settings['ad_below_nav_active'] ?? false),
            'html' => (string)($settings['ad_below_nav_html'] ?? ''),
            'mode' => $mode,
            'sizes_desktop' => $settings['ad_below_nav_sizes_desktop'] ?? null,
            'sizes_mobile' => $settings['ad_below_nav_sizes_mobile'] ?? null,
            'gpt_unit_path' => $settings['ad_below_nav_gpt_unit_path'] ?? null,
        ]);
        $service->method('getAdvertisementsForPlacement')->willReturn(
            $advertisement->is_active ? [$advertisement] : [],
        );
        $service->method('getSettings')->willReturn([
            'global_script' => '',
            'publisher_id' => (string)($settings['ad_publisher_id'] ?? ''),
        ]);

        return $service;
    }
}
