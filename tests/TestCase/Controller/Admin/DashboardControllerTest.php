<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller\Admin;

use App\Test\TestCase\Support\AuthTestTrait;
use Cake\Cache\Cache;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

/**
 * @link \App\Controller\Admin\DashboardController
 */
class DashboardControllerTest extends TestCase
{
    use IntegrationTestTrait;
    use AuthTestTrait;

    /**
     * Fixtures
     *
     * @var array<string>
     */
    protected array $fixtures = [
        'app.Users',
    ];

    /**
     * setUp method
     *
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->mockIdentity();
        $this->enableCsrfToken();
        $this->enableSecurityToken();
    }

    /**
     * Tests index.
     */
    public function testIndex(): void
    {
        $this->get('/admin');
        $this->assertResponseOk();
        $this->assertResponseContains('Dashboard');
    }

    /**
     * Test dashboard shows health check accordion.
     */
    public function testIndexContainsHealthChecks(): void
    {
        $this->get('/admin');
        $this->assertResponseOk();
        $this->assertResponseContains('System Health');
        $this->assertResponseContains('healthAccordion');
        $this->assertResponseContains('PHP Version');
    }

    /**
     * Test dashboard shows quick actions section.
     */
    public function testIndexContainsQuickActions(): void
    {
        $this->get('/admin');
        $this->assertResponseOk();
        $this->assertResponseContains('Quick Actions');
        $this->assertResponseContains('Clear CakePHP Cache');
        $this->assertResponseContains('clear-cache-form');
    }

    /**
     * Test cache clear action via POST.
     */
    public function testClearCachePost(): void
    {
        $this->post('/admin/dashboard/clear-cache');
        $this->assertResponseCode(302);
        $this->assertRedirectContains('/admin');
    }

    /**
     * Test cache clear sets flash message.
     */
    public function testClearCacheFlashMessage(): void
    {
        $this->enableRetainFlashMessages();
        $this->post('/admin/dashboard/clear-cache');
        $this->assertRedirectContains('/admin');
        $this->assertFlashMessageContains('Cache cleared');
    }

    /**
     * Test cache clear rejects GET requests.
     */
    public function testClearCacheRejectsGet(): void
    {
        $this->get('/admin/dashboard/clear-cache');
        $this->assertResponseCode(405);
    }

    /**
     * Test unauthenticated access redirects to login.
     */
    public function testIndexRequiresAuth(): void
    {
        // Clear the pre-configured session from setUp
        $this->_session = [];
        $this->configRequest(['headers' => []]);
        $this->get('/admin');
        $this->assertRedirectContains('/login');
    }

    /**
     * Test that configured cache engines actually get cleared.
     */
    public function testClearCacheClearsAllEngines(): void
    {
        // Write something to the default cache
        Cache::write('dashboard_test_key', 'test_value', 'default');
        $this->assertSame('test_value', Cache::read('dashboard_test_key', 'default'));

        $this->enableCsrfToken();
        $this->enableSecurityToken();
        $this->post('/admin/dashboard/clear-cache');

        // Cache should be cleared
        $this->assertNull(Cache::read('dashboard_test_key', 'default'));
    }

    /**
     * Test admin layout includes Turbo meta tags.
     */
    public function testLayoutContainsTurboMeta(): void
    {
        $this->get('/admin');
        $this->assertResponseOk();
        $this->assertResponseContains('turbo-refresh-method');
        $this->assertResponseContains('turbo-refresh-scroll');
    }

    /**
     * Test admin layout no longer emits legacy importmap markers.
     */
    public function testLayoutOmitsLegacyImportmapMarkers(): void
    {
        $this->get('/admin');
        $this->assertResponseOk();
        $this->assertResponseNotContains('@hotwired/turbo');
    }

    /**
     * Test admin layout uses main runtime entry and omits legacy admin-turbo module.
     */
    public function testLayoutUsesMainRuntimeScript(): void
    {
        $this->get('/admin');
        $this->assertResponseOk();

        $body = (string)$this->_response->getBody();
        $this->assertMatchesRegularExpression(
            '/(?:\/dist\/assets\/main-[^"\']+\.js|\/js\/main\.js)/',
            $body,
        );

        $this->assertResponseNotContains('admin-turbo.mjs');
    }

    /**
     * Test admin layout wraps content in turbo-frame.
     */
    public function testLayoutContainsTurboFrame(): void
    {
        $this->get('/admin');
        $this->assertResponseOk();
        $this->assertResponseContains('<turbo-frame id="admin-content"');
        $this->assertResponseContains('</turbo-frame>');
    }

    /**
     * Test admin nav links target the admin-content turbo-frame.
     */
    public function testNavLinksTargetTurboFrame(): void
    {
        $this->get('/admin');
        $this->assertResponseOk();
        $this->assertResponseContains('data-turbo-frame="admin-content"');
    }

    /**
     * Test admin nav has data-turbo-permanent attribute for persistence.
     */
    public function testNavHasTurboPermanent(): void
    {
        $this->get('/admin');
        $this->assertResponseOk();
        $this->assertResponseContains('data-turbo-permanent');
    }
}
