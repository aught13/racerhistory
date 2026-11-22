<?php
declare(strict_types=1);

namespace App\Service;

use App\Model\Entity\Team;
use App\Model\Table\TeamsTable;
use Cake\ORM\TableRegistry;

/**
 * Resolves public sport/team browse scope and selector options.
 */
class PublicSportScopeService
{
    private TeamsTable $teamsTable;

    private TeamSportContextService $teamSportContextService;

    /**
     * @var array<int,\App\Model\Entity\Team>|null
     */
    private ?array $configuredTeamsCache = null;

    /**
     * @param \App\Model\Table\TeamsTable|null $teamsTable Teams table.
     * @param \App\Service\TeamSportContextService|null $teamSportContextService Team sport resolver.
     */
    public function __construct(
        ?TeamsTable $teamsTable = null,
        ?TeamSportContextService $teamSportContextService = null,
    ) {
        /** @var \App\Model\Table\TeamsTable $resolvedTeamsTable */
        $resolvedTeamsTable = $teamsTable ?? TableRegistry::getTableLocator()->get('Teams');
        $this->teamsTable = $resolvedTeamsTable;
        $this->teamSportContextService = $teamSportContextService ?? new TeamSportContextService();
    }

    /**
     * Resolve and validate the public `sport` and `team` query parameters.
     *
     * Invalid selections must be handled as invalid, not interpreted as all/all.
     *
     * @param array<string,mixed> $query Query parameters.
     * @return array{valid:bool,error:string|null,sportKey:string|null,teamId:int|null}
     */
    public function resolve(array $query): array
    {
        $teams = $this->getConfiguredTeams();
        $sportOptions = $this->buildAvailableSportOptions($teams);

        $rawSport = $query['sport'] ?? 'all';
        if (!is_scalar($rawSport)) {
            return $this->invalidScope('invalid_sport');
        }

        $sportValue = trim((string)$rawSport);
        $sportKey = null;
        if ($sportValue !== '' && strcasecmp($sportValue, 'all') !== 0) {
            $sportKey = $this->teamSportContextService->resolveSportKey($sportValue);
            if ($sportKey === null || !isset($sportOptions[$sportKey])) {
                return $this->invalidScope('invalid_sport');
            }
        }

        $rawTeam = $query['team'] ?? 'all';
        if (!is_scalar($rawTeam)) {
            return $this->invalidScope('invalid_team');
        }

        $teamValue = trim((string)$rawTeam);
        if ($teamValue === '' || strcasecmp($teamValue, 'all') === 0) {
            return $this->validScope($sportKey, null);
        }

        if (!ctype_digit($teamValue) || (int)$teamValue <= 0) {
            return $this->invalidScope('invalid_team');
        }

        $teamId = (int)$teamValue;
        $team = $teams[$teamId] ?? null;
        if (!$team instanceof Team) {
            return $this->invalidScope('invalid_team');
        }

        $teamSportKey = $this->teamSportContextService->resolveSportKeyFromTeam($team);
        if ($teamSportKey === null) {
            return $this->invalidScope('invalid_team');
        }

        if ($sportKey !== null && $sportKey !== $teamSportKey) {
            return $this->invalidScope('team_sport_mismatch');
        }

        return $this->validScope($teamSportKey, $teamId);
    }

    /**
     * Resolve an invalid public scope to its closest valid browse scope.
     *
     * @param array<string,mixed> $query Query parameters.
     * @return array{valid:bool,error:string|null,sportKey:string|null,teamId:int|null}
     */
    public function resolveSafe(array $query): array
    {
        $scope = $this->resolve($query);
        if ($scope['valid']) {
            return $scope;
        }

        $teams = $this->getConfiguredTeams();
        $sportOptions = $this->buildAvailableSportOptions($teams);

        $team = null;
        $teamId = null;
        $rawTeam = $query['team'] ?? 'all';
        if (is_scalar($rawTeam)) {
            $teamValue = trim((string)$rawTeam);
            if ($teamValue !== '' && strcasecmp($teamValue, 'all') !== 0 && ctype_digit($teamValue)) {
                $candidateId = (int)$teamValue;
                $candidate = $teams[$candidateId] ?? null;
                if ($candidate instanceof Team) {
                    $team = $candidate;
                    $teamId = $candidateId;
                }
            }
        }

        if ($team instanceof Team) {
            $teamSportKey = $this->teamSportContextService->resolveSportKeyFromTeam($team);
            if ($teamSportKey !== null && isset($sportOptions[$teamSportKey])) {
                return $this->validScope($teamSportKey, $teamId);
            }
        }

        $rawSport = $query['sport'] ?? 'all';
        if (is_scalar($rawSport)) {
            $sportValue = trim((string)$rawSport);
            if ($sportValue !== '' && strcasecmp($sportValue, 'all') !== 0) {
                $sportKey = $this->teamSportContextService->resolveSportKey($sportValue);
                if ($sportKey !== null && isset($sportOptions[$sportKey])) {
                    return $this->validScope($sportKey, null);
                }
            }
        }

        return $this->validScope(null, null);
    }

    /**
     * Resolve a Games browse scope, selecting the only team with public results when possible.
     *
     * @param array<string,mixed> $query Query parameters.
     * @return array{valid:bool,error:string|null,sportKey:string|null,teamId:int|null}
     */
    public function resolveGamesScope(array $query): array
    {
        $scope = $this->resolve($query);
        if (!$scope['valid']) {
            $scope = $this->resolveSafe($query);
        }

        return $scope;
    }

    /**
     * Determine whether the public sport/team selector bar belongs on this route.
     *
     * @param string $controller Current controller name.
     * @param string $action Current action name.
     * @param array<int|string,mixed> $pass Current route pass parameters.
     * @param array{valid:bool,error:string|null,sportKey:string|null,teamId:int|null} $scope Resolved public scope.
     * @return bool
     */
    public function shouldShowPublicScopeBar(
        string $controller,
        string $action,
        array $pass,
        array $scope,
    ): bool {
        if (in_array($controller, ['Seasons', 'Stats', 'Games'], true)) {
            return true;
        }

        if (in_array($controller, ['Blog', 'People'], true)) {
            return $scope['sportKey'] !== null && $scope['teamId'] !== null;
        }

        if ($controller === 'Pages' && $action === 'display') {
            $page = $pass[0] ?? null;

            return $page === 'home'
                && $scope['sportKey'] !== null
                && $scope['teamId'] !== null;
        }

        return false;
    }

    /**
     * Determine whether a real sport or team scope is selected.
     *
     * @param array{valid:bool,error:string|null,sportKey:string|null,teamId:int|null} $scope Resolved public scope.
     * @return bool
     */
    public function hasPublicScopeSelection(array $scope): bool
    {
        return $scope['sportKey'] !== null || $scope['teamId'] !== null;
    }

    /**
     * Resolve a Stats browse scope, selecting the only stats-enabled sport when possible.
     *
     * @param array<string,mixed> $query Query parameters.
     * @param array<int,string> $supportedSportKeys Sports with a stats service and stats enabled.
     * @return array{valid:bool,error:string|null,sportKey:string|null,teamId:int|null}
     */
    public function resolveStatsScope(array $query, array $supportedSportKeys): array
    {
        $scope = $this->resolve($query);
        if (!$scope['valid']) {
            $scope = $this->resolveSafe($query);
        }
        if ($scope['sportKey'] !== null || count($supportedSportKeys) !== 1) {
            return $scope;
        }

        $sportKey = $this->teamSportContextService->resolveSportKey($supportedSportKeys[0]);
        if ($sportKey !== null) {
            $scope['sportKey'] = $sportKey;
        }

        return $scope;
    }

    /**
     * Get configured sports that currently have at least one team.
     *
     * @return array<string,string>
     */
    public function getAvailableSportOptions(): array
    {
        return $this->buildAvailableSportOptions($this->getConfiguredTeams());
    }

    /**
     * Get configured teams, optionally restricted to one sport.
     *
     * @param string|null $sportKey Canonical sport key, or null for every sport.
     * @return array<int,array{label:string,sportKey:string}>
     */
    public function getTeamOptions(?string $sportKey = null): array
    {
        $teams = $this->getConfiguredTeams();
        $sportOptions = $this->buildAvailableSportOptions($teams);
        if ($sportKey !== null) {
            $sportKey = $this->teamSportContextService->resolveSportKey($sportKey);
            if ($sportKey === null || !isset($sportOptions[$sportKey])) {
                return [];
            }
        }

        $options = [];
        foreach ($teams as $team) {
            $teamSportKey = $this->teamSportContextService->resolveSportKeyFromTeam($team);
            if ($teamSportKey === null || ($sportKey !== null && $teamSportKey !== $sportKey)) {
                continue;
            }

            $label = $team->getDisplayName();
            if ($sportKey === null) {
                $label .= ' (' . $sportOptions[$teamSportKey] . ')';
            }

            $options[(int)$team->id] = [
                'label' => $label,
                'sportKey' => $teamSportKey,
            ];
        }

        uasort(
            $options,
            static fn(array $left, array $right): int => strnatcasecmp($left['label'], $right['label']),
        );

        return $options;
    }

    /**
     * @return array<int,\App\Model\Entity\Team>
     */
    private function getConfiguredTeams(): array
    {
        if ($this->configuredTeamsCache !== null) {
            return $this->configuredTeamsCache;
        }

        $configuredSports = $this->teamSportContextService->getSportOptions();
        $rows = $this->teamsTable->find()
            ->orderByAsc('Teams.team_name')
            ->all()
            ->toArray();

        $teams = [];
        foreach ($rows as $team) {
            if (!$team instanceof Team) {
                continue;
            }

            $this->teamSportContextService->attachSportContextToTeam($team);
            $sportKey = $this->teamSportContextService->resolveSportKeyFromTeam($team);
            if ($sportKey === null || !isset($configuredSports[$sportKey])) {
                continue;
            }

            $teams[(int)$team->id] = $team;
        }

        $this->configuredTeamsCache = $teams;

        return $this->configuredTeamsCache;
    }

    /**
     * @param array<int,\App\Model\Entity\Team> $teams Configured teams.
     * @return array<string,string>
     */
    private function buildAvailableSportOptions(array $teams): array
    {
        $configuredSports = $this->teamSportContextService->getSportOptions();
        $available = [];
        foreach ($teams as $team) {
            $sportKey = $this->teamSportContextService->resolveSportKeyFromTeam($team);
            if ($sportKey !== null && isset($configuredSports[$sportKey])) {
                $available[$sportKey] = $configuredSports[$sportKey];
            }
        }

        uasort($available, static fn(string $left, string $right): int => strcasecmp($left, $right));

        return $available;
    }

    /**
     * @param string|null $sportKey Canonical sport key.
     * @param int|null $teamId Team identifier.
     * @return array{valid:bool,error:null,sportKey:string|null,teamId:int|null}
     */
    private function validScope(?string $sportKey, ?int $teamId): array
    {
        return [
            'valid' => true,
            'error' => null,
            'sportKey' => $sportKey,
            'teamId' => $teamId,
        ];
    }

    /**
     * @param string $error Invalid scope reason.
     * @return array{valid:false,error:string,sportKey:null,teamId:null}
     */
    private function invalidScope(string $error): array
    {
        return [
            'valid' => false,
            'error' => $error,
            'sportKey' => null,
            'teamId' => null,
        ];
    }
}
