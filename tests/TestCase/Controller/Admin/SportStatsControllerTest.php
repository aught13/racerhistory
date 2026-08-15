<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller\Admin;

use App\Test\TestCase\Support\AuthTestTrait;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

/**
 * @link \App\Controller\Admin\SportStatsController
 */
class SportStatsControllerTest extends TestCase
{
    use IntegrationTestTrait;
    use AuthTestTrait;

    protected array $fixtures = [
        'app.Users',
        'app.Sports',
    ];

    /**
     * Tests legacy index redirects to SiteOptions sport-config page.
     */
    public function testIndexRedirectsToSiteOptionsSportsConfigs(): void
    {
        $this->mockIdentity();
        $this->get('/admin/sport-stats');

        $this->assertRedirect('/admin/site-options/sports-configs');
    }

    /**
     * Tests sport-filtered index redirects and preserves the route sport ref.
     */
    public function testIndexFiltersBySportId(): void
    {
        $this->mockIdentity();
        $this->get('/admin/sport-stats/1');

        $this->assertRedirect('/admin/site-options/sports-configs/1');
    }

    /**
     * Tests add route now redirects to SiteOptions sport-config editor.
     */
    public function testAddRouteRedirectsToSiteOptionsEditor(): void
    {
        $this->mockIdentity();

        $this->get('/admin/sport-stats/add/1');

        $this->assertRedirect('/admin/site-options/edit-sport-configs/1');
    }

    /**
     * Tests view route redirects with retirement warning message.
     */
    public function testViewRedirectsWithRetirementWarning(): void
    {
        $this->mockIdentity();
        $this->enableRetainFlashMessages();

        $this->get('/admin/sport-stats/view/1');

        $this->assertRedirect('/admin/site-options/sports-configs');
        $this->assertFlashMessage('Legacy stat registry entries are retired. Use sport configuration settings instead.');
    }

    /**
     * Tests edit route redirects with retirement warning message.
     */
    public function testEditRedirectsWithRetirementWarning(): void
    {
        $this->mockIdentity();
        $this->enableRetainFlashMessages();

        $this->get('/admin/sport-stats/edit/1');

        $this->assertRedirect('/admin/site-options/sports-configs');
        $this->assertFlashMessage('Legacy stat registry entries are retired. Use sport configuration settings instead.');
    }

    /**
     * Tests delete route redirects with success message.
     */
    public function testDeleteRedirectsWithSuccessMessage(): void
    {
        $this->mockIdentity();
        $this->enableCsrfToken();
        $this->enableSecurityToken();
        $this->enableRetainFlashMessages();

        $this->delete('/admin/sport-stats/delete/1');

        $this->assertRedirect('/admin/site-options/sports-configs');
        $this->assertFlashMessage('Legacy stat registry configuration is retired and no longer required.');
    }
}
