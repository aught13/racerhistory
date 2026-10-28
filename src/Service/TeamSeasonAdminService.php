<?php
declare(strict_types=1);

namespace App\Service;

use App\Model\Entity\Season;
use App\Model\Entity\Team;
use App\Model\Entity\TeamSeason;
use App\Model\Table\GamesTable;
use App\Model\Table\ImagesTable;
use App\Model\Table\SeasonsTable;
use App\Model\Table\TeamSeasonRostersTable;
use App\Model\Table\TeamSeasonsTable;
use App\Model\Table\TeamsTable;
use Cake\Datasource\Exception\RecordNotFoundException;
use Cake\ORM\TableRegistry;

/**
 * TeamSeasonAdminService
 *
 * Encapsulates complete admin orchestration for TeamSeasons, including list,
 * detail composition (games, rosters, prev/next links, stats), add/edit form
 * preparation, persistence, and bulk deletion.
 *
 * Notes:
 * - Preserve result array keys expected by TeamSeasonsController templates.
 * - Keep image-resolution fallback silent (missing image is non-fatal).
 * - Reuse this service for future TeamSeason admin endpoints before adding
 *   controller-side query logic.
 */
class TeamSeasonAdminService
{
    private TeamSportContextService $teamSportContextService;

    private RbacPermissionService $rbacPermissionService;

    private AdminDataScopeService $adminDataScopeService;

    /**
     * @param \App\Service\TeamSportContextService|null $teamSportContextService Team sport context helper
     * @param \App\Service\AdminDataScopeService|null $adminDataScopeService Role data scope resolver
     */
    public function __construct(
        ?TeamSportContextService $teamSportContextService = null,
        ?AdminDataScopeService $adminDataScopeService = null,
    ) {
        $this->teamSportContextService = $teamSportContextService ?? new TeamSportContextService();
        $this->rbacPermissionService = new RbacPermissionService();
        $this->adminDataScopeService = $adminDataScopeService ?? new AdminDataScopeService();
    }

    /**
     * Return index page data.
     *
     * @param mixed $identity Current authenticated identity.
     * @param string|null $sportKey Optional sport filter.
     * @param int|null $teamId Optional team filter.
     * @return array{teamSeasons:\Cake\Datasource\ResultSetInterface,teams:array<int,string>,sportOptions:array<string,string>,currentSportKey:string,currentTeamId:int}
     */
    public function getIndexData(
        mixed $identity = null,
        ?string $sportKey = null,
        ?int $teamId = null,
    ): array {
        $query = $this->adminDataScopeService->scopeTeamOwnedQuery(
            $identity,
            'TeamSeasons',
            $this->getTeamSeasonsTable()->find(),
        );
        $normalizedSportKey = null;
        if ($sportKey !== null && trim($sportKey) !== '') {
            $normalizedSportKey = $this->teamSportContextService->resolveSportKey($sportKey);
            if ($normalizedSportKey === null) {
                $query->where(['TeamSeasons.id' => -1]);
            } else {
                $sportConditions = $this->teamSportContextService->buildSportFilterConditions($normalizedSportKey);
                $query->matching('Teams', static function ($teamsQuery) use ($sportConditions) {
                    return $teamsQuery->where($sportConditions);
                });
            }
        }
        if ($teamId !== null) {
            $query->where(['TeamSeasons.team_id' => $teamId]);
        }
        $teamSeasons = $query->contain(['Teams', 'Seasons'])->all();

        foreach ($teamSeasons as $teamSeason) {
            $this->teamSportContextService->attachSportContextToTeam($teamSeason->team ?? null);
        }

        $allowedTeamIds = $this->adminDataScopeService->getAllowedTeamIds($identity);
        $teamQuery = $this->getTeamsTable()->find()->limit(200);
        if ($allowedTeamIds === []) {
            $teamQuery->where(['Teams.id' => -1]);
        } elseif ($allowedTeamIds !== null) {
            $teamQuery->where(['Teams.id IN' => $allowedTeamIds]);
        }
        $teams = $this->formatTeamOptions($teamQuery->orderByAsc('Teams.team_name')->all());
        $sportOptions = $this->teamSportContextService->getSportOptions();
        $currentSportKey = $normalizedSportKey ?? '';
        $currentTeamId = $teamId ?? 0;

        return compact('teamSeasons', 'teams', 'sportOptions', 'currentSportKey', 'currentTeamId');
    }

    /**
     * Return full view page data.
     *
     * @param string|int $id Team season identifier
     * @param mixed $identity Current authenticated identity.
     * @return array<string,mixed>
     */
    public function getViewData(int|string $id, mixed $identity = null): array
    {
        $teamSeason = $this->findScopedTeamSeason($id, $identity);
        $this->teamSportContextService->attachSportContextToTeam($teamSeason->team ?? null);

        $teamSeasonRosters = $this->getTeamSeasonRostersTable()->find()
            ->where(['team_season_id' => $id])
            ->contain(['Persons'])
            ->all();

        $teamSeasonGames = $this->getGamesTable()->find()
            ->where(['team_season_id' => $id])
            ->contain(['GameTypes', 'Opponents', 'Sites' => ['Places'], 'Places'])
            ->orderByAsc('game_date')
            ->all();

        $currentSportId = (int)($this->teamSportContextService->resolveSportIdFromTeam($teamSeason->team ?? null) ?? 0);
        $currentSportKey = $this->teamSportContextService->resolveSportKeyFromTeam($teamSeason->team ?? null);
        $currentSeasonEnd = (int)($teamSeason->season->end ?? 0);
        $allowedTeamIds = $this->adminDataScopeService->getAllowedTeamIds($identity);

        /** @var \App\Model\Entity\TeamSeason|null $previousTeamSeason */
        $previousTeamSeason = null;
        /** @var \App\Model\Entity\TeamSeason|null $nextTeamSeason */
        $nextTeamSeason = null;

        if ($currentSportKey !== null && $currentSeasonEnd > 0) {
            $sportFilter = $this->teamSportContextService->buildSportFilterConditions($currentSportKey);

            $previousQuery = $this->getTeamSeasonsTable()->find()
                ->contain(['Teams', 'Seasons'])
                ->matching('Teams', function ($query) use ($sportFilter) {
                    return $query->where($sportFilter);
                })
                ->matching('Seasons', function ($query) use ($currentSeasonEnd) {
                    return $query->where(['Seasons.end <' => $currentSeasonEnd]);
                });
            if ($allowedTeamIds !== null) {
                $previousQuery->where(['TeamSeasons.team_id IN' => $allowedTeamIds]);
            }
            $previousTeamSeason = $previousQuery->orderByDesc('Seasons.end')->first();

            if (!$previousTeamSeason instanceof TeamSeason) {
                $previousTeamSeason = null;
            }
            if ($previousTeamSeason instanceof TeamSeason) {
                $this->teamSportContextService->attachSportContextToTeam($previousTeamSeason->team);
            }

            $nextQuery = $this->getTeamSeasonsTable()->find()
                ->contain(['Teams', 'Seasons'])
                ->matching('Teams', function ($query) use ($sportFilter) {
                    return $query->where($sportFilter);
                })
                ->matching('Seasons', function ($query) use ($currentSeasonEnd) {
                    return $query->where(['Seasons.end >' => $currentSeasonEnd]);
                });
            if ($allowedTeamIds !== null) {
                $nextQuery->where(['TeamSeasons.team_id IN' => $allowedTeamIds]);
            }
            $nextTeamSeason = $nextQuery->orderByAsc('Seasons.end')->first();

            if (!$nextTeamSeason instanceof TeamSeason) {
                $nextTeamSeason = null;
            }
            if ($nextTeamSeason instanceof TeamSeason) {
                $this->teamSportContextService->attachSportContextToTeam($nextTeamSeason->team);
            }
        }

        $playerStats = null;
        $teamStats = null;
        $opponentStats = null;
        $statsService = new StatsService();
        $adminSeasonStatsElement = $statsService->getAdminSeasonStatsElement($currentSportId);
        if ($adminSeasonStatsElement !== null) {
            $seasonStats = $statsService->getSeasonStats((int)$id);
            if (is_array($seasonStats)) {
                $playerStats = $seasonStats['playerStats'] ?? null;
                $teamStats = $seasonStats['teamStats'] ?? null;
                $opponentStats = $seasonStats['opponentStats'] ?? null;
            }
        }

        return compact(
            'teamSeason',
            'teamSeasonRosters',
            'teamSeasonGames',
            'previousTeamSeason',
            'nextTeamSeason',
            'playerStats',
            'teamStats',
            'opponentStats',
            'adminSeasonStatsElement',
        );
    }

    /**
     * Return add form data, optionally pre-populated.
     *
     * @param int|null $teamId Pre-selected team id
     * @param int|null $seasonId Pre-selected season id
     * @param mixed $identity Current authenticated identity.
     * @return array<string,mixed>
     */
    public function getAddFormData(?int $teamId = null, ?int $seasonId = null, mixed $identity = null): array
    {
        /** @var \App\Model\Entity\TeamSeason $teamSeason */
        $teamSeason = $this->getTeamSeasonsTable()->newEmptyEntity();
        if ($teamId && $this->adminDataScopeService->canAccessTeam($identity, $teamId)) {
            $teamSeason->team_id = $teamId;
        }
        if ($seasonId) {
            $teamSeason->season_id = $seasonId;
        }

        $teamQuery = $this->adminDataScopeService->scopeTeamOwnedQuery(
            $identity,
            'Teams',
            $this->getTeamsTable()->find()->limit(200),
        );
        $teams = $this->formatTeamOptions($teamQuery->orderByAsc('Teams.team_name')->all());
        $seasonsList = $this->getSeasonsList();
        $sports = $this->teamSportContextService->getSportOptions();

        return compact('teamSeason', 'teams', 'seasonsList', 'sports');
    }

    /**
     * Save new team season.
     *
     * @param array<string,mixed> $data Request payload
     * @param int|null $teamId Pre-selected team id from query
     * @param int|null $seasonId Pre-selected season id from query
     * @param mixed $identity Current authenticated identity.
     * @return array{success:bool,teamSeason:\App\Model\Entity\TeamSeason}
     */
    public function saveNewTeamSeason(
        array $data,
        ?int $teamId = null,
        ?int $seasonId = null,
        mixed $identity = null,
    ): array {
        /** @var \App\Model\Entity\TeamSeason $teamSeason */
        $teamSeason = $this->getTeamSeasonsTable()->newEmptyEntity();
        if ($teamId && empty($data['team_id'])) {
            $data['team_id'] = $teamId;
        }
        if ($seasonId && empty($data['season_id'])) {
            $data['season_id'] = $seasonId;
        }
        if (!$this->adminDataScopeService->canAccessTeam($identity, (int)($data['team_id'] ?? 0))) {
            $success = false;

            return compact('success', 'teamSeason');
        }

        $data = $this->normalizeImageInput($data);
        $teamSeason = $this->getTeamSeasonsTable()->patchEntity($teamSeason, $data);
        $success = (bool)$this->getTeamSeasonsTable()->save($teamSeason);

        return compact('success', 'teamSeason');
    }

    /**
     * Return edit form data.
     *
     * @param string|int $id Team season identifier
     * @param mixed $identity Current authenticated identity.
     * @return array<string,mixed>
     */
    public function getEditFormData(int|string $id, mixed $identity = null): array
    {
        $teamSeason = $this->findScopedTeamSeason($id, $identity);
        $this->teamSportContextService->attachSportContextToTeam($teamSeason->team ?? null);
        $teamSeason = $teamSeason->set('team_season_image_entity', null);

        if ($teamSeason->team_season_image) {
            try {
                $imageEntity = $this->getImagesTable()->get($teamSeason->team_season_image);
                $teamSeason = $teamSeason->set('team_season_image_entity', $imageEntity);
            } catch (RecordNotFoundException $exception) {
                // Ignore missing image relation in admin edit page.
            }
        }

        $teamQuery = $this->adminDataScopeService->scopeTeamOwnedQuery(
            $identity,
            'Teams',
            $this->getTeamsTable()->find()->limit(200),
        );
        $teams = $this->formatTeamOptions($teamQuery->orderByAsc('Teams.team_name')->all());
        $seasonsList = $this->getSeasonsList();
        $sports = $this->teamSportContextService->getSportOptions();

        return compact('teamSeason', 'teams', 'seasonsList', 'sports');
    }

    /**
     * Save existing team season.
     *
     * @param string|int $id Team season identifier
     * @param array<string,mixed> $data Request payload
     * @param mixed $identity Current authenticated identity.
     * @return array{success:bool,teamSeason:\App\Model\Entity\TeamSeason}
     */
    public function saveExistingTeamSeason(int|string $id, array $data, mixed $identity = null): array
    {
        $teamSeason = $this->findScopedTeamSeason($id, $identity);
        if (
            array_key_exists('team_id', $data)
            && !$this->adminDataScopeService->canAccessTeam($identity, (int)$data['team_id'])
        ) {
            return ['success' => false, 'teamSeason' => $teamSeason];
        }

        $data = $this->normalizeImageInput($data);
        $teamSeason = $this->getTeamSeasonsTable()->patchEntity($teamSeason, $data);
        $success = (bool)$this->getTeamSeasonsTable()->save($teamSeason);

        return compact('success', 'teamSeason');
    }

    /**
     * Delete a team season.
     *
     * @param string|int $id Team season identifier
     * @param mixed $identity Current authenticated identity
     * @return bool
     */
    public function deleteTeamSeason(int|string $id, mixed $identity = null): bool
    {
        $scoped = $this->rbacPermissionService->scopeQuery(
            $identity,
            'TeamSeasons',
            $this->getTeamSeasonsTable()->find(),
            'delete',
            'id',
        );
        $scoped = $this->adminDataScopeService->scopeTeamOwnedQuery($identity, 'TeamSeasons', $scoped);
        $teamSeason = $scoped->where(['TeamSeasons.id' => (int)$id])->first();
        if ($teamSeason === null) {
            return false;
        }

        return (bool)$this->getTeamSeasonsTable()->delete($teamSeason);
    }

    /**
     * Validate and normalize bulk identifier input.
     *
     * @param array<mixed> $rawIds Raw identifier list
     * @return array<int>
     */
    public function sanitizeIdentifierList(array $rawIds): array
    {
        $filtered = array_values(array_filter($rawIds, static function ($value) {
            return $value !== '' && $value !== null && ctype_digit((string)$value);
        }));

        return array_map('intval', $filtered);
    }

    /**
     * Bulk delete team seasons.
     *
     * @param array<mixed> $rawIds Raw identifier list from request
     * @param mixed $identity Current authenticated identity
     * @return int Number of deleted records
     */
    public function bulkDeleteTeamSeasons(array $rawIds, mixed $identity = null): int
    {
        $teamSeasonIds = $this->sanitizeIdentifierList($rawIds);
        if ($teamSeasonIds === []) {
            return 0;
        }

        $allowedQuery = $this->rbacPermissionService->scopeQuery(
            $identity,
            'TeamSeasons',
            $this->getTeamSeasonsTable()->find(),
            'delete',
            'id',
        );
        $allowed = $this->adminDataScopeService->scopeTeamOwnedQuery($identity, 'TeamSeasons', $allowedQuery)
            ->select(['TeamSeasons.id'])
            ->where(['TeamSeasons.id IN' => $teamSeasonIds])
            ->enableHydration(false)
            ->all()
            ->extract('id')
            ->toList();

        $allowedIds = array_values(array_map('intval', $allowed));
        if ($allowedIds === []) {
            return 0;
        }

        $deletedCount = 0;
        foreach ($allowedIds as $id) {
            try {
                $teamSeason = $this->getTeamSeasonsTable()->get($id);
                if ($this->getTeamSeasonsTable()->delete($teamSeason)) {
                    $deletedCount++;
                }
            } catch (RecordNotFoundException $exception) {
                continue;
            }
        }

        return $deletedCount;
    }

    /**
     * Coerce numeric image input to integer for ORM type safety.
     *
     * @param array<string,mixed> $data Incoming payload
     * @return array<string,mixed>
     */
    private function normalizeImageInput(array $data): array
    {
        if (isset($data['team_season_image']) && is_numeric((string)$data['team_season_image'])) {
            $data['team_season_image'] = (int)$data['team_season_image'];
        }

        return $data;
    }

    /**
     * @param string|int $id Team season identifier.
     * @param mixed $identity Current authenticated identity.
     * @return \App\Model\Entity\TeamSeason
     * @throws \Cake\Datasource\Exception\RecordNotFoundException If outside the role scope.
     */
    private function findScopedTeamSeason(int|string $id, mixed $identity): TeamSeason
    {
        $teamSeason = $this->adminDataScopeService->scopeTeamOwnedQuery(
            $identity,
            'TeamSeasons',
            $this->getTeamSeasonsTable()->find(),
        )
            ->contain(['Teams', 'Seasons'])
            ->where(['TeamSeasons.id' => (int)$id])
            ->first();

        if (!$teamSeason instanceof TeamSeason) {
            throw new RecordNotFoundException('Team season not found.');
        }

        return $teamSeason;
    }

    /**
     * Build season list for select controls as id => "start-end".
     *
     * @return array<int,string>
     */
    private function getSeasonsList(): array
    {
        $rows = $this->getSeasonsTable()->find()
            ->select(['id', 'start', 'end'])
            ->orderByDesc('start')
            ->all();

        $seasonsList = [];
        foreach ($rows as $season) {
            if (!($season instanceof Season)) {
                continue;
            }
            $seasonsList[(int)$season->id] = $season->start . '-' . $season->end;
        }

        return $seasonsList;
    }

    /**
     * @return \App\Model\Table\TeamSeasonsTable
     */
    private function getTeamSeasonsTable(): TeamSeasonsTable
    {
        /** @var \App\Model\Table\TeamSeasonsTable $table */
        $table = TableRegistry::getTableLocator()->get('TeamSeasons');

        return $table;
    }

    /**
     * @return \App\Model\Table\TeamSeasonRostersTable
     */
    private function getTeamSeasonRostersTable(): TeamSeasonRostersTable
    {
        /** @var \App\Model\Table\TeamSeasonRostersTable $table */
        $table = TableRegistry::getTableLocator()->get('TeamSeasonRosters');

        return $table;
    }

    /**
     * @return \App\Model\Table\GamesTable
     */
    private function getGamesTable(): GamesTable
    {
        /** @var \App\Model\Table\GamesTable $table */
        $table = TableRegistry::getTableLocator()->get('Games');

        return $table;
    }

    /**
     * @param iterable<\Cake\Datasource\EntityInterface|array<string,mixed>> $teamEntities Team query records
     * @return array<int,string>
     */
    private function formatTeamOptions(iterable $teamEntities): array
    {
        $options = [];
        foreach ($teamEntities as $team) {
            if (!$team instanceof Team) {
                continue;
            }

            $options[(int)$team->id] = $team->getDisplayName();
        }

        return $options;
    }

    /**
     * @return \App\Model\Table\TeamsTable
     */
    private function getTeamsTable(): TeamsTable
    {
        /** @var \App\Model\Table\TeamsTable $table */
        $table = TableRegistry::getTableLocator()->get('Teams');

        return $table;
    }

    /**
     * @return \App\Model\Table\SeasonsTable
     */
    private function getSeasonsTable(): SeasonsTable
    {
        /** @var \App\Model\Table\SeasonsTable $table */
        $table = TableRegistry::getTableLocator()->get('Seasons');

        return $table;
    }

    /**
     * @return \App\Model\Table\ImagesTable
     */
    private function getImagesTable(): ImagesTable
    {
        /** @var \App\Model\Table\ImagesTable $table */
        $table = TableRegistry::getTableLocator()->get('Images');

        return $table;
    }
}
