<?php
declare(strict_types=1);

namespace App\Controller;

use Cake\Event\EventInterface;

/**
 * Public Stats Controller
 *
 * Displays basketball statistics and leaderboards.
 *
 * @property \Authorization\Controller\Component\AuthorizationComponent $Authorization
 */
class StatsController extends AppController
{
    /**
     * Initialize controller.
     */
    public function initialize(): void
    {
        parent::initialize();
        $this->loadComponent('Authorization.Authorization');
    }

    /**
     * Skip authorization for public actions.
     */
    public function beforeFilter(EventInterface $event): void
    {
        parent::beforeFilter($event);
        $this->Authorization->skipAuthorization();
    }

    /**
     * Stats index/search page.
     */
    public function index(): void
    {
        // Get available seasons for filtering
        $table = $this->fetchTable('TeamSeasons');
        $teamSeasons = $table->find()
            ->contain(['Teams' => ['Sports'], 'Seasons'])
            ->matching('Teams.Sports', function ($q) {
                return $q->where(['Sports.sport_name' => 'Basketball']);
            })
            ->matching('Teams', function ($q) {
                return $q->where(['Teams.gender' => 'M']);
            })
            ->orderByDesc('Seasons.start')
            ->all()
            ->toArray();

        $this->set(compact('teamSeasons'));
    }

    /**
     * Season stats view.
     *
     * @param int $teamSeasonId Team season ID
     * @return \Cake\Http\Response|null
     */
    public function season(int $teamSeasonId)
    {
        // Get team season
        $teamSeasonsTable = $this->fetchTable('TeamSeasons');
        $teamSeason = $teamSeasonsTable->find()
            ->contain(['Teams', 'Seasons'])
            ->where(['TeamSeasons.id' => $teamSeasonId])
            ->first();

        if (!$teamSeason) {
            $this->Flash->error('Season not found');
            return $this->redirect(['action' => 'index']);
        }

        // Get player season stats
        try {
            $statsTable = $this->fetchTable('StatBasketSeasonPersons');
            $playerStats = $statsTable->find()
                ->contain(['Persons'])
                ->where(['StatBasketSeasonPersons.team_season_id' => $teamSeasonId])
                ->orderByDesc('StatBasketSeasonPersons.pts')
                ->all()
                ->toArray();
        } catch (\Exception $e) {
            $playerStats = [];
        }

        $this->set(compact('teamSeason', 'playerStats'));

        return null;
    }
}
