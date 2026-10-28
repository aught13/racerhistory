<?php
declare(strict_types=1);

namespace App\Test\TestCase\Service;

use App\Model\Table\GamesTable;
use App\Model\Table\ImagesTable;
use App\Model\Table\TeamsTable;
use App\Service\CacheEngineService;
use App\Service\DashboardAdminService;
use App\Service\DeployAuditService;
use App\Service\SportConfigService;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;
use RuntimeException;

class DashboardAdminServiceTest extends TestCase
{
    protected array $fixtures = [
        'app.Sports',
        'app.Teams',
        'app.Games',
        'app.GameTypes',
        'app.Opponents',
        'app.Places',
        'app.Sites',
        'app.Images',
    ];

    private DashboardAdminService $service;

    private DeployAuditService $deployAuditService;

    private SportConfigService $sportConfigService;

    private CacheEngineService $cacheEngineService;

    private TeamsTable $teamsTable;

    private GamesTable $gamesTable;

    private ImagesTable $imagesTable;

    /**
     * Initialize the dashboard service with fixture-backed tables and mocks.
     *
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->deployAuditService = $this->createMock(DeployAuditService::class);
        $this->sportConfigService = $this->createMock(SportConfigService::class);
        $this->cacheEngineService = $this->createMock(CacheEngineService::class);
        $teamsTable = TableRegistry::getTableLocator()->get('Teams');
        $gamesTable = TableRegistry::getTableLocator()->get('Games');
        $imagesTable = TableRegistry::getTableLocator()->get('Images');
        if (
            !$teamsTable instanceof TeamsTable
            || !$gamesTable instanceof GamesTable
            || !$imagesTable instanceof ImagesTable
        ) {
            throw new RuntimeException('Dashboard tables were not configured for this test.');
        }
        $this->teamsTable = $teamsTable;
        $this->gamesTable = $gamesTable;
        $this->imagesTable = $imagesTable;
        $this->service = new DashboardAdminService(
            $this->deployAuditService,
            $this->sportConfigService,
            $this->teamsTable,
            $this->gamesTable,
            $this->imagesTable,
            $this->cacheEngineService,
        );
    }

    /**
     * Verify dashboard data includes the audit and table counts.
     *
     * @return void
     */
    public function testDashboardDataReturnsAuditAndTableCounts(): void
    {
        $audit = ['overall' => 'ok'];
        $this->deployAuditService->expects($this->once())->method('run')->willReturn($audit);
        $this->sportConfigService->expects($this->once())
            ->method('getAvailableSports')
            ->willReturn(['basketball' => 'Basketball', 'football' => 'Football']);

        $data = $this->service->dashboardData();

        $this->assertSame($audit, $data['audit']);
        $this->assertSame([
            'sports' => 2,
            'teams' => $this->teamsTable->find()->count(),
            'games' => $this->gamesTable->find()->count(),
            'images' => $this->imagesTable->find()->count(),
        ], $data['counts']);
    }

    /**
     * Verify cache clearing separates successful and failed configurations.
     *
     * @return void
     */
    public function testClearConfiguredCachesSeparatesSuccessfulAndFailedEngines(): void
    {
        $this->cacheEngineService->expects($this->once())
            ->method('configured')
            ->willReturn(['default', 'session', 'failed']);
        $this->cacheEngineService->expects($this->exactly(3))
            ->method('clear')
            ->willReturnMap([
                ['default', true],
                ['session', true],
                ['failed', false],
            ]);

        $result = $this->service->clearConfiguredCaches();

        $this->assertSame(['default', 'session'], $result['cleared']);
        $this->assertSame(['failed'], $result['failed']);
    }
}
