<?php
declare(strict_types=1);

namespace App\Test\TestCase\View\Element;

use App\View\AppView;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;

class AdsBlockElementTest extends TestCase
{
    protected array $fixtures = [
        'app.Advertisements',
        'app.AdvertisingSettings',
        'app.Teams',
        'app.Sports',
    ];

    private AppView $view;

    /**
     * Prepare an AppView instance.
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->view = new AppView();
    }

    /**
     * Release the view after each test.
     */
    public function tearDown(): void
    {
        unset($this->view);
        parent::tearDown();
    }

    /**
     * Inactive slots should render nothing.
     */
    public function testElementReturnsEmptyWhenSlotInactive(): void
    {
        $this->replaceAdvertisements([
            [
                'title' => 'Inactive',
                'is_active' => false,
                'html' => '<div>Ad</div>',
            ],
        ]);

        $output = $this->view->element('Ads/block', ['slot' => 'below_nav']);

        $this->assertSame('', trim($output));
    }

    /**
     * Active non-Google slots should render expected section markup.
     */
    public function testElementRendersStandardSlotMarkup(): void
    {
        $this->replaceAdvertisements([
            [
                'title' => 'Custom',
                'is_active' => true,
                'html' => '<div class="ad-content">Ad</div>',
            ],
        ]);

        $output = $this->view->element('Ads/block', ['slot' => 'below_nav']);

        $this->assertStringContainsString('rh-ad-slot--below-nav', $output);
        $this->assertStringContainsString('data-controller="ad-delivery"', $output);
        $this->assertStringContainsString('data-google-mode="0"', $output);
        $this->assertStringContainsString('data-ad-delivery-mode-value="custom"', $output);
        $this->assertStringContainsString('data-ad-delivery-sizes-desktop-value="[]"', $output);
        $this->assertStringContainsString('data-ad-delivery-sizes-mobile-value="[]"', $output);
        $this->assertStringContainsString('<template data-ad-delivery-target="template"><div class="ad-content">Ad</div></template>', $output);
    }

    /**
     * Google mode should render slot classes without inline queue push script.
     */
    public function testGoogleModeRendersClassWithoutInlinePushScript(): void
    {
        $this->replaceAdvertisements([
            [
                'title' => 'AdSense',
                'is_active' => true,
                'mode' => 'google',
                'html' => '<ins class="adsbygoogle" data-ad-client="ca-pub-111" data-ad-slot="1234567890"></ins>',
            ],
        ]);

        $output = $this->view->element('Ads/block', ['slot' => 'below_nav']);

        $this->assertStringContainsString('rh-ad-slot--google', $output);
        $this->assertStringContainsString('data-google-mode="1"', $output);
        $this->assertStringContainsString('data-ad-delivery-mode-value="google"', $output);
        $this->assertStringContainsString('data-ad-delivery-google-slot-id-value="1234567890"', $output);
        $this->assertStringContainsString('data-ad-delivery-google-client-value="ca-pub-111"', $output);
        $this->assertStringNotContainsString('data-ad-delivery-target="template"', $output);
        $this->assertStringNotContainsString('(window.adsbygoogle = window.adsbygoogle || []).push({});', $output);
    }

    /**
     * GPT and custom payloads should expose persisted unit data to public templates.
     */
    public function testElementRendersGptPayloadAndExpectedSizes(): void
    {
        $this->replaceAdvertisements([
            [
                'title' => 'GPT',
                'is_active' => true,
                'mode' => 'gpt',
                'html' => '<div class="dummy-house-ad">Dummy house ad</div>',
                'sizes_desktop' => '970x250, 728x90',
                'sizes_mobile' => '320x50',
                'gpt_unit_path' => '/999999/racerhistory/dummy-below-nav',
            ],
        ]);

        $output = $this->view->element('Ads/block', ['slot' => 'below_nav']);

        $this->assertStringContainsString('data-ad-delivery-mode-value="gpt"', $output);
        $this->assertStringContainsString('data-ad-delivery-gpt-unit-path-value="/999999/racerhistory/dummy-below-nav"', $output);
        $this->assertStringContainsString('data-ad-delivery-sizes-desktop-value="[[970,250],[728,90]]"', $output);
        $this->assertStringContainsString('data-ad-delivery-sizes-mobile-value="[[320,50]]"', $output);
        $this->assertStringContainsString('Dummy house ad', $output);
    }

    /**
     * Content campaigns should render only when their sport matches the page context.
     */
    public function testElementRendersOnlyMatchingContentCampaigns(): void
    {
        $this->replaceAdvertisements([
            [
                'title' => 'Basketball sponsor',
                'target_type' => 'content',
                'sport_key' => 'basketball',
                'is_active' => true,
                'html' => '<div class="basketball-campaign">Basketball coverage</div>',
            ],
        ]);

        $basketballOutput = $this->view->element('Ads/block', [
            'slot' => 'below_nav',
            'context' => ['sportKey' => 'basketball'],
        ]);
        $footballOutput = $this->view->element('Ads/block', [
            'slot' => 'below_nav',
            'context' => ['sportKey' => 'football'],
        ]);

        $this->assertStringContainsString('Basketball coverage', $basketballOutput);
        $this->assertSame('', trim($footballOutput));
    }

    /**
     * Replace fixture campaigns with the test-specific records.
     *
     * @param array<int,array<string,mixed>> $advertisements Campaign values.
     */
    private function replaceAdvertisements(array $advertisements): void
    {
        /** @var \App\Model\Table\AdvertisementsTable $table */
        $table = TableRegistry::getTableLocator()->get('Advertisements');
        $table->deleteAll([]);

        foreach ($advertisements as $index => $data) {
            $table->saveOrFail($table->newEntity([
                'title' => $data['title'],
                'placement' => 'below_nav',
                'target_type' => $data['target_type'] ?? 'site',
                'sport_key' => $data['sport_key'] ?? null,
                'team_id' => $data['team_id'] ?? null,
                'is_active' => $data['is_active'],
                'html' => $data['html'],
                'mode' => $data['mode'] ?? 'custom',
                'sizes_desktop' => $data['sizes_desktop'] ?? null,
                'sizes_mobile' => $data['sizes_mobile'] ?? null,
                'gpt_unit_path' => $data['gpt_unit_path'] ?? null,
                'display_order' => $index,
            ]));
        }
    }
}
