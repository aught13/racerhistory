<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller\Admin;

use App\Test\TestCase\Support\AuthTestTrait;
use Cake\Cache\Cache;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

/**
 * Legacy Sports URL compatibility tests.
 *
 * @link \App\Controller\Admin\SportsController
 */
class SportsControllerTest extends TestCase
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
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->enableCsrfToken();
        $this->enableSecurityToken();
        Cache::clear('default');
    }

    /**
     * Unauthenticated users should be redirected to login.
     */
    public function testLegacyIndexUnauthenticatedRedirectsToLogin(): void
    {
        $this->get('/admin/sports');
        $this->assertRedirect();
        $this->assertRedirectContains('/users/login');
    }

    /**
     * Legacy index route resolves to SiteOptions sport configs page.
     */
    public function testLegacyIndexRouteRendersSportConfigs(): void
    {
        $this->mockIdentity();

        $this->get('/admin/sports');
        $this->assertResponseOk();
        $this->assertResponseContains('Sport Configurations');
    }

    /**
     * Legacy /view route resolves to SiteOptions sport configs page.
     */
    public function testLegacyViewRouteRendersSportConfigs(): void
    {
        $this->mockIdentity();

        $this->get('/admin/sports/view/1');
        $this->assertResponseOk();
        $this->assertResponseContains('Sport Configurations');
        $this->assertResponseContains('Officials');
    }

    /**
     * Legacy /edit-configs route resolves to SiteOptions editor.
     */
    public function testLegacyEditConfigsRouteRendersEditor(): void
    {
        $this->mockIdentity();

        $this->get('/admin/sports/edit-configs/1');
        $this->assertResponseOk();
        $this->assertResponseContains('Edit Sport Configurations');
        $this->assertResponseContains('data-controller="sports-configs-form"');
    }

    /**
     * Legacy edit-configs POST should save and redirect to canonical SiteOptions route.
     */
    public function testLegacyEditConfigsPostSavesViaSiteOptions(): void
    {
        $this->mockIdentity();

        $this->post('/admin/sports/edit-configs/1', [
            'configs' => [
                'officials' => [
                    'value' => 'Referee, Umpire',
                    'description' => 'Updated officials',
                ],
            ],
        ]);

        $this->assertRedirectContains('/admin/site-options/sports-configs/');
        $this->assertFlashMessage('Sport configurations have been updated.');
    }

    /**
     * Legacy add-config validation should still behave correctly via mapped SiteOptions action.
     */
    public function testLegacyAddConfigValidatesKey(): void
    {
        $this->mockIdentity();

        $this->post('/admin/sports/add-config/1', [
            'config_key' => '',
            'config_value' => 'ignored',
            'description' => 'missing key',
        ]);

        $this->assertRedirectContains('/admin/site-options/edit-sport-configs/');
        $this->assertFlashMessage('Configuration key is required.');
    }

    /**
     * Legacy reset route should still reset defaults via mapped SiteOptions action.
     */
    public function testLegacyResetConfigsRouteStillWorks(): void
    {
        $this->mockIdentity();

        $this->post('/admin/sports/reset-configs/1');

        $this->assertRedirectContains('/admin/site-options/edit-sport-configs/');
        $this->assertFlashMessage('Sport configurations have been reset to defaults.');
    }

    /**
     * Deprecated sport add form route should redirect with guidance.
     */
    public function testDeprecatedAddRouteRedirectsWithWarning(): void
    {
        $this->mockIdentity();

        $this->get('/admin/sports/add');
        $this->assertRedirectContains('/admin/site-options/sports-configs');
        $this->assertFlashMessage('Sport CRUD has been retired. Manage sport behavior via Sport Configs.');
    }

    /**
     * Deprecated sport delete route should not delete and should redirect with guidance.
     */
    public function testDeprecatedDeleteRouteRedirectsWithWarning(): void
    {
        $this->mockIdentity();

        $this->post('/admin/sports/delete/1');
        $this->assertRedirectContains('/admin/site-options/sports-configs');
        $this->assertFlashMessage('Sport records are retired and can no longer be deleted.');
    }

    /**
     * Deprecated popup endpoint returns JSON error for GET.
     */
    public function testAjaxAddGetReturnsInvalidMethodJson(): void
    {
        $this->mockIdentity();

        $this->get('/admin/sports/ajaxAdd');
        $this->assertResponseOk();
        $this->assertContentType('application/json');

        $response = json_decode((string)$this->_response->getBody(), true);
        $this->assertFalse($response['success']);
        $this->assertContains('Invalid request method.', $response['errors']);
    }

    /**
     * Deprecated popup endpoint returns retirement guidance for POST.
     */
    public function testAjaxAddPostReturnsRetiredJsonError(): void
    {
        $this->mockIdentity();

        $this->post('/admin/sports/ajaxAdd', ['sport_name' => 'Soccer']);
        $this->assertResponseOk();
        $this->assertContentType('application/json');

        $response = json_decode((string)$this->_response->getBody(), true);
        $this->assertFalse($response['success']);
        $this->assertContains(
            'Sport creation has been retired. Use Site Options > Sport Configs and SportsDefaults.',
            $response['errors'],
        );
    }

    /**
     * Ensure non-delete bulk actions show a retirement warning and redirect.
     *
     * @return void
     */
    public function testBulkActionNonDeleteShowsWarning(): void
    {
        $this->mockIdentity();
        $this->enableRetainFlashMessages();

        $this->post('/admin/sports/bulk', ['bulk_action' => 'archive']);

        $this->assertRedirectContains('/admin/site-options/sports-configs');
        $this->assertFlashMessage('Sport records are retired and bulk actions are no longer available.');
    }
}
