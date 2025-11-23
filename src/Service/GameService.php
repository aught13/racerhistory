<?php
declare(strict_types=1);

namespace App\Service;

use Burzum\CakeServiceLayer\Service\ServiceAwareTrait;
use Cake\Datasource\Exception\RecordNotFoundException;
use Cake\ORM\Locator\LocatorAwareTrait;

/**
 * GameService
 *
 * Service for game-related business logic including:
 * - Loading games with associations
 * - Managing EAV metadata
 * - Retrieving formatted lists for forms
 * - Inline entity creation
 * - Score calculations and validations
 */
class GameService
{
    use LocatorAwareTrait;
    use ServiceAwareTrait;

    /**
     * SportConfigService instance
     *
     * @var \App\Service\SportConfigService|null
     */
    protected ?SportConfigService $sportConfigService = null;

    /**
     * Constructor
     *
     * @param \App\Service\SportConfigService|null $sportConfigService Sport config service
     */
    public function __construct(?SportConfigService $sportConfigService = null)
    {
        $this->sportConfigService = $sportConfigService ?? $this->loadService('SportConfig', [], false);
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
            $values[$row->key] = $row->value;
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
            ->orderBy(['Opponents.opponent_name' => 'ASC'])
            ->toArray();

        // Sites list (filtered by place if provided)
        /** @var \App\Model\Table\SitesTable $sitesTable */
        $sitesTable = $this->fetchTable('Sites');
        $sitesQuery = $sitesTable->find('list')->orderBy(['Sites.site_name' => 'ASC']);

        if ($placeId) {
            $sitesQuery->where(['Sites.place_id' => $placeId]);
        }

        $sites = $sitesQuery->toArray();

        // Places list (formatted as "Name, State")
        /** @var \App\Model\Table\PlacesTable $placesTable */
        $placesTable = $this->fetchTable('Places');
        $placesQuery = $placesTable->find()
            ->orderBy(['Places.place_name' => 'ASC'])
            ->all();

        $places = [];
        foreach ($placesQuery as $place) {
            $label = $place->place_name;
            if (!empty($place->place_state)) {
                $label .= ', ' . $place->place_state;
            }
            $places[$place->id] = $label;
        }

        // Game types list
        /** @var \App\Model\Table\GameTypesTable $gameTypesTable */
        $gameTypesTable = $this->fetchTable('GameTypes');
        $gameTypes = $gameTypesTable->find('list')
            ->orderBy(['GameTypes.game_type_name' => 'ASC'])
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

    /**
     * Get sites filtered by place ID
     *
     * @param int $placeId Place ID
     * @return array Array of sites with id and name
     */
    public function getSitesByPlace(int $placeId): array
    {
        $sites = [];
        if ($placeId) {
            /** @var \App\Model\Table\SitesTable $sitesTable */
            $sitesTable = $this->fetchTable('Sites');
            $sitesQuery = $sitesTable->find()
                ->where(['Sites.place_id' => $placeId])
                ->orderBy(['Sites.site_name' => 'ASC'])
                ->all();

            foreach ($sitesQuery as $site) {
                $sites[] = [
                    'id' => $site->id,
                    'name' => $site->site_name,
                ];
            }
        }

        return $sites;
    }

    /**
     * Normalize inline creation payloads for associated entities
     *
     * Creates Place, Site, Opponent, or GameType entities from form data
     * and updates the data array with the new IDs
     *
     * @param array $data Request data (modified by reference)
     * @return void
     */
    public function normalizeAssociatedInlineCreate(array &$data): void
    {
        // New place
        if (!empty($data['new_place']['place_name'])) {
            /** @var \App\Model\Table\PlacesTable $places */
            $places = $this->fetchTable('Places');
            $place = $places->newEntity([
                'place_name' => $data['new_place']['place_name'] ?? null,
                'place_city' => $data['new_place']['place_city'] ?? null,
                'place_state' => $data['new_place']['place_state'] ?? null,
            ]);
            if ($places->save($place)) {
                $data['place_id'] = $place->get('id');
            }
        }

        // New site (requires place_id)
        if (!empty($data['new_site']['site_name'])) {
            /** @var \App\Model\Table\SitesTable $sites */
            $sites = $this->fetchTable('Sites');
            $site = $sites->newEntity([
                'site_name' => $data['new_site']['site_name'] ?? null,
                'place_id' => $data['place_id'] ?? null,
            ]);
            if ($sites->save($site)) {
                $data['site_id'] = $site->get('id');
            }
        }

        // New opponent
        if (!empty($data['new_opponent']['opponent_name'])) {
            /** @var \App\Model\Table\OpponentsTable $opponents */
            $opponents = $this->fetchTable('Opponents');
            $opp = $opponents->newEntity([
                'opponent_name' => $data['new_opponent']['opponent_name'] ?? null,
                'place_id' => $data['place_id'] ?? null,
            ]);
            if ($opponents->save($opp)) {
                $data['opponent_id'] = $opp->get('id');
            }
        }

        // New game type
        if (!empty($data['new_game_type']['game_type_name'])) {
            /** @var \App\Model\Table\GameTypesTable $gameTypes */
            $gameTypes = $this->fetchTable('GameTypes');
            $gt = $gameTypes->newEntity([
                'game_type_name' => $data['new_game_type']['game_type_name'] ?? null,
                'post' => $data['new_game_type']['post'] ?? 0,
                'conf' => $data['new_game_type']['conf'] ?? 0,
                'ind' => $data['new_game_type']['ind'] ?? null,
            ]);
            if ($gameTypes->save($gt)) {
                $data['game_type_id'] = $gt->get('id');
            }
        }
    }

    /**
     * Auto-calculate W/L based on game scores
     *
     * @param array $data Game data
     * @return array Modified data with W/L set
     */
    public function calculateWinLoss(array $data): array
    {
        $teamScore = isset($data['pts_mur']) ? (int)$data['pts_mur'] : 0;
        $oppScore = isset($data['pts_opp']) ? (int)$data['pts_opp'] : 0;

        // Only calculate if both scores are present
        if ($teamScore > 0 || $oppScore > 0) {
            if ($teamScore > $oppScore) {
                $data['w'] = 1;
                $data['l'] = 0;
            } elseif ($teamScore < $oppScore) {
                $data['w'] = 0;
                $data['l'] = 1;
            } else {
                // Tie game
                $data['w'] = 1;
                $data['l'] = 1;
            }
        }

        return $data;
    }

    /**
     * Validate period scores against final scores
     *
     * @param array $data Game and EAV data
     * @return array Error messages (empty if valid)
     */
    public function validatePeriodScores(array $data): array
    {
        $errors = [];

        $teamScore = isset($data['pts_mur']) ? (int)$data['pts_mur'] : 0;
        $oppScore = isset($data['pts_opp']) ? (int)$data['pts_opp'] : 0;
        $periods = isset($data['periods']) ? (int)$data['periods'] : 2;
        $otPeriods = isset($data['ot']) ? (int)$data['ot'] : 0;

        // Only validate if scores are present
        if ($teamScore === 0 && $oppScore === 0) {
            return $errors;
        }

        // Calculate sum of regular period scores
        $teamPeriodSum = 0;
        $oppPeriodSum = 0;
        $hasPeriodData = false;

        for ($i = 1; $i <= $periods; $i++) {
            $teamKey = "period_{$i}_team";
            $oppKey = "period_{$i}_opponent";

            if (isset($data[$teamKey]) && $data[$teamKey] !== '') {
                $teamPeriodSum += (int)$data[$teamKey];
                $hasPeriodData = true;
            }
            if (isset($data[$oppKey]) && $data[$oppKey] !== '') {
                $oppPeriodSum += (int)$data[$oppKey];
                $hasPeriodData = true;
            }
        }

        // Calculate sum of overtime period scores
        $teamOtSum = 0;
        $oppOtSum = 0;

        for ($i = 1; $i <= $otPeriods; $i++) {
            $teamKey = "overtime_{$i}_team";
            $oppKey = "overtime_{$i}_opponent";

            if (isset($data[$teamKey]) && $data[$teamKey] !== '') {
                $teamOtSum += (int)$data[$teamKey];
                $hasPeriodData = true;
            }
            if (isset($data[$oppKey]) && $data[$oppKey] !== '') {
                $oppOtSum += (int)$data[$oppKey];
                $hasPeriodData = true;
            }
        }

        // Only validate if period data was provided
        if (!$hasPeriodData) {
            return $errors;
        }

        // If there are OT periods, regular periods must be tied
        if ($otPeriods > 0 && $teamPeriodSum !== $oppPeriodSum) {
            $errors[] = sprintf(
                'Regular period scores must be tied when overtime periods exist. ' .
                'Team: %d, Opponent: %d',
                $teamPeriodSum,
                $oppPeriodSum
            );
        }

        // Total period + OT must equal final score
        $teamTotalPeriods = $teamPeriodSum + $teamOtSum;
        $oppTotalPeriods = $oppPeriodSum + $oppOtSum;

        if ($teamTotalPeriods !== $teamScore) {
            $errors[] = sprintf(
                'Team period scores (%d) must equal final team score (%d)',
                $teamTotalPeriods,
                $teamScore
            );
        }

        if ($oppTotalPeriods !== $oppScore) {
            $errors[] = sprintf(
                'Opponent period scores (%d) must equal final opponent score (%d)',
                $oppTotalPeriods,
                $oppScore
            );
        }

        return $errors;
    }

    /**
     * Save game EAV data from request data
     *
     * @param int $gameId Game ID
     * @param array $data Request data containing EAV fields
     * @return void
     */
    public function saveGameEavFromRequest(int $gameId, array $data): void
    {
        // Get sport information for this game
        /** @var \App\Model\Entity\Game $game */
        $game = $this->fetchTable('Games')->find()
            ->contain(['TeamSeason' => ['Teams' => ['Sports']]])
            ->where(['Games.id' => $gameId])
            ->first();

        /** @var \App\Model\Entity\TeamSeason|null $ts */
        $ts = $game?->team_season;
        $sportId = $ts?->team?->sport->id ?? null;

        if ($sportId) {
            /** @var \App\Model\Table\GameEavTable $gameEavTable */
            $gameEavTable = $this->fetchTable('GameEav');
            $gameEavTable->setSportConfigService($this->sportConfigService);

            // Pass game's periods and overtime values to generate appropriate EAV fields
            $periods = (string)($game->get('periods') ?: '2');
            $overtime = (string)($game->get('ot') ?: '0');
            $eavTemplate = $gameEavTable->getEavTemplateForSport($sportId, $periods, $overtime);

            // Save all EAV fields from the template
            foreach ($eavTemplate as $key => $fieldConfig) {
                $value = $data[$key] ?? null;
                if ($value !== null && $value !== '') {
                    $this->upsertGameEav($gameId, $key, (string)$value);
                }
            }

            // Also persist legacy period_*_mur / period_*_opp keys if present
            foreach ($data as $k => $v) {
                if ($v === null || $v === '') {
                    continue;
                }
                if (preg_match('/^period_\d+_(mur|opp)$/', (string)$k)) {
                    $this->upsertGameEav($gameId, (string)$k, (string)$v);
                }
            }
        } else {
            // Fallback to traditional period/official handling if no sport is found
            $this->saveTraditionalEavFromRequest($gameId, $data);
        }
    }

    /**
     * Fallback method for saving traditional EAV data when sport config is unavailable
     *
     * @param int $gameId Game ID
     * @param array $data Request data
     * @return void
     */
    private function saveTraditionalEavFromRequest(int $gameId, array $data): void
    {
        $periods = (int)($data['periods'] ?? 0);
        if ($periods > 0) {
            for ($i = 1; $i <= $periods; $i++) {
                $mur = $data['period_' . $i . '_mur'] ?? null;
                $opp = $data['period_' . $i . '_opp'] ?? null;
                if ($mur !== null && $mur !== '') {
                    $this->upsertGameEav($gameId, 'period_' . $i . '_mur', (string)$mur);
                }
                if ($opp !== null && $opp !== '') {
                    $this->upsertGameEav($gameId, 'period_' . $i . '_opp', (string)$opp);
                }
            }
        }

        for ($j = 1; $j <= 3; $j++) {
            $name = trim((string)($data['official_' . $j] ?? ''));
            if ($name !== '') {
                $this->upsertGameEav($gameId, 'official_' . $j, $name);
            }
        }
    }

    /**
     * Upsert a single EAV key/value for a game
     *
     * @param int $gameId Game id
     * @param string $key Attribute key
     * @param string $value Attribute value
     * @return void
     */
    private function upsertGameEav(int $gameId, string $key, string $value): void
    {
        /** @var \App\Model\Table\GameEavTable $table */
        $table = $this->fetchTable('GameEav');
        $entity = $table->find()->where(['game_id' => $gameId, 'key' => $key])->first();
        if ($entity) {
            $entity->set('value', $value);
        } else {
            $entity = $table->newEntity(['game_id' => $gameId, 'key' => $key, 'value' => $value]);
        }
        $table->save($entity);
    }
}
