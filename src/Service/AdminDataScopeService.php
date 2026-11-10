<?php
declare(strict_types=1);

namespace App\Service;

use App\Model\Table\RoleDataScopesTable;
use App\Model\Table\TeamSeasonsTable;
use App\Model\Table\TeamsTable;
use Cake\ORM\Query\SelectQuery;
use Cake\ORM\TableRegistry;

/**
 * Resolves role-level access to teams and records owned by team seasons.
 */
class AdminDataScopeService
{
    /** @var list<string> */
    private const GAME_STAT_MODELS = [
        'StatBasketGameBox',
        'StatBasketGamePerson',
        'StatBasketGameTeam',
        'StatBasketGameOpponent',
    ];

    /** @var list<string> */
    private const SEASON_STAT_MODELS = [
        'StatBasketSeasonPerson',
        'StatBasketSeasonTeam',
        'StatBasketSeasonOpponent',
    ];

    private RoleDataScopesTable $roleDataScopesTable;

    private TeamsTable $teamsTable;

    private TeamSeasonsTable $teamSeasonsTable;

    private RbacPermissionService $rbacPermissionService;

    private TeamSportContextService $teamSportContextService;

    /**
     * @var array<int, list<int>|null>
     */
    private array $teamIdsByRole = [];

    /**
     * @param \App\Model\Table\RoleDataScopesTable|null $roleDataScopesTable Role scope rows.
     * @param \App\Model\Table\TeamsTable|null $teamsTable Teams table.
     * @param \App\Model\Table\TeamSeasonsTable|null $teamSeasonsTable Team seasons table.
     * @param \App\Service\RbacPermissionService|null $rbacPermissionService Role resolver.
     * @param \App\Service\TeamSportContextService|null $teamSportContextService Sport compatibility resolver.
     */
    public function __construct(
        ?RoleDataScopesTable $roleDataScopesTable = null,
        ?TeamsTable $teamsTable = null,
        ?TeamSeasonsTable $teamSeasonsTable = null,
        ?RbacPermissionService $rbacPermissionService = null,
        ?TeamSportContextService $teamSportContextService = null,
    ) {
        /** @var \App\Model\Table\RoleDataScopesTable $resolvedScopes */
        $resolvedScopes = $roleDataScopesTable ?? TableRegistry::getTableLocator()->get('RoleDataScopes');
        /** @var \App\Model\Table\TeamsTable $resolvedTeams */
        $resolvedTeams = $teamsTable ?? TableRegistry::getTableLocator()->get('Teams');
        /** @var \App\Model\Table\TeamSeasonsTable $resolvedTeamSeasons */
        $resolvedTeamSeasons = $teamSeasonsTable ?? TableRegistry::getTableLocator()->get('TeamSeasons');

        $this->roleDataScopesTable = $resolvedScopes;
        $this->teamsTable = $resolvedTeams;
        $this->teamSeasonsTable = $resolvedTeamSeasons;
        $this->rbacPermissionService = $rbacPermissionService ?? new RbacPermissionService();
        $this->teamSportContextService = $teamSportContextService ?? new TeamSportContextService();
    }

    /**
     * Return permitted team identifiers. Null means unrestricted; an empty list means deny all.
     *
     * @param mixed $identity Current authenticated identity.
     * @return list<int>|null
     */
    public function getAllowedTeamIds(mixed $identity): ?array
    {
        if ($this->rbacPermissionService->isAdmin($identity)) {
            return null;
        }

        $roleId = $this->rbacPermissionService->getRoleIdForIdentity($identity);
        if ($roleId === null || $roleId <= 0) {
            return [];
        }

        if (array_key_exists($roleId, $this->teamIdsByRole)) {
            return $this->teamIdsByRole[$roleId];
        }

        $scopeRows = $this->roleDataScopesTable->find()
            ->where(['RoleDataScopes.role_id' => $roleId])
            ->all();

        $teamIds = [];
        $sportKeys = [];
        foreach ($scopeRows as $scopeRow) {
            $scopeType = (string)$scopeRow->get('scope_type');
            if ($scopeType === 'all') {
                $this->teamIdsByRole[$roleId] = null;

                return null;
            }
            if ($scopeType === 'team' && (int)$scopeRow->get('team_id') > 0) {
                $teamIds[] = (int)$scopeRow->get('team_id');
            }
            if ($scopeType === 'sport' && trim((string)$scopeRow->get('sport_key')) !== '') {
                $sportKeys[] = strtolower(trim((string)$scopeRow->get('sport_key')));
            }
        }

        foreach (array_unique($sportKeys) as $sportKey) {
            $conditions = $this->teamSportContextService->buildSportFilterConditions($sportKey);
            if ($conditions === []) {
                continue;
            }

            $sportTeamIds = $this->teamsTable->find()
                ->select(['Teams.id'])
                ->where($conditions)
                ->enableHydration(false)
                ->all()
                ->extract('id')
                ->toList();
            foreach ($sportTeamIds as $teamId) {
                $teamIds[] = (int)$teamId;
            }
        }

        $teamIds = array_values(array_unique(array_filter($teamIds, static fn(int $id): bool => $id > 0)));
        sort($teamIds);
        $this->teamIdsByRole[$roleId] = $teamIds;

        return $teamIds;
    }

    /**
     * Clear the cached team grant for one role.
     *
     * @param int $roleId RBAC role identifier.
     * @return void
     */
    public function clearRoleScopeCache(int $roleId): void
    {
        unset($this->teamIdsByRole[$roleId]);
    }

    /**
     * Check whether one team is within the current role's grant.
     *
     * @param mixed $identity Current authenticated identity.
     * @param int $teamId Team identifier.
     * @return bool
     */
    public function canAccessTeam(mixed $identity, int $teamId): bool
    {
        if ($teamId <= 0) {
            return false;
        }

        $allowedTeamIds = $this->getAllowedTeamIds($identity);

        return $allowedTeamIds === null || in_array($teamId, $allowedTeamIds, true);
    }

    /**
     * Check whether one team season belongs to an allowed team.
     *
     * @param mixed $identity Current authenticated identity.
     * @param int $teamSeasonId TeamSeason identifier.
     * @return bool
     */
    public function canAccessTeamSeason(mixed $identity, int $teamSeasonId): bool
    {
        if ($teamSeasonId <= 0) {
            return false;
        }
        if ($this->rbacPermissionService->isAdmin($identity)) {
            return true;
        }

        return $this->scopeTeamOwnedQuery(
            $identity,
            'TeamSeasons',
            $this->teamSeasonsTable->find(),
        )
            ->where(['TeamSeasons.id' => $teamSeasonId])
            ->first() !== null;
    }

    /**
     * Check whether one game belongs to an allowed team season.
     *
     * @param mixed $identity Current authenticated identity.
     * @param int $gameId Game identifier.
     * @return bool
     */
    public function canAccessGame(mixed $identity, int $gameId): bool
    {
        if ($gameId <= 0) {
            return false;
        }
        if ($this->rbacPermissionService->isAdmin($identity)) {
            return true;
        }

        $gamesTable = TableRegistry::getTableLocator()->get('Games');

        return $this->scopeTeamOwnedQuery($identity, 'Games', $gamesTable->find())
            ->where(['Games.id' => $gameId])
            ->first() !== null;
    }

    /**
     * Check whether a game-stat row belongs to an allowed game.
     *
     * @param mixed $identity Current authenticated identity.
     * @param string $modelName Basketball game-stat table alias.
     * @param int $statId Stat row identifier.
     * @return bool
     */
    public function canAccessGameStatRow(mixed $identity, string $modelName, int $statId): bool
    {
        if (!in_array($modelName, self::GAME_STAT_MODELS, true) || $statId <= 0) {
            return false;
        }
        if ($this->rbacPermissionService->isAdmin($identity)) {
            return true;
        }

        $stat = TableRegistry::getTableLocator()->get($modelName)->find()
            ->select([$modelName . '.game_id'])
            ->where([$modelName . '.id' => $statId])
            ->first();
        if ($stat === null) {
            return false;
        }

        return $this->canAccessGame($identity, (int)$stat->get('game_id'));
    }

    /**
     * Check whether a season-stat row belongs to an allowed TeamSeason.
     *
     * @param mixed $identity Current authenticated identity.
     * @param string $modelName Basketball season-stat table alias.
     * @param int $statId Stat row identifier.
     * @return bool
     */
    public function canAccessSeasonStatRow(mixed $identity, string $modelName, int $statId): bool
    {
        if (!in_array($modelName, self::SEASON_STAT_MODELS, true) || $statId <= 0) {
            return false;
        }
        if ($this->rbacPermissionService->isAdmin($identity)) {
            return true;
        }

        $stat = TableRegistry::getTableLocator()->get($modelName)->find()
            ->select([$modelName . '.team_season_id'])
            ->where([$modelName . '.id' => $statId])
            ->first();
        if ($stat === null) {
            return false;
        }

        return $this->canAccessTeamSeason($identity, (int)$stat->get('team_season_id'));
    }

    /**
     * Check the parent scope for a basketball stat administration route.
     *
     * @param mixed $identity Current authenticated identity.
     * @param string $controller Admin controller name.
     * @param string $action Admin action name.
     * @param int $recordId First route identifier.
     * @return bool
     */
    public function canAccessStatAdminRequest(
        mixed $identity,
        string $controller,
        string $action,
        int $recordId,
    ): bool {
        if ($this->rbacPermissionService->isAdmin($identity)) {
            return true;
        }

        if ($controller === 'StatBasketGameBox' && in_array($action, ['gameBox', 'gameBoxPeriods'], true)) {
            return $this->canAccessGame($identity, $recordId);
        }

        if ($controller === 'StatBasketGameTeam' && in_array($action, ['view', 'edit'], true)) {
            return $this->canAccessGame($identity, $recordId);
        }

        if (in_array($controller, ['StatBasketGamePerson', 'StatBasketGameOpponent'], true)) {
            if (in_array($action, ['view', 'add', 'bulkAdd'], true)) {
                return $this->canAccessGame($identity, $recordId);
            }
            if (in_array($action, ['edit', 'deleteConfirm', 'delete'], true)) {
                $statModel = $controller === 'StatBasketGamePerson'
                    ? 'StatBasketGamePerson'
                    : 'StatBasketGameOpponent';

                return $this->canAccessGameStatRow($identity, $statModel, $recordId);
            }
        }

        if ($controller === 'StatBasketSeasonPerson') {
            if ($action === 'add') {
                return $this->canAccessTeamSeason($identity, $recordId);
            }
            if (in_array($action, ['edit', 'delete'], true)) {
                return $this->canAccessSeasonStatRow($identity, $controller, $recordId);
            }
        }

        if (
            in_array($controller, ['StatBasketSeasonTeam', 'StatBasketSeasonOpponent'], true)
            && in_array($action, ['edit', 'delete'], true)
        ) {
            return $this->canAccessTeamSeason($identity, $recordId);
        }

        return true;
    }

    /**
     * Return the TeamSeason IDs allowed by the current role.
     *
     * @param mixed $identity Current authenticated identity.
     * @return list<int>|null Null means unrestricted.
     */
    public function getAllowedTeamSeasonIds(mixed $identity): ?array
    {
        $query = $this->scopeTeamOwnedQuery(
            $identity,
            'TeamSeasons',
            $this->teamSeasonsTable->find(),
        );

        if ($this->getAllowedTeamIds($identity) === null) {
            return null;
        }

        return array_values(array_map(
            'intval',
            $query->select(['TeamSeasons.id'])
                ->enableHydration(false)
                ->all()
                ->extract('id')
                ->toList(),
        ));
    }

    /**
     * Check whether a role may create a new team in a sport.
     *
     * Team-specific grants do not permit creating additional teams in that sport.
     *
     * @param mixed $identity Current authenticated identity.
     * @param string $sportKey Canonical sport key.
     * @return bool
     */
    public function canCreateTeamForSport(mixed $identity, string $sportKey): bool
    {
        if ($this->rbacPermissionService->isAdmin($identity)) {
            return true;
        }

        $roleId = $this->rbacPermissionService->getRoleIdForIdentity($identity);
        if ($roleId === null || $roleId <= 0) {
            return false;
        }

        $normalizedSportKey = $this->teamSportContextService->resolveSportKey($sportKey);
        if ($normalizedSportKey === null) {
            return false;
        }

        return $this->roleDataScopesTable->exists([
            'RoleDataScopes.role_id' => $roleId,
            'RoleDataScopes.scope_type' => 'all',
        ]) || $this->roleDataScopesTable->exists([
            'RoleDataScopes.role_id' => $roleId,
            'RoleDataScopes.scope_type' => 'sport',
            'RoleDataScopes.sport_key' => $normalizedSportKey,
        ]);
    }

    /**
     * Restrict a team-owned table query to the granted teams.
     *
     * @param mixed $identity Current authenticated identity.
     * @param string $modelName Team-owned table alias.
     * @param \Cake\ORM\Query\SelectQuery $query Query to restrict.
     * @return \Cake\ORM\Query\SelectQuery
     */
    public function scopeTeamOwnedQuery(mixed $identity, string $modelName, SelectQuery $query): SelectQuery
    {
        $teamIds = $this->getAllowedTeamIds($identity);
        if ($teamIds === null) {
            return $query;
        }
        if ($teamIds === []) {
            return $query->where(['1 = 0']);
        }

        if ($modelName === 'Teams') {
            return $query->where(['Teams.id IN' => $teamIds]);
        }
        if ($modelName === 'TeamSeasons') {
            return $query->where(['TeamSeasons.team_id IN' => $teamIds]);
        }

        $teamSeasonIds = $this->teamSeasonsTable->find()
            ->select(['TeamSeasons.id'])
            ->where(['TeamSeasons.team_id IN' => $teamIds]);

        if ($modelName === 'Games') {
            return $query->where(['Games.team_season_id IN' => $teamSeasonIds]);
        }
        if ($modelName === 'TeamSeasonRosters') {
            return $query->where(['TeamSeasonRosters.team_season_id IN' => $teamSeasonIds]);
        }

        if (
            in_array($modelName, [
            'StatBasketSeasonPerson',
            'StatBasketSeasonTeam',
            'StatBasketSeasonOpponent',
            ], true)
        ) {
            return $query->where([$modelName . '.team_season_id IN' => $teamSeasonIds]);
        }

        if (
            in_array($modelName, [
            'StatBasketGameBox',
            'StatBasketGamePerson',
            'StatBasketGameTeam',
            'StatBasketGameOpponent',
            ], true)
        ) {
            $gameIds = TableRegistry::getTableLocator()->get('Games')->find()
                ->select(['Games.id'])
                ->where(['Games.team_season_id IN' => $teamSeasonIds]);

            return $query->where([$modelName . '.game_id IN' => $gameIds]);
        }

        return $query;
    }
}
