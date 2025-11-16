<?php
declare(strict_types=1);

namespace App\Service;

use Cake\Datasource\Exception\RecordNotFoundException;
use Cake\ORM\Locator\LocatorAwareTrait;

/**
 * GameService
 *
 * Service for game-related business logic including:
 * - Loading games with associations
 * - Managing EAV metadata
 * - Retrieving formatted lists for forms
 */
class GameService
{
    use LocatorAwareTrait;

    /**
     * SportConfigService instance
     *
     * @var \App\Service\SportConfigService
     */
    protected SportConfigService $sportConfigService;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->sportConfigService = new SportConfigService();
    }

    /**
     * Get a game with full associations for display/edit
     *
     * @param int $gameId Game ID
     * @return \App\Model\Entity\Game
     * @throws \Cake\Datasource\Exception\RecordNotFoundException
     */
    public function getGameWithAssociations(int $gameId): \App\Model\Entity\Game
    {
        /** @var \App\Model\Table\GamesTable $gamesTable */
        $gamesTable = $this->fetchTable('Games');

        /** @var \App\Model\Entity\Game $game */
        $game = $gamesTable->find()
            ->contain([
                'TeamSeason' => ['Teams' => ['Sports'], 'Seasons'],
                'GameTypes',
                'Opponents',
                'Sites' => ['Places'],
                'Places',
            ])
            ->where(['Games.id' => $gameId])
            ->firstOrFail();

        return $game;
    }

    /**
     * Load existing EAV values for a game as a flat array
     *
     * @param int $gameId Game ID
     * @return array EAV key-value pairs
     */
    public function loadGameEavValues(int $gameId): array
    {
        /** @var \App\Model\Table\GameEavTable $gameEavTable */
        $gameEavTable = $this->fetchTable('GameEav');

        $rows = $gameEavTable->find()
            ->where(['game_id' => $gameId])
            ->all();

        $values = [];
        foreach ($rows as $row) {
            $values[$row->eav_key] = $row->eav_value;
        }

        // Legacy mapping: period_X_mur/opp -> period_X_team/opponent
        foreach ($values as $k => $v) {
            if (preg_match('/^period_(\d+)_mur$/', $k, $m)) {
                $new = 'period_' . $m[1] . '_team';
                if (!isset($values[$new]) || $values[$new] === '' || $values[$new] === null) {
                    $values[$new] = $v;
                }
            } elseif (preg_match('/^period_(\d+)_opp$/', $k, $m)) {
                $new = 'period_' . $m[1] . '_opponent';
                if (!isset($values[$new]) || $values[$new] === '' || $values[$new] === null) {
                    $values[$new] = $v;
                }
            }
        }

        return $values;
    }

    /**
     * Get EAV metadata for a game or team season
     *
     * Returns sport configuration, EAV template, and existing values if editing
     *
     * @param int|null $gameId Game ID (optional, for editing)
     * @param int|null $teamSeasonId Team Season ID (optional, for creating)
     * @return array Metadata with keys: sportId, sportName, configs, eavTemplate, values
     * @throws \Cake\Datasource\Exception\RecordNotFoundException
     */
    public function getGameEavMetadata(?int $gameId = null, ?int $teamSeasonId = null): array
    {
        $sportId = null;
        $sportName = null;
        $existingValues = [];
        $game = null;

        // Load from game if provided
        if ($gameId) {
            $game = $this->getGameWithAssociations($gameId);
            $teamSeasonId = (int)($game->get('team_season_id') ?? 0);

            /** @var \App\Model\Entity\TeamSeason|null $teamSeasonAssoc */
            $teamSeasonAssoc = $game->get('team_season');
            if ($teamSeasonAssoc && $teamSeasonAssoc->team && $teamSeasonAssoc->team->sport) {
                $sportId = $teamSeasonAssoc->team->sport->id;
                $sportName = $teamSeasonAssoc->team->sport->sport_name;
            }

            $existingValues = $this->loadGameEavValues($gameId);
        }

        // Load sport from team season if not yet resolved
        if (!$sportId && $teamSeasonId) {
            /** @var \App\Model\Entity\TeamSeason $teamSeason */
            $teamSeason = $this->fetchTable('TeamSeasons')->find()
                ->contain(['Teams' => ['Sports']])
                ->where(['TeamSeasons.id' => $teamSeasonId])
                ->firstOrFail();

            if ($teamSeason->team && $teamSeason->team->sport) {
                $sportId = $teamSeason->team->sport->id;
                $sportName = $teamSeason->team->sport->sport_name;
            }
        }

        if (!$sportId) {
            throw new RecordNotFoundException('Cannot determine sport from provided game or team season');
        }

        // Get sport configs
        /** @var \App\Model\Table\SportConfigsTable $sportConfigsTable */
        $sportConfigsTable = $this->fetchTable('SportConfigs');
        $configs = $sportConfigsTable->getFormattedConfigsForSport($sportId);

        // Get EAV template
        /** @var \App\Model\Table\GameEavTable $gameEavTable */
        $gameEavTable = $this->fetchTable('GameEav');
        $gameEavTable->setSportConfigService($this->sportConfigService);

        $periods = '2';
        $overtime = '0';
        if ($game) {
            $periods = (string)($game->get('periods') ?: '2');
            $overtime = (string)($game->get('ot') ?: '0');
        }

        $eavTemplate = $gameEavTable->getEavTemplateForSport($sportId, $periods, $overtime);

        return [
            'sportId' => $sportId,
            'sportName' => $sportName,
            'configs' => $configs,
            'eavTemplate' => $eavTemplate,
            'values' => $existingValues,
        ];
    }

    /**
     * Get formatted lists for game form (opponents, sites, places, game types)
     *
     * @param int|null $placeId Optional place ID to filter sites
     * @return array Associative array with keys: opponents, sites, places, gameTypes
     */
    public function getFormLists(?int $placeId = null): array
    {
        // Opponents list
        /** @var \App\Model\Table\OpponentsTable $opponentsTable */
        $opponentsTable = $this->fetchTable('Opponents');
        $opponents = $opponentsTable->find('list')
            ->order(['Opponents.opponent_name' => 'ASC'])
            ->toArray();

        // Sites list (filtered by place if provided)
        /** @var \App\Model\Table\SitesTable $sitesTable */
        $sitesTable = $this->fetchTable('Sites');
        $sitesQuery = $sitesTable->find('list')->order(['Sites.site_name' => 'ASC']);

        if ($placeId) {
            $sitesQuery->where(['Sites.place_id' => $placeId]);
        }

        $sites = $sitesQuery->toArray();

        // Places list
        /** @var \App\Model\Table\PlacesTable $placesTable */
        $placesTable = $this->fetchTable('Places');
        $places = $placesTable->find('list')
            ->order(['Places.place_city' => 'ASC'])
            ->toArray();

        // Game types list
        /** @var \App\Model\Table\GameTypesTable $gameTypesTable */
        $gameTypesTable = $this->fetchTable('GameTypes');
        $gameTypes = $gameTypesTable->find('list')
            ->order(['GameTypes.game_type_name' => 'ASC'])
            ->toArray();

        return compact('opponents', 'sites', 'places', 'gameTypes');
    }

    /**
     * Save a game with EAV values
     *
     * @param \App\Model\Entity\Game $game Game entity
     * @param array $eavData EAV key-value pairs
     * @return bool Success status
     */
    public function saveGameWithEav(\App\Model\Entity\Game $game, array $eavData): bool
    {
        /** @var \App\Model\Table\GamesTable $gamesTable */
        $gamesTable = $this->fetchTable('Games');

        /** @var \App\Model\Table\GameEavTable $gameEavTable */
        $gameEavTable = $this->fetchTable('GameEav');

        // Save game first
        if (!$gamesTable->save($game)) {
            return false;
        }

        // Save EAV values
        $gameId = $game->id;
        foreach ($eavData as $key => $value) {
            // Skip empty values
            if ($value === '' || $value === null) {
                continue;
            }

            // Find existing or create new
            $existing = $gameEavTable->find()
                ->where(['game_id' => $gameId, 'eav_key' => $key])
                ->first();

            if ($existing) {
                $existing->eav_value = $value;
                $gameEavTable->save($existing);
            } else {
                $newEav = $gameEavTable->newEntity([
                    'game_id' => $gameId,
                    'eav_key' => $key,
                    'eav_value' => $value,
                ]);
                $gameEavTable->save($newEav);
            }
        }

        return true;
    }

    /**
     * Delete a game and its associated EAV values
     *
     * @param int $gameId Game ID
     * @return bool Success status
     */
    public function deleteGame(int $gameId): bool
    {
        /** @var \App\Model\Table\GamesTable $gamesTable */
        $gamesTable = $this->fetchTable('Games');

        /** @var \App\Model\Table\GameEavTable $gameEavTable */
        $gameEavTable = $this->fetchTable('GameEav');

        // Delete EAV values first
        $gameEavTable->deleteAll(['game_id' => $gameId]);

        // Delete game
        $game = $gamesTable->get($gameId);

        return $gamesTable->delete($game);
    }
}
