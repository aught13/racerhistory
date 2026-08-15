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
        ]);

        $this->assertRedirect('/admin/site-options/edit');
        $this->assertFlashMessage('Site options have been saved.');

        $table = $this->getTableLocator()->get('SiteOptions');

        $registration = $table->find()->where(['option_key' => 'registration'])->first();
        $maintenance = $table->find()->where(['option_key' => 'site_maintenance'])->first();
        $recordsPerPage = $table->find()->where(['option_key' => 'records_per_page'])->first();
        $supportEmail = $table->find()->where(['option_key' => 'support_email'])->first();

        $this->assertNotNull($registration);
        $this->assertNotNull($maintenance);
        $this->assertNotNull($recordsPerPage);
        $this->assertNotNull($supportEmail);

        $this->assertSame('false', $registration->value);
        $this->assertSame('true', $maintenance->value);
        $this->assertSame('35', $recordsPerPage->value);
        $this->assertSame('support@racerhistory.local', $supportEmail->value);
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
}
