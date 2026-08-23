<?php
declare(strict_types=1);

namespace App\Service;

use Cake\ORM\Query\SelectQuery;
use Cake\ORM\TableRegistry;
use Throwable;

/**
 * GamesAdminService
 *
 * Owns admin-facing orchestration around game listing/filtering helpers,
 * table-side JSON payload generation, and destructive actions.
 *
 * This complements GameUpsertService/GameViewService/GameEavMetaService by
 * covering the remaining admin concerns that should not live in the
 * controller.
 */
class GamesAdminService
{
    /**
     * @var \App\Service\GameService
     */
    private GameService $gameService;

    /**
     * @var \App\Service\SportConfigService
     */
    private SportConfigService $sportConfigService;
    private RbacPermissionService $rbacPermissionService;

    /**
     * @param \App\Service\GameService|null $gameService
     * @param \App\Service\SportConfigService|null $sportConfigService
     * @param \App\Service\RbacPermissionService|null $rbacPermissionService
     */
    public function __construct(
        ?GameService $gameService = null,
        ?SportConfigService $sportConfigService = null,
        ?RbacPermissionService $rbacPermissionService = null,
    ) {
        $this->gameService = $gameService ?? new GameService();
        $this->sportConfigService = $sportConfigService ?? new SportConfigService();
        $this->rbacPermissionService = $rbacPermissionService ?? new RbacPermissionService();
    }

    /**
     * Build index context from optional team season filter.
     *
     * @param int|null $teamSeasonId
     * @return array{teamSeason:mixed,teamSeasonId:int|null}
     */
    public function getIndexContext(?int $teamSeasonId): array
    {
        $teamSeason = null;
        if ($teamSeasonId !== null && $teamSeasonId > 0) {
            $teamSeason = TableRegistry::getTableLocator()
                ->get('TeamSeasons')
                ->get($teamSeasonId, contain: ['Teams', 'Seasons']);
        }

        return [
            'teamSeason' => $teamSeason,
            'teamSeasonId' => $teamSeasonId,
        ];
    }

    /**
     * Build DataTables payload for games listing.
     *
     * @param array<string,mixed> $params
     * @return array{recordsTotal:int,recordsFiltered:int,data:array<int,array<string,mixed>>}
     */
    public function buildDataTablePayload(array $params): array
    {
        return $this->gameService->buildGamesDataTable($params);
    }

    /**
     * Get sites by place ID for dependent select UI.
     *
     * @param int $placeId
     * @return array<int,array<string,mixed>>
     */
    public function getSitesByPlace(int $placeId): array
    {
        return $this->gameService->getSitesByPlace($placeId);
    }

    /**
     * Determine if a sport has configured stat tables.
     *
     * @param int $sportId
     */
    public function hasStats(int $sportId): bool
    {
        if ($sportId <= 0) {
            return false;
        }

        $hasStats = $this->sportConfigService->getConfig($sportId, 'has_stats', null);
        if (is_bool($hasStats)) {
            return $hasStats;
        }

        return !empty($this->sportConfigService->getStatFields($sportId, 'player'));
    }

    /**
     * Build all form list variables for add/edit pages.
     *
     * @param int|null $placeId
     * @return array<string,mixed>
     */
    public function getFormLists(?int $placeId = null): array
    {
        $lists = $this->gameService->getFormLists($placeId);
        $extra = $this->gameService->getTeamSeasonAndSportsLists();

        return array_merge($lists, $extra);
    }

    /**
     * Delete one game by ID.
     *
     * @param int $id
     * @param mixed $identity Current authenticated identity
     * @return bool
     */
    public function delete(int $id, mixed $identity = null): bool
    {
        // If identity is admin, allow direct delete
        if ($this->rbacPermissionService->isAdmin($identity)) {
            return $this->gameService->deleteGame($id);
        }

        // Otherwise, ensure the identity is allowed to delete this specific game
        $gamesTable = TableRegistry::getTableLocator()->get('Games');
        $query = $gamesTable->find()->where(['Games.id' => $id])->select(['id'])->disableHydration();
        $scoped = $this->applyScope($identity, 'delete', $query, 'Games');
        $row = $scoped->first();
        if (!$row) {
            return false;
        }

        return $this->gameService->deleteGame($id);
    }

    /**
     * Bulk delete game IDs.
     *
     * @param array<int|string> $ids
     * @param mixed $identity Current authenticated identity
     * @return array{deleted:int,teamSeasonId:int|null}
     */
    public function bulkDelete(array $ids, mixed $identity = null): array
    {
        $normalizedIds = array_values(array_filter(array_map('intval', $ids), fn($v) => $v > 0));
        if ($normalizedIds === []) {
            return ['deleted' => 0, 'teamSeasonId' => null];
        }

        // Admins may delete freely
        if ($this->rbacPermissionService->isAdmin($identity)) {
            return $this->gameService->bulkDeleteGames($normalizedIds);
        }

        $gamesTable = TableRegistry::getTableLocator()->get('Games');
        $query = $gamesTable->find()
            ->select(['id', 'team_season_id'])
            ->where(['Games.id IN' => $normalizedIds])
            ->disableHydration();

        $scoped = $this->applyScope($identity, 'delete', $query, 'Games');

        $allowedIds = [];
        $teamSeasonId = null;
        foreach ($scoped->all() as $row) {
            if (is_array($row) && !empty($row['id'])) {
                $allowedIds[] = (int)$row['id'];
                if ($teamSeasonId === null && array_key_exists('team_season_id', $row)) {
                    $rawTeamSeasonId = $row['team_season_id'];
                    $teamSeasonId = $rawTeamSeasonId === null ? null : (int)$rawTeamSeasonId;
                }
            }
        }

        if ($allowedIds === []) {
            return ['deleted' => 0, 'teamSeasonId' => $teamSeasonId];
        }

        $result = $this->gameService->bulkDeleteGames($allowedIds);

        // Ensure teamSeasonId is included when present
        $result['teamSeasonId'] = $teamSeasonId;

        return $result;
    }

    /**
     * Apply RBAC/service scope to a query.
     *
     * @param mixed $identity Current authenticated identity
     * @param string $action RBAC action/ability name
     * @param \Cake\ORM\Query\SelectQuery $query Query to scope
     * @param string $modelName Canonical RBAC model name
     * @return \Cake\ORM\Query\SelectQuery
     */
    private function applyScope(mixed $identity, string $action, SelectQuery $query, string $modelName): SelectQuery
    {
        if (is_object($identity) && method_exists($identity, 'applyScope')) {
            try {
                $scoped = $identity->applyScope($action, $query);
                if ($scoped instanceof SelectQuery) {
                    return $scoped;
                }
            } catch (Throwable) {
                // Fall back to RBAC service scoping when policy resolution fails.
            }
        }

        return $this->rbacPermissionService->scopeQuery($identity, $modelName, $query, $action, 'user_id');
    }
}
