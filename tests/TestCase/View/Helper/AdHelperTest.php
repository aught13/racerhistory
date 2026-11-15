<?php
declare(strict_types=1);

namespace App\Test\TestCase\View\Helper;

use App\Service\AdConfigurationService;
use App\View\Helper\AdHelper;
use Cake\TestSuite\TestCase;
use Cake\View\View;

class AdHelperTest extends TestCase
{
    /**
     * Missing slots should return inactive payload defaults.
     */
    public function testSlotReturnsInactiveDefaultsWhenNotConfigured(): void
    {
        $helper = $this->createHelper([$this->configuration()]);
        $payload = $helper->slot('below_nav', ['sportKey' => 'basketball']);

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
        $helper = $this->createHelper([
            $this->configuration([
                'active' => true,
                'html' => '<div class="banner">Banner</div>',
            ]),
        ]);
        $payload = $helper->slot('below_nav', ['sportKey' => 'basketball']);

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
        $helper = $this->createHelper([
            $this->configuration([
                'active' => true,
                'mode' => 'google',
                'html' => '<ins class="adsbygoogle" data-ad-client="ca-pub-321" data-ad-slot="1234567890" data-ad-format="auto"></ins>',
                'google_slot_id' => '1234567890',
                'google_client' => 'ca-pub-321',
                'google_format' => 'auto',
            ]),
        ]);
        $payload = $helper->slot('below_nav', ['sportKey' => 'basketball']);

        $this->assertTrue($payload['active']);
        $this->assertSame('google', $payload['mode']);
        $this->assertTrue($payload['is_google']);
        $this->assertSame('1234567890', $payload['google_slot_id']);
        $this->assertSame('ca-pub-321', $payload['google_client']);
        $this->assertSame('auto', $payload['google_format']);
    }

    /**
     * Every matching campaign should be represented as a separate slot payload.
     */
    public function testSlotsReturnsAllCampaignPayloads(): void
    {
        $helper = $this->createHelper([
            $this->configuration(['active' => true, 'html' => '<div>Site sponsor</div>']),
            $this->configuration(['active' => true, 'html' => '<div>Content sponsor</div>']),
        ]);

        $payloads = $helper->slots('below_nav', ['sportKey' => 'basketball']);

        $this->assertCount(2, $payloads);
        $this->assertSame('below-nav', $payloads[0]['slot_class']);
        $this->assertStringContainsString('Site sponsor', $payloads[0]['html']);
        $this->assertStringContainsString('Content sponsor', $payloads[1]['html']);
    }

    /**
     * The view helper returns the separately configured global provider script.
     */
    public function testGlobalScriptReturnsProviderScript(): void
    {
        $helper = $this->createHelper([], '<script src="/provider.js"></script>');

        $this->assertSame('<script src="/provider.js"></script>', $helper->globalScript());
    }

    /**
     * Placements without an explicit context use sport/team view variables.
     */
    public function testSlotsResolvePageContextFromViewVariables(): void
    {
        $configuration = $this->configuration(['active' => true, 'html' => '<div>Scoped</div>']);
        $service = $this->getMockBuilder(AdConfigurationService::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getSlotConfigurations', 'resolvePageContext'])
            ->getMock();
        $service->expects($this->once())
            ->method('resolvePageContext')
            ->with($this->callback(
                static fn(array $viewVars): bool => $viewVars['publicScope'] === [
                    'sportKey' => 'basketball',
                    'teamId' => 1,
                ],
            ))
            ->willReturn(['sportKey' => 'basketball', 'teamId' => 1]);
        $service->expects($this->once())
            ->method('getSlotConfigurations')
            ->with('below_nav', ['sportKey' => 'basketball', 'teamId' => 1])
            ->willReturn([$configuration]);
        $view = new View();
        $view->set('publicScope', ['sportKey' => 'basketball', 'teamId' => 1]);
        $helper = new AdHelper($view, ['adConfigurationService' => $service]);

        $this->assertCount(1, $helper->slots('below_nav'));
    }

    /**
     * @param array<int,array<string,mixed>> $configurations
     * @param string $globalScript Provider loader script.
     */
    private function createHelper(array $configurations, string $globalScript = ''): AdHelper
    {
        $service = $this->getMockBuilder(AdConfigurationService::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getSlotConfiguration', 'getSlotConfigurations', 'getGlobalScript', 'resolvePageContext'])
            ->getMock();
        $service->method('getSlotConfiguration')->willReturn(
            $configurations[0] ?? $this->configuration(),
        );
        $service->method('getSlotConfigurations')->willReturn($configurations);
        $service->method('resolvePageContext')->willReturn([
            'sportKey' => null,
            'teamId' => null,
        ]);
        $service->method('getGlobalScript')->willReturn($globalScript);

        return new AdHelper(new View(), ['adConfigurationService' => $service]);
    }

    /**
     * @param array<string,mixed> $overrides
     * @return array<string,mixed>
     */
    private function configuration(array $overrides = []): array
    {
        return array_replace([
            'slot' => 'below_nav',
            'active' => false,
            'mode' => 'custom',
            'html' => '',
            'sizes_desktop' => [],
            'sizes_mobile' => [],
            'gpt_unit_path' => '',
            'google_slot_id' => '',
            'google_client' => '',
            'google_format' => '',
            'google_layout' => '',
            'google_layout_key' => '',
            'google_full_width_responsive' => '',
        ], $overrides);
    }
}
