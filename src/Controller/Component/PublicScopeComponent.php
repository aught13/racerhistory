<?php
declare(strict_types=1);

namespace App\Controller\Component;

use App\Service\PublicSportScopeService;
use App\Service\StatsService;
use Cake\Controller\Component;
use Cake\Controller\ComponentRegistry;

class PublicScopeComponent extends Component
{
    /**
     * Create the component with the services used to resolve public scope.
     *
     * @param \Cake\Controller\ComponentRegistry $registry The component registry.
     * @param \App\Service\PublicSportScopeService $publicSportScopeService The public sport scope service.
     * @param \App\Service\StatsService $statsService The statistics service.
     * @param array<string,mixed> $config The component configuration.
     */
    public function __construct(
        ComponentRegistry $registry,
        private PublicSportScopeService $publicSportScopeService,
        private StatsService $statsService,
        array $config = [],
    ) {
        parent::__construct($registry, $config);
    }

    /**
     * @param array<string,mixed> $query
     * @return array{valid:bool,error:string|null,sportKey:string|null,teamId:int|null}
     */
    public function resolve(array $query): array
    {
        return $this->publicSportScopeService->resolve($query);
    }

    /**
     * @return array<string,string>
     */
    public function getAvailableSportOptions(): array
    {
        return $this->publicSportScopeService->getAvailableSportOptions();
    }

    /**
     * @param string|null $sportKey The selected sport key.
     * @return array<int,array{label:string,sportKey:string}>
     */
    public function getTeamOptions(?string $sportKey): array
    {
        return $this->publicSportScopeService->getTeamOptions($sportKey);
    }

    /**
     * @return array<string>
     */
    public function getSupportedStatSports(): array
    {
        return $this->statsService->getSupportedSports();
    }
}
