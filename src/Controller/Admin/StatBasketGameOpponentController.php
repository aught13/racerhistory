<?php
declare(strict_types=1);

namespace App\Controller\Admin;

use Cake\Http\Response;

/**
 * StatBasketGameOpponent Controller (Admin)
 *
 * Manages basketball opponent player game statistics.
 *
 * @property \App\Model\Table\StatBasketGameOpponentTable $StatBasketGameOpponent
 */
class StatBasketGameOpponentController extends AppController
{
    /**
     * View method - display opponent stats for a specific game
     *
     * @param int $gameId Game ID
     * @return \Cake\Http\Response|null|void Renders view
     */
    public function view(int $gameId)
    {
        $stats = $this->StatBasketGameOpponent
            ->find()
            ->where(['StatBasketGameOpponent.game_id' => $gameId])
            ->orderBy(['jersey' => 'ASC'])
            ->all();

        $game = $this->fetchTable('Games')->get($gameId, contain: ['TeamSeason', 'Opponents']);

        $this->set(compact('stats', 'game'));
    }

    /**
     * Add method - create new opponent player stat entry
     *
     * @param int $gameId Game ID
     * @return \Cake\Http\Response|null|void Redirects on successful add, renders view otherwise.
     */
    public function add(int $gameId)
    {
        $stat = $this->StatBasketGameOpponent->newEmptyEntity();
        assert($stat instanceof \App\Model\Entity\StatBasketGameOpponent);
        $stat->game_id = $gameId;
        $stat->period = 'Z'; // Default to final stats
        $stat->GP = '1'; // Default games played to 1

        if ($this->request->is('post')) {
            $stat = $this->StatBasketGameOpponent->patchEntity($stat, $this->request->getData());
            if ($this->StatBasketGameOpponent->save($stat)) {
                /** @var \App\Model\Entity\StatBasketGameOpponent $stat */
                // Handle add-to-totals if checkbox was selected
                $addToTotals = $this->request->getData('add_to_totals');
                if ($addToTotals && $stat->period === 'Z') {
                    $this->addToSeasonTotals($stat, $gameId);
                }

                $this->Flash->success(__('The opponent player stat has been saved.'));

                return $this->redirect(['action' => 'view', $gameId]);
            }
            $this->Flash->error(__('The opponent player stat could not be saved. Please, try again.'));
        }

        $game = $this->fetchTable('Games')->get($gameId, contain: ['TeamSeason', 'Opponents']);
        assert($game instanceof \App\Model\Entity\Game);

        $this->set(compact('stat', 'game'));
    }

    /**
     * Edit method - update existing opponent player stat entry
     *
     * @param int $id Stat ID
     * @return \Cake\Http\Response|null|void Redirects on successful edit, renders view otherwise.
     */
    public function edit(int $id)
    {
        $stat = $this->StatBasketGameOpponent->get($id, contain: ['Games']);
        assert($stat instanceof \App\Model\Entity\StatBasketGameOpponent);

        // Store original stat values for comparison if editing
        $originalStat = clone $stat;

        if ($this->request->is(['patch', 'post', 'put'])) {
            $stat = $this->StatBasketGameOpponent->patchEntity($stat, $this->request->getData());
            assert($stat instanceof \App\Model\Entity\StatBasketGameOpponent);
            if ($this->StatBasketGameOpponent->save($stat)) {
                // Handle add-to-totals if checkbox was selected
                $addToTotals = $this->request->getData('add_to_totals');
                if ($addToTotals && $stat->period === 'Z') {
                    $this->updateSeasonTotals($originalStat, $stat, $stat->game_id);
                }

                $this->Flash->success(__('The opponent player stat has been saved.'));

                return $this->redirect(['action' => 'view', $stat->game_id]);
            }
            $this->Flash->error(__('The opponent player stat could not be saved. Please, try again.'));
        }

        $game = $this->fetchTable('Games')->get($stat->game_id, contain: ['TeamSeason', 'Opponents']);
        assert($game instanceof \App\Model\Entity\Game);

        $this->set(compact('stat', 'game'));
    }

    /**
     * Delete method - remove opponent player stat entry
     *
     * @param int $id Stat ID
     * @return \Cake\Http\Response|null Redirects to view
     */
    public function delete(int $id): ?Response
    {
        $this->request->allowMethod(['post', 'delete']);
        $stat = $this->StatBasketGameOpponent->get($id);
        assert($stat instanceof \App\Model\Entity\StatBasketGameOpponent);
        $gameId = $stat->game_id;

        if ($this->StatBasketGameOpponent->delete($stat)) {
            $this->Flash->success(__('The opponent player stat has been deleted.'));
        } else {
            $this->Flash->error(__('The opponent player stat could not be deleted. Please, try again.'));
        }

        return $this->redirect(['action' => 'view', $gameId]);
    }

    /**
     * Add game stats to opponent's season totals
     *
     * @param \App\Model\Entity\StatBasketGameOpponent $gameStat Game stat to add
     * @param int $gameId Game ID to get team_season_id
     * @return void
     */
    protected function addToSeasonTotals(\App\Model\Entity\StatBasketGameOpponent $gameStat, int $gameId): void
    {
        $seasonTable = $this->fetchTable('StatBasketSeasonOpponent');
        $game = $this->fetchTable('Games')->get($gameId);
        /** @var \App\Model\Entity\Game $game */
        assert($game instanceof \App\Model\Entity\Game);

        // Find or create season totals record
        /** @var \App\Model\Entity\StatBasketSeasonOpponent|null $seasonStat */
        $seasonStat = $seasonTable
            ->find()
            ->where(['team_season_id' => $game->team_season_id])
            ->first();

        if (!$seasonStat) {
            $seasonStat = $seasonTable->newEmptyEntity();
            /** @var \App\Model\Entity\StatBasketSeasonOpponent $seasonStat */
            $seasonStat->team_season_id = $game->team_season_id;
        }
        assert($seasonStat instanceof \App\Model\Entity\StatBasketSeasonOpponent);

        // Add game stats to season totals
        $this->addStatValues($seasonStat, $gameStat);

        $seasonTable->save($seasonStat);
    }

    /**
     * Update season totals when editing a game stat
     *
     * @param \App\Model\Entity\StatBasketGameOpponent $originalStat Original stat values
     * @param \App\Model\Entity\StatBasketGameOpponent $newStat New stat values
     * @param int $gameId Game ID to get team_season_id
     * @return void
     */
    protected function updateSeasonTotals(
        \App\Model\Entity\StatBasketGameOpponent $originalStat,
        \App\Model\Entity\StatBasketGameOpponent $newStat,
        int $gameId,
    ): void {
        $seasonTable = $this->fetchTable('StatBasketSeasonOpponent');
        $game = $this->fetchTable('Games')->get($gameId);
        assert($game instanceof \App\Model\Entity\Game);

        // Find season totals record
        /** @var \App\Model\Entity\StatBasketSeasonOpponent|null $seasonStat */
        $seasonStat = $seasonTable
            ->find()
            ->where(['team_season_id' => $game->team_season_id])
            ->first();

        if (!$seasonStat) {
            // If no season stat exists, just add the new values
            $this->addToSeasonTotals($newStat, $gameId);

            return;
        }
        assert($seasonStat instanceof \App\Model\Entity\StatBasketSeasonOpponent);

        // Subtract original values and add new values
        $this->subtractStatValues($seasonStat, $originalStat);
        $this->addStatValues($seasonStat, $newStat);

        $seasonTable->save($seasonStat);
    }

    /**
     * Add stat values from game stat to season stat
     *
     * @param \App\Model\Entity\StatBasketSeasonOpponent $seasonStat Season stat to update
     * @param \App\Model\Entity\StatBasketGameOpponent $gameStat Game stat to add from
     * @return void
     */
    protected function addStatValues(
        \App\Model\Entity\StatBasketSeasonOpponent $seasonStat,
        \App\Model\Entity\StatBasketGameOpponent $gameStat,
    ): void {
        $fields = ['GP', 'MIN', 'FGM', 'FGA', 'TPM', 'TPA', 'FTM', 'FTA',
            'ORB', 'DRB', 'RB', 'AST', 'STL', 'BS', 'TRN', 'PF', 'TF'];

        foreach ($fields as $field) {
            $current = (int)($seasonStat->$field ?? 0);
            $add = (int)($gameStat->$field ?? 0);
            $seasonStat->$field = (string)($current + $add);
        }

        // PTS is stored as string in season stats
        $currentPts = (int)($seasonStat->PTS ?? 0);
        $addPts = (int)($gameStat->PTS ?? 0);
        $seasonStat->PTS = (string)($currentPts + $addPts);
    }

    /**
     * Subtract stat values from season stat
     *
     * @param \App\Model\Entity\StatBasketSeasonOpponent $seasonStat Season stat to update
     * @param \App\Model\Entity\StatBasketGameOpponent $gameStat Game stat to subtract
     * @return void
     */
    protected function subtractStatValues(
        \App\Model\Entity\StatBasketSeasonOpponent $seasonStat,
        \App\Model\Entity\StatBasketGameOpponent $gameStat,
    ): void {
        $fields = ['GP', 'MIN', 'FGM', 'FGA', 'TPM', 'TPA', 'FTM', 'FTA',
            'ORB', 'DRB', 'RB', 'AST', 'STL', 'BS', 'TRN', 'PF', 'TF'];

        foreach ($fields as $field) {
            $current = (int)($seasonStat->$field ?? 0);
            $subtract = (int)($gameStat->$field ?? 0);
            $seasonStat->$field = (string)max(0, $current - $subtract);
        }

        // PTS is stored as string in season stats
        $currentPts = (int)($seasonStat->PTS ?? 0);
        $subtractPts = (int)($gameStat->PTS ?? 0);
        $seasonStat->PTS = (string)max(0, $currentPts - $subtractPts);
    }
}
