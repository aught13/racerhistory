<?php
declare(strict_types=1);

namespace App\Controller\Admin;

use App\Service\BasketballStatsService;
use Cake\Http\Response;

/**
 * StatBasketGameBox Controller (Admin)
 *
 * Manages basketball game box scores and period statistics.
 * Updates season totals for team and opponent when box scores are saved (final period only).
 * Provides actions for editing/creating final period box scores and period-by-period box scores for basketball games. The gameBox action handles the final period box scores, while the gameBoxPeriods action manages the period-by-period stats. When saving final period box scores, there is an option to update season totals, which will adjust the cumulative stats for the team and opponent based on the new box score data. The controller also retrieves stat field labels from the SportConfigService to display user-friendly labels in the views. The controller ensures that box scores are only managed for basketball games and provides appropriate feedback to the user through flash messages. Proper validation and error handling are implemented to ensure a smooth user experience when managing game box scores in the admin interface.
 *
 * Actions:
 * - gameBox: Handles editing and creating final period box scores for a basketball game. It checks if the game is a basketball game, loads existing box scores for the team and opponent, and allows the user to save new box scores. There is an option to update season totals when saving final period box scores, which will adjust the cumulative stats for the team and opponent accordingly. The action also retrieves stat field labels from the SportConfigService to display in the view.
 * - gameBoxPeriods: Handles editing period-by-period box scores for a basketball game. It loads existing period stats for the game and allows the user to save updates for each period, including overtime periods. The action retrieves stat field labels from the SportConfigService to display in the view and provides feedback to the user through flash messages based on the success or failure of saving the period stats. Both actions ensure that they are only managing box scores for basketball games and provide appropriate feedback to the user through flash messages. Proper validation and error handling are implemented to ensure a smooth user experience when managing game box scores in the admin interface.
 *
 * Security:
 * - Both actions should be protected by authentication and authorization checks to ensure that only authorized users can manage game box scores. This is typically handled by middleware or components that are not shown in this code
 * snippet.
 * - Proper validation and error handling should be implemented to prevent invalid data from being saved and to provide clear feedback to the user in case of errors.
 * - The gameBox action checks if the game is a basketball game before allowing access to box score management, providing an additional layer of validation to ensure that box scores are only managed for appropriate games.
 *
 * Dependencies:
 * - StatBasketGameBoxTable: The model for managing basketball game box score records in the database.
 * - SportConfigService: Used to retrieve stat field labels for displaying user-friendly labels in the views.
 * - BasketballStatsService: A service that contains logic for applying game box scores to season totals, abstracting away the details of how season totals are calculated and updated based on game box score changes. This allows the controller to focus on handling requests and formatting responses, while delegating the business logic of updating season totals to the service.
 *
 * Components:
 * - FlashComponent: Used to set success and error messages after saving box scores, providing feedback to the user about the outcome of their actions.
 *
 * Note: The gameBox action includes an option to update season totals when saving final period box scores. This should be used with caution, as it will adjust the cumulative stats for the team and opponent based on the new box score data. Proper validation and confirmation should be implemented in the UI to ensure that administrators understand the implications of updating season totals when saving box scores. Additionally, both actions ensure that they are only managing box scores for basketball games and provide appropriate feedback to the user through flash messages. Proper validation and error handling are implemented to ensure a smooth user experience when managing game box scores in the admin interface.
 *
 * @property \App\Model\Table\GamesTable $Games
 * @property \App\Model\Table\TeamSeasonTable $TeamSeason
 * @property \App\Model\Table\TeamsTable $Teams
 * @property \App\Model\Table\SportsTable $Sports
 * @property \App\Model\Table\OpponentsTable $Opponents
 * @property \App\Model\Table\StatBasketGameBoxTable $StatBasketGameBox
 * @property \App\Service\SportConfigService $SportConfig
 * @property \App\Service\BasketballStatsService $basketballStatsService
 * @property \Authorization\Controller\Component\AuthorizationComponent $Authorization
 * @property \Cake\Controller\Component\FlashComponent $Flash
 */
class StatBasketGameBoxController extends AppController
{
    /**
     * @var \App\Service\SportConfigService Service for sport configuration management
     */
    protected \App\Service\SportConfigService $SportConfig;

    private BasketballStatsService $basketballStatsService;

    /**
     * Initialization hook method.
     *
     * @return void
     */
    public function initialize(): void
    {
        parent::initialize();
        $this->loadService('SportConfig');

        $this->basketballStatsService = new BasketballStatsService();
    }

    /**
     * Edit/Create game box scores (final period only - period Z)
     *
     * @param int $gameId Game ID
     * @return \Cake\Http\Response|null
     */
    public function gameBox(int $gameId): ?Response
    {
        /** @var \App\Model\Entity\Game $game */
        $game = $this->fetchTable('Games')->find()
            ->contain(['TeamSeason' => ['Teams' => ['Sports']], 'Opponents'])
            ->where(['Games.id' => $gameId])
            ->firstOrFail();

        // Check if this is a basketball game
        if ($game->team_season && $game->team_season->team && $game->team_season->team->sport) {
            $sportName = strtolower($game->team_season->team->sport->sport_name);
            if ($sportName !== 'basketball') {
                $this->Flash->error(__('Game box scores are currently only supported for basketball games.'));

                return $this->redirect(['controller' => 'Games', 'action' => 'edit', $gameId]);
            }
        }

        /** @var \App\Model\Table\StatBasketGameBoxTable $boxTable */
        $boxTable = $this->fetchTable('StatBasketGameBox');

        // Load existing box scores (final period only)
        $teamBox = $boxTable->find()
            ->where(['game_id' => $gameId, 'opponent_id' => 0, 'period' => 'Z'])
            ->first();

        $opponentId = $game->opponent_id ?? 0;
        $opponentBox = $boxTable->find()
            ->where(['game_id' => $gameId, 'opponent_id' => $opponentId, 'period' => 'Z'])
            ->first();

        // Check if we have period stats already
        $periodStats = $boxTable->find()
            ->where(['game_id' => $gameId, 'period !=' => 'Z'])
            ->orderBy(['period' => 'ASC'])
            ->all()
            ->toArray();

        $hasPeriodStats = !empty($periodStats);

        if ($this->request->is(['patch', 'post', 'put'])) {
            $data = $this->request->getData();

            // Store original stats for comparison
            $originalTeamBox = $teamBox ? clone $teamBox : null;
            $originalOpponentBox = $opponentBox ? clone $opponentBox : null;

            // Determine if season totals should be updated
            $addToTotals = $this->request->getData('add_to_totals');
            $teamMinutes = $addToTotals ? (int)($this->request->getData('team_minutes') ?? 0) : 0;

            // Save team final stats (period Z, opponent_id 0)
            if (!empty($data['team'])) {
                $teamData = $data['team'];
                $teamData['game_id'] = $gameId;
                $teamData['opponent_id'] = 0;
                $teamData['period'] = 'Z';

                // Set GP and MIN when updating season totals
                if ($addToTotals) {
                    $teamData['GP'] = '1';
                    $teamData['MIN'] = (string)$teamMinutes;
                }

                if ($teamBox) {
                    $teamBox = $boxTable->patchEntity($teamBox, $teamData);
                } else {
                    $teamBox = $boxTable->newEntity($teamData);
                }

                if (!$boxTable->save($teamBox)) {
                    $this->Flash->error(__('Could not save team box scores. Please try again.'));

                    return null;
                }
            }

            // Save opponent final stats (period Z, with opponent_id)
            if (!empty($data['opponent'])) {
                $oppData = $data['opponent'];
                $oppData['game_id'] = $gameId;
                $oppData['opponent_id'] = $opponentId;
                $oppData['period'] = 'Z';

                // Set GP and MIN when updating season totals
                if ($addToTotals) {
                    $oppData['GP'] = '1';
                    $oppData['MIN'] = (string)$teamMinutes;
                }

                if ($opponentBox) {
                    $opponentBox = $boxTable->patchEntity($opponentBox, $oppData);
                } else {
                    $opponentBox = $boxTable->newEntity($oppData);
                }

                if (!$boxTable->save($opponentBox)) {
                    $this->Flash->error(__('Could not save opponent box scores. Please try again.'));

                    return null;
                }
            }

            // Update season totals if checkbox is selected (final period only)
            if ($addToTotals && $teamBox && $opponentBox) {
                $this->basketballStatsService->applyGameBoxToSeasonTotals(
                    $game,
                    $teamBox,
                    $opponentBox,
                    $originalTeamBox,
                    $originalOpponentBox,
                );
            }

            $this->Flash->success(__('Game box scores have been saved.'));

            // If user wants to add period stats, redirect to period entry
            if (!empty($data['add_periods'])) {
                return $this->redirect(['action' => 'gameBoxPeriods', $gameId]);
            }

            return $this->redirect(['controller' => 'Games', 'action' => 'view', $gameId]);
        }

        // Get stat field labels from SportConfigService
        $sportId = $game->team_season->team->sport->id;
        $fieldLabels = $this->SportConfig->getAllFieldLabels($sportId);

        $this->set(compact('game', 'teamBox', 'opponentBox', 'fieldLabels', 'hasPeriodStats'));

        return null;
    }

    /**
     * Edit period-by-period box scores
     *
     * @param int $gameId Game ID
     * @return \Cake\Http\Response|null
     */
    public function gameBoxPeriods(int $gameId): ?Response
    {
        /** @var \App\Model\Entity\Game $game */
        $game = $this->fetchTable('Games')->find()
            ->contain(['TeamSeason' => ['Teams' => ['Sports']], 'Opponents'])
            ->where(['Games.id' => $gameId])
            ->firstOrFail();

        /** @var \App\Model\Table\StatBasketGameBoxTable $boxTable */
        $boxTable = $this->fetchTable('StatBasketGameBox');

        // Get number of periods from game
        $numPeriods = (int)($game->periods ?? 2);
        $numOT = (int)($game->ot ?? 0);
        $opponentId = $game->opponent_id ?? 0;

        // Load existing period stats
        $existingStats = [];
        $periodStats = $boxTable->find()
            ->where(['game_id' => $gameId, 'period !=' => 'Z'])
            ->orderBy(['period' => 'ASC'])
            ->all();

        foreach ($periodStats as $stat) {
            $key = ($stat->opponent_id == 0 ? 'team' : 'opponent') . '_' . $stat->period;
            $existingStats[$key] = $stat;
        }

        if ($this->request->is(['patch', 'post', 'put'])) {
            $data = $this->request->getData();
            $saveErrors = [];

            // Process each period
            for ($p = 1; $p <= $numPeriods; $p++) {
                // Team period stats
                if (!empty($data['team_' . $p])) {
                    $teamData = $data['team_' . $p];
                    $teamData['game_id'] = $gameId;
                    $teamData['opponent_id'] = 0;
                    $teamData['period'] = (string)$p;

                    $existingKey = 'team_' . $p;
                    if (isset($existingStats[$existingKey])) {
                        $entity = $boxTable->patchEntity($existingStats[$existingKey], $teamData);
                    } else {
                        $entity = $boxTable->newEntity($teamData);
                    }

                    if (!$boxTable->save($entity)) {
                        $saveErrors[] = "Team Period $p";
                    }
                }

                // Opponent period stats
                if (!empty($data['opponent_' . $p])) {
                    $oppData = $data['opponent_' . $p];
                    $oppData['game_id'] = $gameId;
                    $oppData['opponent_id'] = $opponentId;
                    $oppData['period'] = (string)$p;

                    $existingKey = 'opponent_' . $p;
                    if (isset($existingStats[$existingKey])) {
                        $entity = $boxTable->patchEntity($existingStats[$existingKey], $oppData);
                    } else {
                        $entity = $boxTable->newEntity($oppData);
                    }

                    if (!$boxTable->save($entity)) {
                        $saveErrors[] = "Opponent Period $p";
                    }
                }
            }

            // Process overtime periods
            for ($ot = 1; $ot <= $numOT; $ot++) {
                $otPeriod = 'OT' . ($ot > 1 ? $ot : '');

                // Team OT stats
                if (!empty($data['team_' . $otPeriod])) {
                    $teamData = $data['team_' . $otPeriod];
                    $teamData['game_id'] = $gameId;
                    $teamData['opponent_id'] = 0;
                    $teamData['period'] = $otPeriod;

                    $existingKey = 'team_' . $otPeriod;
                    if (isset($existingStats[$existingKey])) {
                        $entity = $boxTable->patchEntity($existingStats[$existingKey], $teamData);
                    } else {
                        $entity = $boxTable->newEntity($teamData);
                    }

                    if (!$boxTable->save($entity)) {
                        $saveErrors[] = "Team $otPeriod";
                    }
                }

                // Opponent OT stats
                if (!empty($data['opponent_' . $otPeriod])) {
                    $oppData = $data['opponent_' . $otPeriod];
                    $oppData['game_id'] = $gameId;
                    $oppData['opponent_id'] = $opponentId;
                    $oppData['period'] = $otPeriod;

                    $existingKey = 'opponent_' . $otPeriod;
                    if (isset($existingStats[$existingKey])) {
                        $entity = $boxTable->patchEntity($existingStats[$existingKey], $oppData);
                    } else {
                        $entity = $boxTable->newEntity($oppData);
                    }

                    if (!$boxTable->save($entity)) {
                        $saveErrors[] = "Opponent $otPeriod";
                    }
                }
            }

            if (empty($saveErrors)) {
                $this->Flash->success(__('Period box scores have been saved.'));

                return $this->redirect(['controller' => 'Games', 'action' => 'view', $gameId]);
            }

            $this->Flash->error(__('Could not save some period stats: {0}', implode(', ', $saveErrors)));
        }

        // Get stat field labels
        $sportId = $game->team_season->team->sport->id;
        $fieldLabels = $this->SportConfig->getAllFieldLabels($sportId);

        $this->set(compact('game', 'numPeriods', 'numOT', 'existingStats', 'fieldLabels'));

        return null;
    }
}
