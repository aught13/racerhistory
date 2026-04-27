<?php
declare(strict_types=1);

namespace App\Service;

use Cake\ORM\TableRegistry;

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

    /**
     * @param \App\Service\GameService|null $gameService
     * @param \App\Service\SportConfigService|null $sportConfigService
     */
    public function __construct(
        ?GameService $gameService = null,
        ?SportConfigService $sportConfigService = null,
    ) {
        $this->gameService = $gameService ?? new GameService();
        $this->sportConfigService = $sportConfigService ?? new SportConfigService();
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
     */
    public function hasStats(int $sportId): bool
    {
        if ($sportId <= 0) {
            return false;
        }

        return !empty($this->sportConfigService->getAllStatTables($sportId));
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
     */
    public function delete(int $id): bool
    {
        return $this->gameService->deleteGame($id);
    }

    /**
     * Bulk delete game IDs.
     *
     * @param array<int|string,mixed> $ids
     * @return array{deleted:int,teamSeasonId:int|null}
     */
    public function bulkDelete(array $ids): array
    {
        /** @var array{deleted:int,teamSeasonId:int|null} $result */
        $result = $this->gameService->bulkDeleteGames($ids);

        return $result;
    }
}
