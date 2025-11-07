<?php
declare(strict_types=1);

namespace App\Controller\Admin;

/**
 * StatBasketGameTeam Controller (Admin)
 *
 * Manages basketball team-level game statistics (dead ball rebounds, team violations, etc.).
 *
 * @property \App\Model\Table\StatBasketGameTeamTable $StatBasketGameTeam
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

            // Patch team stats
            if (isset($data['team'])) {
                $teamStats = $this->StatBasketGameTeam->patchEntity($teamStats, $data['team']);
            }

            // Patch opponent stats
            if (isset($data['opponent'])) {
                $opponentStats = $this->StatBasketGameTeam->patchEntity($opponentStats, $data['opponent']);
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
