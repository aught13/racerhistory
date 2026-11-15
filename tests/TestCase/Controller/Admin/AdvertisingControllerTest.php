<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller\Admin;

use App\Test\TestCase\Support\AuthTestTrait;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

class AdvertisingControllerTest extends TestCase
{
    use IntegrationTestTrait;
    use AuthTestTrait;

    /**
     * @var array<string>
     */
    protected array $fixtures = [
        'app.Users',
        'app.Advertisements',
        'app.AdvertisingSettings',
        'app.Teams',
        'app.Sports',
    ];

    /**
     * Enable form protection for admin POSTs.
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->enableCsrfToken();
        $this->enableSecurityToken();
    }

    /**
     * The dedicated advertising page contains provider settings and campaigns.
     */
    public function testIndexRendersAdvertisingArea(): void
    {
        $this->mockIdentity();

        $this->get('/admin/advertising');

        $this->assertResponseOk();
        $this->assertResponseContains('Provider Settings');
        $this->assertResponseContains('Global provider script');
        $this->assertResponseContains('ca-pub-1234567890123456');
        $this->assertResponseContains('Site-wide sponsor');
        $this->assertResponseContains('Content sponsor');
        $this->assertResponseNotContains('ad_below_nav_active');
    }

    /**
     * The campaign editor exposes each XenForo-style criterion with completion help.
     */
    public function testAddFormRendersTargetingAndDeliveryControls(): void
    {
        $this->mockIdentity();

        $this->get('/admin/advertising/add');

        $this->assertResponseOk();
        $this->assertResponseContains('Position');
        $this->assertResponseContains('Audience');
        $this->assertResponseContains('Sport filter');
        $this->assertResponseContains('Team filter');
        $this->assertResponseContains('Display order');
        $this->assertResponseContains('Do not enter JSON');
        $this->assertResponseContains('Choose one team to show this campaign on pages about that team');
    }

    /**
     * A content campaign saves with its sport and team selection.
     */
    public function testAddSavesContentSponsor(): void
    {
        $this->mockIdentity();
        $this->enableRetainFlashMessages();

        $this->post('/admin/advertising/add', [
            'title' => 'Women basketball coverage',
            'placement' => 'below_nav',
            'target_type' => 'content',
            'sport_key' => 'basketball',
            'team_id' => '1',
            'is_active' => '1',
            'mode' => 'custom',
            'html' => '<div class="wbb-sponsor">Basketball coverage</div>',
            'sizes_desktop' => '',
            'sizes_mobile' => '',
            'gpt_unit_path' => '',
            'display_order' => '5',
        ]);

        $this->assertRedirect('/admin/advertising');
        $this->assertFlashMessage('The advertisement has been saved.');

        $saved = $this->getTableLocator()
            ->get('Advertisements')
            ->find()
            ->where(['title' => 'Women basketball coverage'])
            ->first();

        $this->assertNotNull($saved);
        $this->assertSame('content', $saved->target_type);
        $this->assertSame('basketball', $saved->sport_key);
        $this->assertSame(1, (int)$saved->team_id);
        $this->assertSame(5, (int)$saved->display_order);
    }

    /**
     * A content campaign without sport or team criteria stays on the form with an error.
     */
    public function testAddRejectsUnfilteredContentSponsor(): void
    {
        $this->mockIdentity();
        $this->enableRetainFlashMessages();

        $this->post('/admin/advertising/add', [
            'title' => 'Missing filters',
            'placement' => 'below_nav',
            'target_type' => 'content',
            'sport_key' => '',
            'team_id' => '',
            'is_active' => '0',
            'mode' => 'custom',
            'html' => '',
            'display_order' => '0',
        ]);

        $this->assertResponseOk();
        $this->assertResponseContains('Choose a sport, a team, or both for a content sponsor.');
        $this->assertFlashMessage('The advertisement could not be saved. Please correct the errors and try again.');
    }

    /**
     * Editing a campaign as site-wide clears any previously saved content filters.
     */
    public function testEditSiteSponsorClearsContentFilters(): void
    {
        $this->mockIdentity();

        $this->post('/admin/advertising/edit/2', [
            'title' => 'Now global',
            'placement' => 'below_nav',
            'target_type' => 'site',
            'sport_key' => 'basketball',
            'team_id' => '1',
            'is_active' => '1',
            'mode' => 'custom',
            'html' => '<div>Global now</div>',
            'sizes_desktop' => '',
            'sizes_mobile' => '',
            'gpt_unit_path' => '',
            'display_order' => '1',
        ]);

        $this->assertRedirect('/admin/advertising/edit/2');
        $saved = $this->getTableLocator()->get('Advertisements')->get(2);
        $this->assertSame('site', $saved->target_type);
        $this->assertNull($saved->sport_key);
        $this->assertNull($saved->team_id);
    }

    /**
     * Provider settings are saved in their own store.
     */
    public function testSaveProviderSettings(): void
    {
        $this->mockIdentity();

        $this->post('/admin/advertising/save-settings', [
            'global_script' => '<script src="/provider.js"></script>',
            'publisher_id' => 'ca-pub-9876543210',
        ]);

        $this->assertRedirect('/admin/advertising');
        $settings = $this->getTableLocator()->get('AdvertisingSettings')->find()->where(['id' => 1])->first();
        $this->assertNotNull($settings);
        $this->assertSame('<script src="/provider.js"></script>', $settings->global_script);
        $this->assertSame('ca-pub-9876543210', $settings->publisher_id);
    }

    /**
     * Deleting an advertisement is a guarded POST operation.
     */
    public function testDeleteCampaign(): void
    {
        $this->mockIdentity();

        $this->post('/admin/advertising/delete/1');

        $this->assertRedirect('/admin/advertising');
        $this->assertNull(
            $this->getTableLocator()->get('Advertisements')->find()->where(['id' => 1])->first(),
        );
    }

    /**
     * The old ads.txt URL now runs through the dedicated advertising area.
     */
    public function testLegacyAdsTxtRouteReportsMissingPublisherId(): void
    {
        $this->mockIdentity();
        $this->enableRetainFlashMessages();

        $this->post('/admin/site-options/write-ads-txt');

        $this->assertRedirect('/admin/advertising');
        $this->assertFlashMessage('No Publisher ID configured.');
    }
}
