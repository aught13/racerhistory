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

        $originalTeamStats = null;
        if (!$teamStats) {
            $teamStats = $this->StatBasketGameTeam->newEmptyEntity();
            assert($teamStats instanceof \App\Model\Entity\StatBasketGameTeam);
            $teamStats->game_id = $gameId;
            $teamStats->opp = 0;
        } else {
            $originalTeamStats = clone $teamStats;
        }

        // Get or create opponent stats
        $opponentStats = $this->StatBasketGameTeam
            ->find()
            ->where([
                'StatBasketGameTeam.game_id' => $gameId,
                'StatBasketGameTeam.opp' => 1,
            ])
            ->first();

        $originalOpponentStats = null;
        if (!$opponentStats) {
            $opponentStats = $this->StatBasketGameTeam->newEmptyEntity();
            assert($opponentStats instanceof \App\Model\Entity\StatBasketGameTeam);
            $opponentStats->game_id = $gameId;
            $opponentStats->opp = 1;
        } else {
            $originalOpponentStats = clone $opponentStats;
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
                // Handle add-to-totals for team if checkbox was selected
                $addTeamToTotals = $this->request->getData('add_team_to_totals');
                if ($addTeamToTotals) {
                    if ($originalTeamStats) {
                        $this->updateTeamSeasonTotals($originalTeamStats, $teamStats, $gameId);
                    } else {
                        $this->addTeamToSeasonTotals($teamStats, $gameId);
                    }
                }

                // Handle add-to-totals for opponent if checkbox was selected
                $addOpponentToTotals = $this->request->getData('add_opponent_to_totals');
                if ($addOpponentToTotals) {
                    if ($originalOpponentStats) {
                        $this->updateOpponentSeasonTotals($originalOpponentStats, $opponentStats, $gameId);
                    } else {
                        $this->addOpponentToSeasonTotals($opponentStats, $gameId);
                    }
                }

                $this->Flash->success(__('The team stats have been saved.'));

                return $this->redirect(['action' => 'view', $gameId]);
            }
        }

        $game = $this->fetchTable('Games')->get($gameId, contain: ['TeamSeason', 'Opponents']);

        $this->set(compact('teamStats', 'opponentStats', 'game'));
    }

    /**
     * Add game stats to team's season totals
     *
     * @param \App\Model\Entity\StatBasketGameTeam $gameStat Game stat to add
     * @param int $gameId Game ID to get team_season_id
     * @return void
     */
    protected function addTeamToSeasonTotals(\App\Model\Entity\StatBasketGameTeam $gameStat, int $gameId): void
    {
        $seasonTable = $this->fetchTable('StatBasketSeasonTeam');
        $game = $this->fetchTable('Games')->get($gameId);

        // Find or create season totals record
        $seasonStat = $seasonTable
            ->find()
            ->where(['team_season_id' => $game->team_season_id])
            ->first();

        if (!$seasonStat) {
            $seasonStat = $seasonTable->newEmptyEntity();
            $seasonStat->team_season_id = $game->team_season_id;
        }

        // Add game stats to season totals
        $this->addTeamStatValues($seasonStat, $gameStat);

        $seasonTable->save($seasonStat);
    }

    /**
     * Update team season totals when editing a game stat
     *
     * @param \App\Model\Entity\StatBasketGameTeam $originalStat Original stat values
     * @param \App\Model\Entity\StatBasketGameTeam $newStat New stat values
     * @param int $gameId Game ID to get team_season_id
     * @return void
     */
    protected function updateTeamSeasonTotals(
        \App\Model\Entity\StatBasketGameTeam $originalStat,
        \App\Model\Entity\StatBasketGameTeam $newStat,
        int $gameId,
    ): void {
        $seasonTable = $this->fetchTable('StatBasketSeasonTeam');
        $game = $this->fetchTable('Games')->get($gameId);

        // Find season totals record
        $seasonStat = $seasonTable
            ->find()
            ->where(['team_season_id' => $game->team_season_id])
            ->first();

        if (!$seasonStat) {
            // If no season stat exists, just add the new values
            $this->addTeamToSeasonTotals($newStat, $gameId);

            return;
        }

        // Subtract original values and add new values
        $this->subtractTeamStatValues($seasonStat, $originalStat);
        $this->addTeamStatValues($seasonStat, $newStat);

        $seasonTable->save($seasonStat);
    }

    /**
     * Add game stats to opponent season totals
     *
     * @param \App\Model\Entity\StatBasketGameTeam $gameStat Game stat to add
     * @param int $gameId Game ID to get team_season_id
     * @return void
     */
    protected function addOpponentToSeasonTotals(\App\Model\Entity\StatBasketGameTeam $gameStat, int $gameId): void
    {
        $seasonTable = $this->fetchTable('StatBasketSeasonOpponent');
        $game = $this->fetchTable('Games')->get($gameId);

        // Find or create season totals record
        $seasonStat = $seasonTable
            ->find()
            ->where(['team_season_id' => $game->team_season_id])
            ->first();

        if (!$seasonStat) {
            $seasonStat = $seasonTable->newEmptyEntity();
            $seasonStat->team_season_id = $game->team_season_id;
        }

        // Add game stats to season totals
        $this->addTeamStatValues($seasonStat, $gameStat);

        $seasonTable->save($seasonStat);
    }

    /**
     * Update opponent season totals when editing a game stat
     *
     * @param \App\Model\Entity\StatBasketGameTeam $originalStat Original stat values
     * @param \App\Model\Entity\StatBasketGameTeam $newStat New stat values
     * @param int $gameId Game ID to get team_season_id
     * @return void
     */
    protected function updateOpponentSeasonTotals(
        \App\Model\Entity\StatBasketGameTeam $originalStat,
        \App\Model\Entity\StatBasketGameTeam $newStat,
        int $gameId,
    ): void {
        $seasonTable = $this->fetchTable('StatBasketSeasonOpponent');
        $game = $this->fetchTable('Games')->get($gameId);

        // Find season totals record
        $seasonStat = $seasonTable
            ->find()
            ->where(['team_season_id' => $game->team_season_id])
            ->first();

        if (!$seasonStat) {
            // If no season stat exists, just add the new values
            $this->addOpponentToSeasonTotals($newStat, $gameId);

            return;
        }

        // Subtract original values and add new values
        $this->subtractTeamStatValues($seasonStat, $originalStat);
        $this->addTeamStatValues($seasonStat, $newStat);

        $seasonTable->save($seasonStat);
    }

    /**
     * Add stat values from game stat to season stat
     *
     * @param \App\Model\Entity\StatBasketSeasonTeam|\App\Model\Entity\StatBasketSeasonOpponent $seasonStat Season stat
     * @param \App\Model\Entity\StatBasketGameTeam $gameStat Game stat to add from
     * @return void
     */
    protected function addTeamStatValues(\App\Model\Entity\StatBasketSeasonTeam|\App\Model\Entity\StatBasketSeasonOpponent $seasonStat, \App\Model\Entity\StatBasketGameTeam $gameStat): void
    {
        $fields = ['ORB', 'DRB', 'RB', 'TRN', 'TF', 'PTS'];

        foreach ($fields as $field) {
            $current = (int)($seasonStat->$field ?? 0);
            $add = (int)($gameStat->$field ?? 0);
            $seasonStat->$field = (string)($current + $add);
        }
    }

    /**
     * Subtract stat values from season stat
     *
     * @param \App\Model\Entity\StatBasketSeasonTeam|\App\Model\Entity\StatBasketSeasonOpponent $seasonStat Season stat
     * @param \App\Model\Entity\StatBasketGameTeam $gameStat Game stat to subtract
     * @return void
     */
    protected function subtractTeamStatValues(\App\Model\Entity\StatBasketSeasonTeam|\App\Model\Entity\StatBasketSeasonOpponent $seasonStat, \App\Model\Entity\StatBasketGameTeam $gameStat): void
    {
        $fields = ['ORB', 'DRB', 'RB', 'TRN', 'TF', 'PTS'];

        foreach ($fields as $field) {
            $current = (int)($seasonStat->$field ?? 0);
            $subtract = (int)($gameStat->$field ?? 0);
            $seasonStat->$field = (string)max(0, $current - $subtract);
        }
    }
}
