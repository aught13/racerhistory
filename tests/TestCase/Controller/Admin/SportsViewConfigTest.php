<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller\Admin;

use App\Test\TestCase\Support\AuthTestTrait;
use Cake\Cache\Cache;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

/**
 * Coverage for legacy Sports config view URLs.
 */
class SportsViewConfigTest extends TestCase
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
        $this->mockIdentity(['role' => 'admin']);
        Cache::clear('default');
    }

    /**
     * Legacy /admin/sports/view/:ref should render the SiteOptions-backed config page.
     */
    public function testLegacyViewRouteShowsConfigScreen(): void
    {
        $this->get('/admin/sports/view/1');

        $this->assertResponseOk();
        $this->assertResponseContains('Sport Configurations');
        $this->assertResponseContains('Edit Configurations');
        $this->assertResponseContains('Officials');
    }

    /**
     * Legacy view should expose sport switcher buttons from the configured sport catalog.
     */
    public function testLegacyViewRouteShowsSportSwitcher(): void
    {
        $this->get('/admin/sports/view/1');

        $this->assertResponseOk();
        $this->assertResponseContains('Basketball');
        $this->assertResponseContains('Football');
        $this->assertResponseContains('Baseball');
        $this->assertResponseContains('btn-outline-primary');
    }
}
