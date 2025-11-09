<?php
declare(strict_types=1);

namespace App\Controller\Admin;

use Cake\Datasource\Exception\RecordNotFoundException;
use Cake\Http\Response;

/**
 * Admin Games Controller
 *
 * Manages games and associated values (game types, opponent, site/place) and
 * EAV attributes such as period scores and officials.
 *
 * @property \App\Model\Table\GamesTable $Games
 */
class GamesController extends AppController
{
    /**
     * SportConfigService instance for sport-aware configurations
     *
     * @var \App\Service\SportConfigService
     */
    protected \App\Service\SportConfigService $sportConfigService;

    /**
     * Initialize controller
     *
     * @return void
     */
    public function initialize(): void
    {
        parent::initialize();
        $this->sportConfigService = new \App\Service\SportConfigService();
    }

    /**
     * Unified AJAX meta endpoint.
     * Accepts one of:
     *  - game_id: load existing game, infer team_season/sport, include existing EAV values
     *  - team_season_id: load sport meta without existing values
     * Returns JSON: { success, sportId, sportName, configs, eavTemplate, values }
     *
     * @return \Cake\Http\Response|null
     */
    public function ajaxGameEavMeta(): ?Response
    {
        $this->request->allowMethod(['get']);
        $gameId = (int)$this->request->getQuery('game_id');
        $teamSeasonId = (int)$this->request->getQuery('team_season_id');
        $payload = ['success' => false];

        try {
            // (removed debug logging)
            $sportId = null;
            $sportName = null;
            $existingValues = [];

            if ($gameId) {
                /** @var \App\Model\Entity\Game $game */
                $game = $this->Games->find()
                    ->contain(['TeamSeason' => ['Teams' => ['Sports']]])
                    ->where(['Games.id' => $gameId])
                    ->firstOrFail();
                $teamSeasonId = (int)($game->get('team_season_id') ?? 0);
                /** @var \App\Model\Entity\TeamSeason|null $teamSeasonAssoc */
                $teamSeasonAssoc = $game->get('team_season');
                if ($teamSeasonAssoc && $teamSeasonAssoc->team && $teamSeasonAssoc->team->sport) {
                    $sportId = $teamSeasonAssoc->team->sport->id;
                    $sportName = $teamSeasonAssoc->team->sport->sport_name;
                }
                // Load existing EAV values
                $existingValues = $this->loadGameEavArray($gameId);
                // Legacy mapping: period_X_mur/opp -> period_X_team/opponent (overwrite if new key missing or empty)
                foreach ($existingValues as $k => $v) {
                    if (preg_match('/^period_(\d+)_mur$/', $k, $m)) {
                        $new = 'period_' . $m[1] . '_team';
                        if (
                            !isset($existingValues[$new]) ||
                            $existingValues[$new] === '' ||
                            $existingValues[$new] === null
                        ) {
                            $existingValues[$new] = $v;
                        }
                    } elseif (preg_match('/^period_(\d+)_opp$/', $k, $m)) {
                        $new = 'period_' . $m[1] . '_opponent';
                        if (
                            !isset($existingValues[$new]) ||
                            $existingValues[$new] === '' ||
                            $existingValues[$new] === null
                        ) {
                            $existingValues[$new] = $v;
                        }
                    }
                }
                // (removed debug logging)
            }

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

            $configs = [];
            $eavTemplate = [];
            if ($sportId) {
                /** @var \App\Model\Table\SportConfigsTable $sportConfigsTable */
                $sportConfigsTable = $this->fetchTable('SportConfigs');
                $configs = $sportConfigsTable->getFormattedConfigsForSport($sportId);

                /** @var \App\Model\Table\GameEavTable $gameEavTable */
                $gameEavTable = $this->fetchTable('GameEav');

                // Inject SportConfigService into GameEavTable
                $gameEavTable->setSportConfigService($this->sportConfigService);

                // If we have a game, use its periods/ot values; otherwise use defaults
                $periods = '2';
                $overtime = '0';
                if (isset($game)) {
                    $periods = (string)($game->get('periods') ?: '2');
                    $overtime = (string)($game->get('ot') ?: '0');
                }
                $eavTemplate = $gameEavTable->getEavTemplateForSport($sportId, $periods, $overtime);
            }

            // If neither a valid game nor team season resolved a sport, treat as error
            if (!$sportId) {
                $payload = [
                    'success' => false,
                    'error' => 'Missing or invalid parameters',
                ];
            } else {
                $payload = [
                    'success' => true,
                    'sportId' => $sportId,
                    'sportName' => $sportName,
                    'configs' => $configs,
                    'eavTemplate' => $eavTemplate,
                    'values' => $existingValues,
                ];

                // If HTML fragment requested, render the server-side element and return HTML
                $format = $this->request->getQuery('format');
                if ($format === 'html') {
                    // Prepare variables expected by the element
                    $this->set(compact('eavTemplate'));
                    $eav = $existingValues;
                    $legacyMappedEav = $existingValues; // element expects this variable optionally
                    $sportName = $sportName;
                    $html = $this->viewBuilder()
                        ->setClassName('App\View\AppView')
                        ->build()
                        ->element(
                            'Admin/Games/sport_specific_fields',
                            compact('eavTemplate', 'eav', 'legacyMappedEav', 'sportName')
                        );

                    return $this->response->withType('text/html')->withStringBody($html);
                }
            }
        } catch (\Throwable $e) {
            $payload['error'] = 'Lookup failed';
        }

        return $this->response
            ->withType('application/json')
            ->withStringBody(json_encode($payload));
    }

    /**
     * List games with associations.
     */
    public function index(): void
    {
        $query = $this->Games->find()
            ->contain(['TeamSeason' => ['Teams', 'Seasons'], 'GameTypes', 'Opponents', 'Sites' => ['Places'], 'Places'])
            ->orderByDesc('game_date');

        // Filter by team_season_id if provided
        $teamSeasonId = $this->request->getQuery('team_season_id');
        if ($teamSeasonId) {
            $query->where(['Games.team_season_id' => $teamSeasonId]);

            // Get team season info for breadcrumb/title
            $teamSeason = $this->fetchTable('TeamSeasons')->get($teamSeasonId, contain: ['Teams', 'Seasons']);
            $this->set('teamSeason', $teamSeason);
        }

        $games = $query->all();
        $this->set(compact('games'));
    }

    /**
     * View a game with associations and EAV attributes.
     *
     * @param string $id Game ID
     */
    public function view(string $id): void
    {
        /** @var \App\Model\Entity\Game $game */
        $game = $this->Games->find()
            ->contain([
                'TeamSeason' => ['Teams' => ['Sports'], 'Seasons'], 'GameTypes', 'Opponents',
                'Sites' => ['Places'], 'Places',
            ])
            ->where(['Games.id' => $id])
            ->firstOrFail();
        $eav = $this->loadGameEavArray((int)$id);

        // Initialize stat variables with defaults
        $teamBoxStats = [];
        $opponentBoxStats = [];
        $teamPeriodStats = [];
        $opponentPeriodStats = [];
        $playerStats = [];
        $opponentPlayerStats = [];
        $teamTeamStats = null;
        $opponentTeamStats = null;
        $hasSportConfig = false;
        $hasPeriodStats = false;

        if ($game->team_season && $game->team_season->team && $game->team_season->team->sport) {
            $sportId = $game->team_season->team->sport->id;
            $sportName = strtolower($game->team_season->team->sport->sport_name);
            $hasSportConfig = true;

            // Delegate sport-specific stats loading to service
            if ($sportName === 'basketball') {
                $basketballService = new \App\Service\BasketballStatsService();
                $basketballStats = $basketballService->getGameStats((int)$id);

                if ($basketballStats) {
                    $teamBoxStats = $basketballStats['teamBoxStats'] ?? [];
                    $opponentBoxStats = $basketballStats['opponentBoxStats'] ?? [];
                    $teamPeriodStats = $basketballStats['teamPeriodStats'] ?? [];
                    $opponentPeriodStats = $basketballStats['opponentPeriodStats'] ?? [];
                    $playerStats = $basketballStats['playerStats'] ?? [];
                    $opponentPlayerStats = $basketballStats['opponentPlayerStats'] ?? [];
                    $teamTeamStats = $basketballStats['teamTeamStats'] ?? null;
                    $opponentTeamStats = $basketballStats['opponentTeamStats'] ?? null;
                    $hasPeriodStats = $basketballStats['hasPeriodStats'] ?? false;
                }
            }

            // Get field labels from SportConfigService
            $fieldLabels = $this->sportConfigService->getAllFieldLabels($sportId);
            $this->set('fieldLabels', $fieldLabels);
        }

        $this->setSportAwareData($game);
        $this->set(compact(
            'game',
            'eav',
            'teamBoxStats',
            'opponentBoxStats',
            'teamPeriodStats',
            'opponentPeriodStats',
            'playerStats',
            'opponentPlayerStats',
            'teamTeamStats',
            'opponentTeamStats',
            'hasSportConfig',
            'hasPeriodStats',
        ));
    }

    /**
     * Add a new game.
     */
    public function add(): ?Response
    {
        // Require team_season_id for add
        $teamSeasonId = (int)$this->request->getQuery('team_season_id');
        if (!$teamSeasonId) {
            $this->Flash->error(__('You must add a game from within a team season.'));

            return $this->redirect(['prefix' => 'Admin', 'controller' => 'TeamSeasons', 'action' => 'index']);
        }

        /** @var \App\Model\Entity\Game $game */
        $game = $this->Games->newEmptyEntity();
        $game->set('team_season_id', $teamSeasonId);

        // Get sportId from team season
        /** @var \App\Model\Entity\TeamSeason $teamSeason */
        $teamSeason = $this->fetchTable('TeamSeasons')->find()
            ->contain(['Teams' => ['Sports']])
            ->where(['TeamSeasons.id' => $teamSeasonId])
            ->firstOrFail();
        $sportId = $teamSeason->team->sport->id;
        $periods = $game->periods ?? '2';
        $overtime = $game->ot ?? '0';
        /** @var \App\Model\Table\GameEavTable $gameEavTable */
        $gameEavTable = $this->fetchTable('GameEav');
        $gameEavTable->setSportConfigService($this->sportConfigService);
        $eavTemplate = $gameEavTable->getEavTemplateForSport($sportId, $periods, $overtime);
        $eav = [];

        if ($this->request->is('post')) {
            $data = $this->request->getData();
            $this->normalizeAssociatedInlineCreate($data);

            // Auto-calculate W/L based on scores
            $data = $this->calculateWinLoss($data);

            // Validate period scores if present
            $eavErrors = $this->validatePeriodScores($data);
            if (!empty($eavErrors)) {
                foreach ($eavErrors as $error) {
                    $this->Flash->error($error);
                }
                $this->setFormLists();
                $this->setSportAwareData($game);
                $eav = $data; // Keep user input for re-display
                $this->set(compact('game', 'eav', 'eavTemplate'));

                return null;
            }

            $game = $this->Games->patchEntity($game, $data);
            if ($this->Games->save($game)) {
                $this->saveGameEavFromRequest((int)$game->get('id'));
                $this->Flash->success(__('The game has been saved.'));

                return $this->redirect([
                    'prefix' => 'Admin',
                    'controller' => 'TeamSeasons',
                    'action' => 'view',
                    $teamSeasonId,
                ]);
            }
            // Validation errors are handled via Flash messages and form re-rendering
            $this->Flash->error(__('The game could not be saved. Please, try again.'));
        }

        $this->setFormLists();
        $this->setSportAwareData($game);
        $this->set(compact('game', 'eav', 'eavTemplate'));

        return null;
    }

    /**
     * Edit a game.
     *
     * @param string $id Game ID
     */
    public function edit(string $id): ?Response
    {
        /** @var \App\Model\Entity\Game $game */
        $game = $this->Games->find()
            ->contain(['TeamSeason' => ['Teams' => ['Sports']]])
            ->where(['Games.id' => $id])
            ->firstOrFail();

        if ($this->request->is(['patch', 'post', 'put'])) {
            $data = $this->request->getData();
            $this->normalizeAssociatedInlineCreate($data);

            // Auto-calculate W/L based on scores
            $data = $this->calculateWinLoss($data);

            // Validate period scores if present
            $eavErrors = $this->validatePeriodScores($data);
            if (!empty($eavErrors)) {
                foreach ($eavErrors as $error) {
                    $this->Flash->error($error);
                }
                $eav = $this->loadGameEavArray((int)$id);
                // Merge in the new data for re-display
                foreach ($data as $key => $value) {
                    if (strpos($key, 'period_') === 0 || strpos($key, 'overtime_') === 0) {
                        $eav[$key] = $value;
                    }
                }
                $this->setFormLists();
                $this->setSportAwareData($game);
                $this->set(compact('game', 'eav'));

                return null;
            }

            $game = $this->Games->patchEntity($game, $data);
            if ($this->Games->save($game)) {
                $this->saveGameEavFromRequest((int)$game->get('id'));
                $this->Flash->success(__('The game has been saved.'));

                // Redirect back to team season if we have the context
                $teamSeasonId = $game->get('team_season_id');
                if ($teamSeasonId) {
                    return $this->redirect([
                        'prefix' => 'Admin', 'controller' => 'TeamSeasons', 'action' => 'view', $teamSeasonId,
                    ]);
                }

                return $this->redirect(['action' => 'index']);
            }
            // Validation errors are handled via Flash messages and form re-rendering
            $this->Flash->error(__('The game could not be saved. Please, try again.'));
        }

        $eav = $this->loadGameEavArray((int)$id);
        $this->setFormLists();
        $this->setSportAwareData($game);
        $this->set(compact('game', 'eav'));

        return null;
    }

    /**
     * Delete a game.
     */
    public function delete(string $id): Response
    {
        $this->request->allowMethod(['post', 'delete']);
        $game = $this->Games->get($id);
        if ($this->Games->delete($game)) {
            $this->Flash->success(__('The game has been deleted.'));
        } else {
            $this->Flash->error(__('The game could not be deleted. Please, try again.'));
        }

        return $this->redirect(['action' => 'index']);
    }

    /**
     * Bulk action dispatcher for games.
     */
    public function bulk(): Response
    {
        $action = $this->request->getData('bulk_action');
        if ($action === 'delete') {
            return $this->bulkDelete();
        }

        $this->Flash->error('Invalid bulk action.');

        return $this->redirect(['action' => 'index']);
    }

    /**
     * Bulk delete selected games.
     */
    public function bulkDelete(): Response
    {
        $this->request->allowMethod(['post']);
        $ids = (array)$this->request->getData('game_ids');
        $ids = array_values(array_filter($ids, fn($v) => $v !== '' && $v !== null && ctype_digit((string)$v)));

        if (empty($ids)) {
            $this->Flash->error('No games selected for deletion.');

            return $this->redirect(['action' => 'index']);
        }

        // Get team_season_id before deletion for redirect context
        $teamSeasonId = null;
        try {
            $firstGame = $this->Games->get($ids[0]);
            $teamSeasonId = $firstGame->get('team_season_id');
        } catch (RecordNotFoundException $e) {
            // Game doesn't exist, ignore
        }

        $deleted = 0;
        foreach ($ids as $id) {
            try {
                $entity = $this->Games->get($id);
            } catch (RecordNotFoundException $e) {
                continue;
            }
            if ($this->Games->delete($entity)) {
                $deleted++;
            }
        }

        if ($deleted > 0) {
            $this->Flash->success(__('Deleted {0} game(s).', $deleted));
        } else {
            $this->Flash->error(__('No games were deleted.'));
        }

        if ($teamSeasonId) {
            return $this->redirect([
                'prefix' => 'Admin', 'controller' => 'TeamSeasons', 'action' => 'view', $teamSeasonId,
            ]);
        }

        return $this->redirect(['action' => 'index']);
    }

    /**
     * Build lists for select inputs and provide inline create options.
     */
    private function setFormLists(): void
    {
        $teamSeasons = $this->fetchTable('TeamSeasons')->find()
            ->contain(['Teams' => ['Sports'], 'Seasons'])
            ->orderByDesc('Seasons.start')
            ->all();
        $teamSeasonList = [];
        foreach ($teamSeasons as $ts) {
            /** @var \App\Model\Entity\TeamSeason $ts */
            $sportName = $ts->team->sport->sport_name ?? 'Unknown';
            $label = sprintf(
                '%s (%s) — %s-%s',
                ($ts->team->team_name ?? 'Team'),
                $sportName,
                ($ts->season->start ?? ''),
                ($ts->season->end ?? '')
            );
            $teamSeasonList[$ts->id] = $label;
        }

        $gameTypes = $this->fetchTable('GameTypes')->find('list')->all();
        $opponents = $this->fetchTable('Opponents')->find('list')->all();
        $places = $this->fetchTable('Places')->find('list')->all();
        $sites = $this->fetchTable('Sites')->find('list')->all();

        $sports = $this->fetchTable('Sports')->find('list')->all(); // for opponent/site creation helpers

        $this->set(compact('teamSeasonList', 'gameTypes', 'opponents', 'places', 'sites', 'sports'));
    }

    /**
     * Set sport-aware data for game forms based on the selected team's sport.
     *
     * @param \App\Model\Entity\Game $game Game entity
     * @return void
     */
    private function setSportAwareData(\App\Model\Entity\Game $game): void
    {
        $sportId = null;
        $sportName = 'Unknown';

        // Get sport information from team season
        if ($game->get('team_season_id')) {
            /** @var \App\Model\Entity\TeamSeason $teamSeason */
            $teamSeason = $this->fetchTable('TeamSeasons')->find()
                ->contain(['Teams' => ['Sports']])
                ->where(['TeamSeasons.id' => $game->get('team_season_id')])
                ->first();
            if ($teamSeason && $teamSeason->team && $teamSeason->team->sport) {
                $sportId = $teamSeason->team->sport->id;
                $sportName = $teamSeason->team->sport->sport_name;
            }
        }

        // Get sport-specific configurations
        $sportConfigs = [];
        $eavTemplate = [];
        if ($sportId) {
            /** @var \App\Model\Table\SportConfigsTable $sportConfigsTable */
            $sportConfigsTable = $this->fetchTable('SportConfigs');
            $sportConfigs = $sportConfigsTable->getFormattedConfigsForSport($sportId);

            /** @var \App\Model\Table\GameEavTable $gameEavTable */
            $gameEavTable = $this->fetchTable('GameEav');
            $gameEavTable->setSportConfigService($this->sportConfigService);
            // Pass game's periods and overtime values to generate appropriate EAV fields
            $periods = (string)($game->get('periods') ?: '2');
            $overtime = (string)($game->get('ot') ?: '0');
            $eavTemplate = $gameEavTable->getEavTemplateForSport($sportId, $periods, $overtime);

            // When editing, map legacy stored keys (period_X_mur/opp) to new naming (period_X_team/opponent)
            if ($game->id) {
                $existing = $this->loadGameEavArray((int)$game->id);
                foreach ($existing as $k => $v) {
                    if (preg_match('/^period_(\d+)_mur$/', $k, $m)) {
                        $newKey = 'period_' . $m[1] . '_team';
                        if (!isset($existing[$newKey])) {
                            $existing[$newKey] = $v;
                        }
                    } elseif (preg_match('/^period_(\d+)_opp$/', $k, $m)) {
                        $newKey = 'period_' . $m[1] . '_opponent';
                        if (!isset($existing[$newKey])) {
                            $existing[$newKey] = $v;
                        }
                    }
                }
                // Expose mapped values separately so form element can access
                $this->set('legacyMappedEav', $existing);
            }
        }

        $this->set(compact('sportId', 'sportName', 'sportConfigs', 'eavTemplate'));
    }

    /**
     * Persist EAV attributes from request using sport-aware configuration.
     *
     * This method saves both traditional period scores and officials, as well as
     * any sport-specific EAV fields defined in the sport configuration.
     */
    private function saveGameEavFromRequest(int $gameId): void
    {
        $data = $this->request->getData();

        // Get sport information for this game
        /** @var \App\Model\Entity\Game $game */
        $game = $this->Games->find()
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

            // Also persist legacy period_*_mur / period_*_opp keys if present so older tests & tools remain stable.
            // This is a transitional compatibility layer and can be removed once legacy keys are fully migrated.
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
     * Fallback method for saving traditional EAV data when sport config is unavailable.
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
     * Normalize inline creation payloads for associated entities.
     *
     * Supports creating Opponent, Place (+ Site), Site alone, and GameType.
     * Incoming $data can include sub-arrays like new_opponent[name], etc.
     */
    private function normalizeAssociatedInlineCreate(array &$data): void
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
     * Load EAV attributes into an array for a game.
     *
     * @param int $gameId Game id
     * @return array<string, mixed>
     */
    private function loadGameEavArray(int $gameId): array
    {
        $rows = $this->fetchTable('GameEav')->find()
            ->select(['key', 'value'])
            ->where(['game_id' => $gameId])
            ->all();
        $attributes = [];
        foreach ($rows as $row) {
            $attributes[$row->key] = $row->value;
        }

        return $attributes;
    }

    /**
     * Upsert a single EAV key/value for a game.
     *
     * @param int $gameId Game id
     * @param string $key Attribute key
     * @param string $value Attribute value
     * @return void
     */
    private function upsertGameEav(int $gameId, string $key, string $value): void
    {
        $table = $this->fetchTable('GameEav');
        $entity = $table->find()->where(['game_id' => $gameId, 'key' => $key])->first();
        if ($entity) {
            $entity->set('value', $value);
        } else {
            $entity = $table->newEntity(['game_id' => $gameId, 'key' => $key, 'value' => $value]);
        }
        $table->save($entity);
    }

    /**
     * Auto-calculate W/L based on game scores
     *
     * @param array $data Game data
     * @return array Modified data with W/L set
     */
    private function calculateWinLoss(array $data): array
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
    private function validatePeriodScores(array $data): array
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
            $errors[] = __(
                'Regular period scores must be tied when overtime periods exist. ' .
                'Team: {0}, Opponent: {1}',
                $teamPeriodSum,
                $oppPeriodSum
            );
        }

        // Total period + OT must equal final score
        $teamTotalPeriods = $teamPeriodSum + $teamOtSum;
        $oppTotalPeriods = $oppPeriodSum + $oppOtSum;

        if ($teamTotalPeriods !== $teamScore) {
            $errors[] = __(
                'Team period scores ({0}) must equal final team score ({1})',
                $teamTotalPeriods,
                $teamScore
            );
        }

        if ($oppTotalPeriods !== $oppScore) {
            $errors[] = __(
                'Opponent period scores ({0}) must equal final opponent score ({1})',
                $oppTotalPeriods,
                $oppScore
            );
        }

        return $errors;
    }

}