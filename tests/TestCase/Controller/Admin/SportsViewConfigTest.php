<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller\Admin;

use App\Test\TestCase\Support\AuthTestTrait;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

/**
 * App\Controller\Admin\SportsController Test Case for view with configs
 */
class SportsViewConfigTest extends TestCase
{
    use IntegrationTestTrait;
    use AuthTestTrait;

    /**
     * Fixtures
     */
    protected array $fixtures = [
        'app.Sports',
        'app.SportConfigs',
        'app.Teams',
        'app.Users',
        'app.SiteOptions',
    ];

    /**
     * setUp method
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->mockIdentity(['role' => 'admin']);
    }

    /**
     * Test view method includes sport configurations
     */
    public function testViewIncludesConfigurations(): void
    {
        // Create a sport with some configurations
        $sportConfigs = $this->getTableLocator()->get('SportConfigs');
        $sportConfigs->setConfig(1, 'period_name_2', 'Half', 'Basketball halves');
        $sportConfigs->setConfig(1, 'period_name_4', 'Quarter', 'Basketball quarters');
        $sportConfigs->setConfig(1, 'officials', ['Referee 1', 'Referee 2'], 'Basketball officials');

        $this->get('/admin/sports/view/1');

        $this->assertResponseOk();
        $this->assertResponseContains('Sport Configurations');
        $this->assertResponseContains('Edit Configurations');
        $this->assertResponseContains('View All Configs');
        $this->assertResponseContains('Period Names');
        $this->assertResponseContains('Officials');
        $this->assertResponseContains('Half');
        $this->assertResponseContains('Quarter');
        $this->assertResponseContains('Referee 1');
    }

    /**
     * Test view method shows sport configurations section
     */
    public function testViewShowsConfigurationsSection(): void
    {
        $this->get('/admin/sports/view/1');

        $this->assertResponseOk();
        $this->assertResponseContains('Sport Configurations');
        $this->assertResponseContains('Edit Configurations');
        $this->assertResponseContains('View All Configs');
        // Check that configuration management buttons are present
        $this->assertResponseContains('btn-warning'); // Edit config button
        $this->assertResponseContains('btn-info'); // View config button
    }
}
