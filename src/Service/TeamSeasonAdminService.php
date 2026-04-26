<?php
declare(strict_types=1);

namespace App\Service;

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
    /**
     * Return index page data.
     *
     * @return array{teamSeasons:\Cake\Datasource\ResultSetInterface}
     */
    public function getIndexData(): array
    {
        $teamSeasons = $this->getTeamSeasonsTable()->find()
            ->contain(['Teams', 'Seasons'])
            ->all();

        return compact('teamSeasons');
    }

    /**
     * Return full view page data.
     *
     * @param string|int $id Team season identifier
     * @return array<string,mixed>
     */
    public function getViewData(int|string $id): array
    {
        /** @var \App\Model\Entity\TeamSeason $teamSeason */
        $teamSeason = $this->getTeamSeasonsTable()->get($id, contain: ['Teams', 'Seasons']);

        $teamSeasonRosters = $this->getTeamSeasonRostersTable()->find()
            ->where(['team_season_id' => $id])
            ->contain(['Persons'])
            ->all();

        $teamSeasonGames = $this->getGamesTable()->find()
            ->where(['team_season_id' => $id])
            ->contain(['GameTypes', 'Opponents', 'Sites' => ['Places'], 'Places'])
            ->orderByAsc('game_date')
            ->all();

        $currentSportId = (int)($teamSeason->team->sport_id ?? 0);
        $currentSeasonEnd = (int)($teamSeason->season->end ?? 0);

        /** @var \App\Model\Entity\TeamSeason|null $previousTeamSeason */
        $previousTeamSeason = null;
        /** @var \App\Model\Entity\TeamSeason|null $nextTeamSeason */
        $nextTeamSeason = null;

        if ($currentSportId > 0 && $currentSeasonEnd > 0) {
            $previousTeamSeason = $this->getTeamSeasonsTable()->find()
                ->contain(['Teams', 'Seasons'])
                ->matching('Teams', function ($query) use ($currentSportId) {
                    return $query->where(['Teams.sport_id' => $currentSportId]);
                })
                ->matching('Seasons', function ($query) use ($currentSeasonEnd) {
                    return $query->where(['Seasons.end <' => $currentSeasonEnd]);
                })
                ->orderByDesc('Seasons.end')
                ->first();

            $nextTeamSeason = $this->getTeamSeasonsTable()->find()
                ->contain(['Teams', 'Seasons'])
                ->matching('Teams', function ($query) use ($currentSportId) {
                    return $query->where(['Teams.sport_id' => $currentSportId]);
                })
                ->matching('Seasons', function ($query) use ($currentSeasonEnd) {
                    return $query->where(['Seasons.end >' => $currentSeasonEnd]);
                })
                ->orderByAsc('Seasons.end')
                ->first();
        }

        $playerStats = null;
        $teamStats = null;
        $opponentStats = null;
        if ($currentSportId > 0) {
            $seasonStats = (new StatsService())->getSeasonStats((int)$id);
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
            'opponentStats'
        );
    }

    /**
     * Return add form data, optionally pre-populated.
     *
     * @param int|null $teamId Pre-selected team id
     * @param int|null $seasonId Pre-selected season id
     * @return array<string,mixed>
     */
    public function getAddFormData(?int $teamId = null, ?int $seasonId = null): array
    {
        /** @var \App\Model\Entity\TeamSeason $teamSeason */
        $teamSeason = $this->getTeamSeasonsTable()->newEmptyEntity();
        if ($teamId) {
            $teamSeason->team_id = $teamId;
        }
        if ($seasonId) {
            $teamSeason->season_id = $seasonId;
        }

        $teams = $this->getTeamsTable()->find('list', limit: 200)->all();
        $seasonsList = $this->getSeasonsList();
        $sports = $this->getSportsTable()->find('list', limit: 200)->all();

        return compact('teamSeason', 'teams', 'seasonsList', 'sports');
    }

    /**
     * Save new team season.
     *
     * @param array<string,mixed> $data Request payload
     * @param int|null $teamId Pre-selected team id from query
     * @param int|null $seasonId Pre-selected season id from query
     * @return array{success:bool,teamSeason:\App\Model\Entity\TeamSeason}
     */
    public function saveNewTeamSeason(array $data, ?int $teamId = null, ?int $seasonId = null): array
    {
        /** @var \App\Model\Entity\TeamSeason $teamSeason */
        $teamSeason = $this->getTeamSeasonsTable()->newEmptyEntity();
        if ($teamId && empty($data['team_id'])) {
            $data['team_id'] = $teamId;
        }
        if ($seasonId && empty($data['season_id'])) {
            $data['season_id'] = $seasonId;
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
     * @return array<string,mixed>
     */
    public function getEditFormData(int|string $id): array
    {
        /** @var \App\Model\Entity\TeamSeason $teamSeason */
        $teamSeason = $this->getTeamSeasonsTable()->get($id, contain: ['Teams', 'Seasons']);
        $teamSeason = $teamSeason->set('team_season_image_entity', null);

        if ($teamSeason->team_season_image) {
            try {
                $imageEntity = $this->getImagesTable()->get($teamSeason->team_season_image);
                $teamSeason = $teamSeason->set('team_season_image_entity', $imageEntity);
            } catch (RecordNotFoundException $exception) {
                // Ignore missing image relation in admin edit page.
            }
        }

        $teams = $this->getTeamsTable()->find('list', limit: 200)->all();
        $seasonsList = $this->getSeasonsList();
        $sports = $this->getSportsTable()->find('list', limit: 200)->all();

        return compact('teamSeason', 'teams', 'seasonsList', 'sports');
    }

    /**
     * Save existing team season.
     *
     * @param string|int $id Team season identifier
     * @param array<string,mixed> $data Request payload
     * @return array{success:bool,teamSeason:\App\Model\Entity\TeamSeason}
     */
    public function saveExistingTeamSeason(int|string $id, array $data): array
    {
        /** @var \App\Model\Entity\TeamSeason $teamSeason */
        $teamSeason = $this->getTeamSeasonsTable()->get($id, contain: ['Teams', 'Seasons']);
        $data = $this->normalizeImageInput($data);
        $teamSeason = $this->getTeamSeasonsTable()->patchEntity($teamSeason, $data);
        $success = (bool)$this->getTeamSeasonsTable()->save($teamSeason);

        return compact('success', 'teamSeason');
    }

    /**
     * Delete a team season.
     *
     * @param string|int $id Team season identifier
     * @return bool
     */
    public function deleteTeamSeason(int|string $id): bool
    {
        $teamSeason = $this->getTeamSeasonsTable()->get($id);

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
     * @return int Number of deleted records
     */
    public function bulkDeleteTeamSeasons(array $rawIds): int
    {
        $teamSeasonIds = $this->sanitizeIdentifierList($rawIds);
        if ($teamSeasonIds === []) {
            return 0;
        }

        $deletedCount = 0;
        foreach ($teamSeasonIds as $id) {
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
            if (!($season instanceof \App\Model\Entity\Season)) {
                continue;
            }
            $seasonsList[(int)$season->id] = $season->start . '-' . $season->end;
        }

        return $seasonsList;
    }

    /**
     * @return \App\Model\Table\TeamSeasonsTable
     */
    private function getTeamSeasonsTable(): \App\Model\Table\TeamSeasonsTable
    {
        /** @var \App\Model\Table\TeamSeasonsTable $table */
        $table = TableRegistry::getTableLocator()->get('TeamSeasons');

        return $table;
    }

    /**
     * @return \App\Model\Table\TeamSeasonRostersTable
     */
    private function getTeamSeasonRostersTable(): \App\Model\Table\TeamSeasonRostersTable
    {
        /** @var \App\Model\Table\TeamSeasonRostersTable $table */
        $table = TableRegistry::getTableLocator()->get('TeamSeasonRosters');

        return $table;
    }

    /**
     * @return \App\Model\Table\GamesTable
     */
    private function getGamesTable(): \App\Model\Table\GamesTable
    {
        /** @var \App\Model\Table\GamesTable $table */
        $table = TableRegistry::getTableLocator()->get('Games');

        return $table;
    }

    /**
     * @return \App\Model\Table\TeamsTable
     */
    private function getTeamsTable(): \App\Model\Table\TeamsTable
    {
        /** @var \App\Model\Table\TeamsTable $table */
        $table = TableRegistry::getTableLocator()->get('Teams');

        return $table;
    }

    /**
     * @return \App\Model\Table\SeasonsTable
     */
    private function getSeasonsTable(): \App\Model\Table\SeasonsTable
    {
        /** @var \App\Model\Table\SeasonsTable $table */
        $table = TableRegistry::getTableLocator()->get('Seasons');

        return $table;
    }

    /**
     * @return \App\Model\Table\SportsTable
     */
    private function getSportsTable(): \App\Model\Table\SportsTable
    {
        /** @var \App\Model\Table\SportsTable $table */
        $table = TableRegistry::getTableLocator()->get('Sports');

        return $table;
    }

    /**
     * @return \App\Model\Table\ImagesTable
     */
    private function getImagesTable(): \App\Model\Table\ImagesTable
    {
        /** @var \App\Model\Table\ImagesTable $table */
        $table = TableRegistry::getTableLocator()->get('Images');

        return $table;
    }
}
