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
     * Resolve an invalid public scope to its closest valid fallback scope.
     *
     * @param array<string,mixed> $query
     * @return array{valid:bool,error:string|null,sportKey:string|null,teamId:int|null}
     */
    public function resolveSafe(array $query): array
    {
        return $this->publicSportScopeService->resolveSafe($query);
    }

    /**
     * @param array<string,mixed> $query
     * @return array{valid:bool,error:string|null,sportKey:string|null,teamId:int|null}
     */
    public function resolveGamesScope(array $query): array
    {
        return $this->publicSportScopeService->resolveGamesScope($query);
    }

    /**
     * @param array<string,mixed> $query
     * @return array{valid:bool,error:string|null,sportKey:string|null,teamId:int|null}
     */
    public function resolveStatsScope(array $query): array
    {
        return $this->publicSportScopeService->resolveStatsScope(
            $query,
            $this->statsService->getSupportedSports(),
        );
    }

    /**
     * Determine whether the public selector bar should be displayed.
     *
     * @param string $controller Current controller name.
     * @param string $action Current action name.
     * @param array<int|string,mixed> $pass Current route pass parameters.
     * @param array{valid:bool,error:string|null,sportKey:string|null,teamId:int|null} $scope Resolved public scope.
     * @return bool
     */
    public function shouldShowScopeBar(
        string $controller,
        string $action,
        array $pass,
        array $scope,
    ): bool {
        return $this->publicSportScopeService->shouldShowPublicScopeBar(
            $controller,
            $action,
            $pass,
            $scope,
        );
    }

    /**
     * Determine whether a real public sport or team is selected.
     *
     * @param array{valid:bool,error:string|null,sportKey:string|null,teamId:int|null} $scope Resolved public scope.
     * @return bool
     */
    public function hasScopeSelection(array $scope): bool
    {
        return $this->publicSportScopeService->hasPublicScopeSelection($scope);
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
