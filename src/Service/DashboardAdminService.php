<?php
declare(strict_types=1);

namespace App\Service;

use App\Model\Table\GamesTable;
use App\Model\Table\ImagesTable;
use App\Model\Table\TeamsTable;

class DashboardAdminService
{
    /**
     * Create the dashboard service with its data and cache dependencies.
     *
     * @param \App\Service\DeployAuditService $deployAuditService The deployment audit service.
     * @param \App\Service\SportConfigService $sportConfigService The sport configuration service.
     * @param \App\Model\Table\TeamsTable $teamsTable The teams table.
     * @param \App\Model\Table\GamesTable $gamesTable The games table.
     * @param \App\Model\Table\ImagesTable $imagesTable The images table.
     * @param \App\Service\CacheEngineService $cacheEngineService The cache engine service.
     */
    public function __construct(
        private DeployAuditService $deployAuditService,
        private SportConfigService $sportConfigService,
        private TeamsTable $teamsTable,
        private GamesTable $gamesTable,
        private ImagesTable $imagesTable,
        private CacheEngineService $cacheEngineService,
    ) {
    }

    /**
     * @return array{audit:array<string,mixed>,counts:array{sports:int,teams:int,games:int,images:int}}
     */
    public function dashboardData(): array
    {
        return [
            'audit' => $this->deployAuditService->run(),
            'counts' => [
                'sports' => count($this->sportConfigService->getAvailableSports()),
                'teams' => (int)$this->teamsTable->find()->count(),
                'games' => (int)$this->gamesTable->find()->count(),
                'images' => (int)$this->imagesTable->find()->count(),
            ],
        ];
    }

    /**
     * @return array{cleared:array<int,string>,failed:array<int,string>}
     */
    public function clearConfiguredCaches(): array
    {
        $cleared = [];
        $failed = [];

        foreach ($this->cacheEngineService->configured() as $name) {
            if ($this->cacheEngineService->clear($name)) {
                $cleared[] = $name;
            } else {
                $failed[] = $name;
            }
        }

        return compact('cleared', 'failed');
    }
}
