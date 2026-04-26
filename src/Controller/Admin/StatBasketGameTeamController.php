<?php
declare(strict_types=1);

namespace App\Controller\Admin;

/**
 * StatBasketGameTeam Controller (Admin)
 *
 * Manages basketball team-level game statistics (dead ball rebounds, team violations, etc.).
 * The view action displays the stats for a specific game, while the edit action allows updating those stats. Both actions retrieve the relevant team and opponent stats based on the game ID and whether the stats are for the team or the opponent. The edit action handles form submissions to update the stats, ensuring that both team and opponent stats are saved together for consistency. Proper flash messages are set to inform the user of success or failure during the save process, and after a successful edit, the user is redirected back to the view page for the same game.
 *
 * Actions:
 * - view: Displays the team and opponent stats for a specific game, along with the game details.
 * - edit: Allows editing of the team and opponent stats for a specific game. Handles form submissions to update the stats and provides feedback through flash messages.
 *
 * Security:
 * - Both actions should be protected by authentication and authorization checks to ensure that only authorized users can view and edit game stats. This is typically handled by middleware or components that are not shown in this code snippet.
 * - The edit action should validate the input data to prevent invalid or malicious data from being saved to the database. Proper error handling and feedback mechanisms should be implemented to inform the user of any issues during the save process.
 *
 * Dependencies:
 * - SportConfigService: Used to retrieve sport-specific configurations that may affect how stats are displayed or edited.
 * - BasketballStatsService: Provides methods for calculating and managing basketball-specific statistics, abstracting away the details of these operations from the controller.
 *
 * Components:
 * - FlashComponent: Used to set success and error messages after attempting to save stats in the edit action, providing feedback to the user about the outcome of their actions.
 *
 * Note: The view and edit actions rely on the game ID to retrieve the relevant stats, and they also fetch the game details to provide context for the stats being displayed or edited. The edit action ensures that both team and opponent stats are handled together to maintain consistency in the data, and it provides appropriate feedback to the user based on the success or failure of the save operation. Proper validation and error handling should be implemented in the edit action to ensure data integrity and a good user experience.
 *
 * @property \App\Model\Table\StatBasketGameTeamTable $StatBasketGameTeam
 * @property \App\Service\SportConfigService $SportConfig
 * @property \App\Service\BasketballStatsService $basketballStatsService
 * @property \Authorization\Controller\Component\AuthorizationComponent $Authorization
 * @property \Cake\Controller\Component\FlashComponent $Flash
 */
class StatBasketGameTeamController extends AppController
{
    /**
     * View method - display team stats for a specific game
     *
     * @param int $gameId Game ID
     * @return \Cake\Http\Response|null|void Renders view
     */
    public function view(int $gameId)
    {
        $teamStats = $this->StatBasketGameTeam
            ->find()
            ->where([
                'StatBasketGameTeam.game_id' => $gameId,
                'StatBasketGameTeam.opp' => 0,
            ])
            ->first();

        $opponentStats = $this->StatBasketGameTeam
            ->find()
            ->where([
                'StatBasketGameTeam.game_id' => $gameId,
                'StatBasketGameTeam.opp' => 1,
            ])
            ->first();

        $game = $this->fetchTable('Games')->get($gameId, contain: ['TeamSeason', 'Opponents']);

        $this->set(compact('teamStats', 'opponentStats', 'game'));
    }

    /**
     * Edit method - update team stats (both team and opponent)
     *
     * @param int $gameId Game ID
     * @return \Cake\Http\Response|null|void Redirects on successful edit, renders view otherwise.
     */
    public function edit(int $gameId)
    {
        // Get or create team stats
        $teamStats = $this->StatBasketGameTeam
            ->find()
            ->where([
                'StatBasketGameTeam.game_id' => $gameId,
                'StatBasketGameTeam.opp' => 0,
            ])
            ->first();

        if (!$teamStats) {
            $teamStats = $this->StatBasketGameTeam->newEmptyEntity();
            assert($teamStats instanceof \App\Model\Entity\StatBasketGameTeam);
            $teamStats->game_id = $gameId;
            $teamStats->opp = 0;
        }

        // Get or create opponent stats
        $opponentStats = $this->StatBasketGameTeam
            ->find()
            ->where([
                'StatBasketGameTeam.game_id' => $gameId,
                'StatBasketGameTeam.opp' => 1,
            ])
            ->first();

        if (!$opponentStats) {
            $opponentStats = $this->StatBasketGameTeam->newEmptyEntity();
            assert($opponentStats instanceof \App\Model\Entity\StatBasketGameTeam);
            $opponentStats->game_id = $gameId;
            $opponentStats->opp = 1;
        }

        if ($this->request->is(['patch', 'post', 'put'])) {
            $data = $this->request->getData();

            // Patch team stats, ensuring game_id and opp are preserved
            if (isset($data['team'])) {
                $teamData = $data['team'];
                $teamData['game_id'] = $gameId;
                $teamData['opp'] = 0;
                $teamStats = $this->StatBasketGameTeam->patchEntity($teamStats, $teamData);
            }

            // Patch opponent stats, ensuring game_id and opp are preserved
            if (isset($data['opponent'])) {
                $opponentData = $data['opponent'];
                $opponentData['game_id'] = $gameId;
                $opponentData['opp'] = 1;
                $opponentStats = $this->StatBasketGameTeam->patchEntity($opponentStats, $opponentData);
            }

            $success = true;

            // Save both entities
            if (!$this->StatBasketGameTeam->save($teamStats)) {
                $success = false;
                $this->Flash->error(__('The team stats could not be saved. Please, try again.'));
            }

            if (!$this->StatBasketGameTeam->save($opponentStats)) {
                $success = false;
                $this->Flash->error(__('The opponent stats could not be saved. Please, try again.'));
            }

            if ($success) {
                $this->Flash->success(__('The team stats have been saved.'));

                return $this->redirect(['action' => 'view', $gameId]);
            }
        }

        $game = $this->fetchTable('Games')->get($gameId, contain: ['TeamSeason', 'Opponents']);

        $this->set(compact('teamStats', 'opponentStats', 'game'));
    }
}
