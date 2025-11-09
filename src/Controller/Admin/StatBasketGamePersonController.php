<?php
declare(strict_types=1);

namespace App\Controller\Admin;

use Cake\Http\Response;

/**
 * StatBasketGamePerson Controller (Admin)
 *
 * Manages basketball player game statistics.
 *
 * @property \App\Model\Table\StatBasketGamePersonTable $StatBasketGamePerson
 */
class StatBasketGamePersonController extends AppController
{
    /**
     * View method - display stats for a specific game
     *
     * @param int $gameId Game ID
     * @return \Cake\Http\Response|null|void Renders view
     */
    public function view(int $gameId)
    {
        $stats = $this->StatBasketGamePerson
            ->find()
            ->contain(['TeamSeasonRosters' => ['Persons', 'TeamSeasons']])
            ->where(['StatBasketGamePerson.game_id' => $gameId])
            ->orderBy(function ($exp, $query) {
                return [
                    $query->newExpr('COALESCE(StatBasketGamePerson.GS, 0) DESC'),
                    $query->newExpr('COALESCE(StatBasketGamePerson.MIN, 0) DESC'),
                    'StatBasketGamePerson.PTS' => 'DESC',
                ];
            })
            ->all();

        $game = $this->fetchTable('Games')->get($gameId, contain: ['TeamSeason', 'Opponents']);

        $this->set(compact('stats', 'game'));
    }

    /**
     * Add method - create new player stat entry
     *
     * @param int $gameId Game ID
     * @return \Cake\Http\Response|null|void Redirects on successful add, renders view otherwise.
     */
    public function add(int $gameId)
    {
        $stat = $this->StatBasketGamePerson->newEmptyEntity();
        assert($stat instanceof \App\Model\Entity\StatBasketGamePerson);
        $stat->game_id = $gameId;
        $stat->period = 'Z'; // Default to final stats
        $stat->GP = '1'; // Default games played to 1

        if ($this->request->is('post')) {
            $stat = $this->StatBasketGamePerson->patchEntity($stat, $this->request->getData());
            if ($this->StatBasketGamePerson->save($stat)) {
                // Handle add-to-totals if checkbox was selected
                $addToTotals = $this->request->getData('add_to_totals');
                if ($addToTotals && $stat->team_season_roster_id && $stat->period === 'Z') {
                    $this->addToSeasonTotals($stat);
                }

                $this->Flash->success(__('The player stat has been saved.'));

                return $this->redirect(['action' => 'view', $gameId]);
            }
            $this->Flash->error(__('The player stat could not be saved. Please, try again.'));
        }

        $game = $this->fetchTable('Games')->get($gameId, contain: ['TeamSeason', 'Opponents']);
        assert($game instanceof \App\Model\Entity\Game);

        // Get roster for this game's team season
        $roster = $this->fetchTable('TeamSeasonRosters')
            ->find()
            ->contain(['Persons'])
            ->where(['team_season_id' => $game->team_season_id])
            ->orderBy(['roster_number' => 'ASC'])
            ->all();

        $teamSeasonRoster = $roster->combine('id', function ($row) {
            $person = $row->person;
            $name = $person->display ?? $person->full ?? '';
            $number = $row->roster_number ?? '';

            return ($number ? "#{$number} " : '') . $name;
        })->toArray();

        $this->set(compact('stat', 'game', 'teamSeasonRoster'));
    }

    /**
     * Edit method - update existing player stat entry
     *
     * @param int $id Stat ID
     * @return \Cake\Http\Response|null|void Redirects on successful edit, renders view otherwise.
     */
    public function edit(int $id)
    {
        $stat = $this->StatBasketGamePerson->get($id, contain: ['TeamSeasonRosters', 'Games']);
        assert($stat instanceof \App\Model\Entity\StatBasketGamePerson);

        // Store original stat values for comparison if editing
        $originalStat = clone $stat;

        if ($this->request->is(['patch', 'post', 'put'])) {
            $stat = $this->StatBasketGamePerson->patchEntity($stat, $this->request->getData());
            assert($stat instanceof \App\Model\Entity\StatBasketGamePerson);
            if ($this->StatBasketGamePerson->save($stat)) {
                // Handle add-to-totals if checkbox was selected
                $addToTotals = $this->request->getData('add_to_totals');
                if ($addToTotals && $stat->team_season_roster_id && $stat->period === 'Z') {
                    $this->updateSeasonTotals($originalStat, $stat);
                }

                $this->Flash->success(__('The player stat has been saved.'));

                return $this->redirect(['action' => 'view', $stat->game_id]);
            }
            $this->Flash->error(__('The player stat could not be saved. Please, try again.'));
        }

        $game = $this->fetchTable('Games')->get($stat->game_id, contain: ['TeamSeason', 'Opponents']);
        assert($game instanceof \App\Model\Entity\Game);

        // Get roster for this game's team season
        $roster = $this->fetchTable('TeamSeasonRosters')
            ->find()
            ->contain(['Persons'])
            ->where(['team_season_id' => $game->team_season_id])
            ->orderBy(['roster_number' => 'ASC'])
            ->all();

        $teamSeasonRoster = $roster->combine('id', function ($row) {
            $person = $row->person;
            $name = $person->display ?? $person->full ?? '';
            $number = $row->roster_number ?? '';

            return ($number ? "#{$number} " : '') . $name;
        })->toArray();

        $this->set(compact('stat', 'game', 'teamSeasonRoster'));
    }

    /**
     * Delete method - remove player stat entry
     *
     * @param int $id Stat ID
     * @return \Cake\Http\Response|null Redirects to view
     */
    public function delete(int $id): ?Response
    {
        $this->request->allowMethod(['post', 'delete']);
        $stat = $this->StatBasketGamePerson->get($id);
        assert($stat instanceof \App\Model\Entity\StatBasketGamePerson);
        $gameId = $stat->game_id;

        if ($this->StatBasketGamePerson->delete($stat)) {
            $this->Flash->success(__('The player stat has been deleted.'));
        } else {
            $this->Flash->error(__('The player stat could not be deleted. Please, try again.'));
        }

        return $this->redirect(['action' => 'view', $gameId]);
    }

    /**
     * Add game stats to player's season totals
     *
     * @param \App\Model\Entity\StatBasketGamePerson $gameStat Game stat to add
     * @return void
     */
    protected function addToSeasonTotals(\App\Model\Entity\StatBasketGamePerson $gameStat): void
    {
        $seasonTable = $this->fetchTable('StatBasketSeasonPerson');

        // Find or create season totals record
        $seasonStat = $seasonTable
            ->find()
            ->where(['team_season_roster_id' => $gameStat->team_season_roster_id])
            ->first();

        if (!$seasonStat) {
            $seasonStat = $seasonTable->newEmptyEntity();
            $seasonStat->team_season_roster_id = $gameStat->team_season_roster_id;
        }

        // Add game stats to season totals
        $this->addStatValues($seasonStat, $gameStat);

        $seasonTable->save($seasonStat);
    }

    /**
     * Update season totals when editing a game stat
     *
     * @param \App\Model\Entity\StatBasketGamePerson $originalStat Original stat values
     * @param \App\Model\Entity\StatBasketGamePerson $newStat New stat values
     * @return void
     */
    protected function updateSeasonTotals(
        \App\Model\Entity\StatBasketGamePerson $originalStat,
        \App\Model\Entity\StatBasketGamePerson $newStat,
    ): void {
        $seasonTable = $this->fetchTable('StatBasketSeasonPerson');

        // Find season totals record
        $seasonStat = $seasonTable
            ->find()
            ->where(['team_season_roster_id' => $newStat->team_season_roster_id])
            ->first();

        if (!$seasonStat) {
            // If no season stat exists, just add the new values
            $this->addToSeasonTotals($newStat);

            return;
        }

        // Subtract original values and add new values
        $this->subtractStatValues($seasonStat, $originalStat);
        $this->addStatValues($seasonStat, $newStat);

        $seasonTable->save($seasonStat);
    }

    /**
     * Add stat values from game stat to season stat
     *
     * @param \App\Model\Entity\StatBasketSeasonPerson $seasonStat Season stat to update
     * @param \App\Model\Entity\StatBasketGamePerson $gameStat Game stat to add from
     * @return void
     */
    protected function addStatValues(
        \App\Model\Entity\StatBasketSeasonPerson $seasonStat,
        \App\Model\Entity\StatBasketGamePerson $gameStat,
    ): void {
        $fields = ['GP', 'GS', 'MIN', 'FGM', 'FGA', 'TPM', 'TPA', 'FTM', 'FTA',
            'ORB', 'DRB', 'RB', 'AST', 'STL', 'BS', 'TRN', 'PF', 'TF'];

        foreach ($fields as $field) {
            $current = (int)($seasonStat->$field ?? 0);
            $add = (int)($gameStat->$field ?? 0);
            $seasonStat->$field = (string)($current + $add);
        }

        // PTS is stored as integer in season stats
        $currentPts = (int)($seasonStat->PTS ?? 0);
        $addPts = (int)($gameStat->PTS ?? 0);
        $seasonStat->PTS = $currentPts + $addPts;
    }

    /**
     * Subtract stat values from season stat
     *
     * @param \App\Model\Entity\StatBasketSeasonPerson $seasonStat Season stat to update
     * @param \App\Model\Entity\StatBasketGamePerson $gameStat Game stat to subtract
     * @return void
     */
    protected function subtractStatValues(
        \App\Model\Entity\StatBasketSeasonPerson $seasonStat,
        \App\Model\Entity\StatBasketGamePerson $gameStat,
    ): void {
        $fields = ['GP', 'GS', 'MIN', 'FGM', 'FGA', 'TPM', 'TPA', 'FTM', 'FTA',
            'ORB', 'DRB', 'RB', 'AST', 'STL', 'BS', 'TRN', 'PF', 'TF'];

        foreach ($fields as $field) {
            $current = (int)($seasonStat->$field ?? 0);
            $subtract = (int)($gameStat->$field ?? 0);
            $seasonStat->$field = (string)max(0, $current - $subtract);
        }

        // PTS is stored as integer in season stats
        $currentPts = (int)($seasonStat->PTS ?? 0);
        $subtractPts = (int)($gameStat->PTS ?? 0);
        $seasonStat->PTS = max(0, $currentPts - $subtractPts);
    }
}
