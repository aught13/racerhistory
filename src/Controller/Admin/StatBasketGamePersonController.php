<?php
declare(strict_types=1);

namespace App\Controller\Admin;

use App\Service\BasketballStatsService;
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
    private BasketballStatsService $basketballStatsService;

    /**
     * Initialization hook method.
     *
     * @return void
     */
    public function initialize(): void
    {
        parent::initialize();

        $this->basketballStatsService = new BasketballStatsService();
    }

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
                /** @var \App\Model\Entity\StatBasketGamePerson $stat */
                // Handle add-to-totals if checkbox was selected
                $addToTotals = $this->request->getData('add_to_totals');
                if ($addToTotals && $stat->team_season_roster_id && $stat->period === 'Z') {
                    $this->basketballStatsService->addGamePersonStatToSeasonTotals($stat);
                }

                $this->Flash->success(__('The player stat has been saved.'));

                return $this->redirect(['action' => 'view', $gameId]);
            }
            // Display specific validation errors
            $errors = $stat->getErrors();
            if (!empty($errors)) {
                foreach ($errors as $field => $fieldErrors) {
                    foreach ($fieldErrors as $error) {
                        $this->Flash->error(__('Validation error in {0}: {1}', $field, $error));
                    }
                }
            } else {
                $this->Flash->error(__('The player stat could not be saved. Please, try again.'));
            }
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
                    $this->basketballStatsService->updateGamePersonStatSeasonTotals($originalStat, $stat);
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
}
