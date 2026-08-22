<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller\Admin;

use App\Test\TestCase\Support\AuthTestTrait;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

/**
 * @link \App\Controller\Admin\SiteOptionsController
 */
class SiteOptionsControllerTest extends TestCase
{
    use IntegrationTestTrait;
    use AuthTestTrait;

    /**
     * @var array<string>
     */
    protected array $fixtures = [
        'app.Users',
        'app.SiteOptions',
    ];

    /**
     * Sets up per-test security token defaults.
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->enableCsrfToken();
        $this->enableSecurityToken();
    }

    /**
     * GET /admin/site-options/edit renders dynamic controls from definitions.
     */
    public function testEditGetRendersDynamicOptionsForm(): void
    {
        $this->mockIdentity();

        $this->get('/admin/site-options/edit');

        $this->assertResponseOk();
        $this->assertResponseContains('Site Options');
        $this->assertResponseContains('<turbo-frame id="site_options_frame">');
        $this->assertResponseContains('Enable User Registration');
        $this->assertResponseContains('Site Maintenance Mode');
        $this->assertResponseContains('Default Records Per Page');
        $this->assertResponseContains('System Support Email Address');
        $this->assertResponseContains('Ads - Global Script');
        $this->assertResponseContains('Ads - Below Nav (Display Ad): Active');
        $this->assertResponseContains('Ads - News Sidebar 2 (Multiplex Ad): Google AdSense Mode');
    }

    /**
     * Classic POST persists values and redirects back to edit.
     */
    public function testEditPostClassicRequestRedirectsAndPersistsValues(): void
    {
        $this->mockIdentity();
        $this->enableRetainFlashMessages();

        $this->post('/admin/site-options/edit', [
            'registration' => '0',
            'site_maintenance' => '1',
            'records_per_page' => '35',
            'support_email' => 'support@racerhistory.local',
            'ad_script' => '<script async src="https://example.com/ad.js"></script>',
            'ad_below_nav_active' => '1',
            'ad_below_nav_html' => '<div class="ad-slot">Top Banner</div>',
            'ad_below_nav_google_mode' => '1',
        ]);

        $this->assertRedirect('/admin/site-options/edit');
        $this->assertFlashMessage('Site options have been saved.');

        $table = $this->getTableLocator()->get('SiteOptions');

        $registration = $table->find()->where(['option_key' => 'registration'])->first();
        $maintenance = $table->find()->where(['option_key' => 'site_maintenance'])->first();
        $recordsPerPage = $table->find()->where(['option_key' => 'records_per_page'])->first();
        $supportEmail = $table->find()->where(['option_key' => 'support_email'])->first();
        $adScript = $table->find()->where(['option_key' => 'ad_script'])->first();
        $adBelowNavActive = $table->find()->where(['option_key' => 'ad_below_nav_active'])->first();
        $adBelowNavHtml = $table->find()->where(['option_key' => 'ad_below_nav_html'])->first();
        $adBelowNavGoogleMode = $table->find()->where(['option_key' => 'ad_below_nav_google_mode'])->first();

        $this->assertNotNull($registration);
        $this->assertNotNull($maintenance);
        $this->assertNotNull($recordsPerPage);
        $this->assertNotNull($supportEmail);
        $this->assertNotNull($adScript);
        $this->assertNotNull($adBelowNavActive);
        $this->assertNotNull($adBelowNavHtml);
        $this->assertNotNull($adBelowNavGoogleMode);

        $this->assertSame('false', $registration->value);
        $this->assertSame('true', $maintenance->value);
        $this->assertSame('35', $recordsPerPage->value);
        $this->assertSame('support@racerhistory.local', $supportEmail->value);
        $this->assertSame('<script async src="https://example.com/ad.js"></script>', $adScript->value);
        $this->assertSame('true', $adBelowNavActive->value);
        $this->assertSame('<div class="ad-slot">Top Banner</div>', $adBelowNavHtml->value);
        $this->assertSame('true', $adBelowNavGoogleMode->value);
    }

    /**
     * Omitted checkbox values fall back to configured defaults.
     */
    public function testEditPostFallsBackToDefaultsForMissingCheckboxPayloadKeys(): void
    {
        $this->mockIdentity();

        $table = $this->getTableLocator()->get('SiteOptions');
        $registration = $table->find()->where(['option_key' => 'registration'])->firstOrFail();
        $registration->value = 'false';
        $table->saveOrFail($registration);

        $this->post('/admin/site-options/edit', [
            'records_per_page' => '25',
            'support_email' => 'admin+site-options@example.com',
        ]);

        $this->assertRedirect('/admin/site-options/edit');

        $updatedRegistration = $table->find()->where(['option_key' => 'registration'])->first();
        $maintenance = $table->find()->where(['option_key' => 'site_maintenance'])->first();

        $this->assertNotNull($updatedRegistration);
        $this->assertNotNull($maintenance);
        $this->assertSame('true', $updatedRegistration->value);
        $this->assertSame('false', $maintenance->value);
    }

    /**
     * Writing ads.txt should create the file when publisher ID is configured.
     */
    public function testWriteAdsTxtCreatesFile(): void
    {
        $this->mockIdentity();
        $this->enableRetainFlashMessages();

        // Persist a publisher id via the edit screen
        $this->post('/admin/site-options/edit', [
            'ad_publisher_id' => 'ca-pub-1234567890',
        ]);

        $this->assertRedirect('/admin/site-options/edit');

        $adsPath = WWW_ROOT . 'ads.txt';
        if (file_exists($adsPath)) {
            unlink($adsPath);
        }

        $this->post('/admin/site-options/write-ads-txt');

        $this->assertRedirect('/admin/site-options/edit');
        $this->assertFlashMessage('ads.txt has been written to webroot.');

        $this->assertFileExists($adsPath);
        $contents = (string)file_get_contents($adsPath);
        $this->assertStringContainsString('ca-pub-1234567890', $contents);

        // Cleanup
        unlink($adsPath);
    }

    /**
     * Writing ads.txt without a configured publisher id should fail.
     */
    public function testWriteAdsTxtFailsWithoutPublisherId(): void
    {
        $this->mockIdentity();
        $this->enableRetainFlashMessages();

        // Ensure no publisher id present
        $table = $this->getTableLocator()->get('SiteOptions');
        $row = $table->find()->where(['option_key' => 'ad_publisher_id'])->first();
        if ($row) {
            $row->value = '';
            $table->saveOrFail($row);
        }

        // Clear runtime cache/config so controller reads fresh DB state
        \Cake\Cache\Cache::delete('global_site_options');
        \Cake\Core\Configure::delete('SiteOptions');

        $adsPath = WWW_ROOT . 'ads.txt';
        if (file_exists($adsPath)) {
            unlink($adsPath);
        }

        $this->post('/admin/site-options/write-ads-txt');

        $this->assertRedirect('/admin/site-options/edit');
        $this->assertFlashMessage('No Publisher ID configured.');
        $this->assertFileDoesNotExist($adsPath);
    }

    /**
     * GET edit should show Authorized message when ads.txt contains publisher id.
     */
    public function testEditGetShowsAuthorizedWhenAdsTxtContainsPublisherId(): void
    {
        $this->mockIdentity();

        // Persist a publisher id and enable a google-mode slot so the admin check runs
        $this->enableRetainFlashMessages();
        $this->post('/admin/site-options/edit', [
            'ad_publisher_id' => 'ca-pub-abcdef',
            'ad_below_nav_google_mode' => '1',
        ]);

        $adsPath = WWW_ROOT . 'ads.txt';
        file_put_contents($adsPath, "google.com, ca-pub-abcdef, DIRECT, f08c47fec0942fa0\n");

        $this->get('/admin/site-options/edit');

        $this->assertResponseOk();
        $this->assertResponseContains("Authorized: Your publisher ID was found in the site's ads.txt file.");

        unlink($adsPath);
    }
}
