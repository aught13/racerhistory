<?php
declare(strict_types=1);

namespace App\Controller\Admin;

use Cake\Datasource\Exception\RecordNotFoundException;
use Cake\Http\Response;

/**
 * Admin TeamSeasons Controller
 *
 * Handles administrative team seasons management operations.
 * Provides functionality for team seasons administration and CRUD operations.
 *
 * TeamSeasons represent the relationship between teams and seasons, capturing
 * detailed information about a team's participation in a specific season including:
 * - Team and season associations
 * - League and tournament information
 * - Season-specific notes and media
 * - Competition results and finishing positions
 *
 * @property \App\Model\Table\TeamSeasonsTable $TeamSeasons
 * @property \App\Model\Entity\Image|null $team_season_image_entity
 */
class TeamSeasonsController extends AppController
{
    /**
     * @property \App\Model\Entity\Image|null $team_season_image_entity (runtime-assigned in edit())
     */

    /**
     * List all team seasons for administration.
     *
     * @return void
     */
    public function index(): void
    {
        $teamSeasons = $this->TeamSeasons->find()
            ->contain(['Teams', 'Seasons'])
            ->all();
        $this->set(compact('teamSeasons'));
    }

    /**
     * View a single team season.
     *
     * @param string $id TeamSeason ID
     * @return void
     */
    public function view(string $id): void
    {
        /** @var \App\Model\Entity\TeamSeason $teamSeason */
        $teamSeason = $this->TeamSeasons->get($id, contain: ['Teams', 'Seasons']);
        $teamSeasonRosters = $this->fetchTable('TeamSeasonRosters')->find()
            ->where(['team_season_id' => $id])
            ->contain(['Persons'])
            ->all();

        // Get games for this team season
        $teamSeasonGames = $this->fetchTable('Games')->find()
            ->where(['team_season_id' => $id])
            ->contain(['GameTypes', 'Opponents', 'Sites' => ['Places'], 'Places'])
            ->orderByDesc('game_date')
            ->all();

        // Find previous and next team seasons of the same sport, ordered by season end year
        $currentSportId = $teamSeason->team->sport_id;
        $currentSeasonEnd = $teamSeason->season->end;

        /** @var \App\Model\Entity\TeamSeason|null $previousTeamSeason */
        $previousTeamSeason = $this->TeamSeasons->find()
            ->contain(['Teams', 'Seasons'])
            ->matching('Teams', function ($q) use ($currentSportId) {
                return $q->where(['Teams.sport_id' => $currentSportId]);
            })
            ->matching('Seasons', function ($q) use ($currentSeasonEnd) {
                return $q->where(['Seasons.end <' => $currentSeasonEnd]);
            })
            ->orderByDesc('Seasons.end')
            ->first();

        /** @var \App\Model\Entity\TeamSeason|null $nextTeamSeason */
        $nextTeamSeason = $this->TeamSeasons->find()
            ->contain(['Teams', 'Seasons'])
            ->matching('Teams', function ($q) use ($currentSportId) {
                return $q->where(['Teams.sport_id' => $currentSportId]);
            })
            ->matching('Seasons', function ($q) use ($currentSeasonEnd) {
                return $q->where(['Seasons.end >' => $currentSeasonEnd]);
            })
            ->orderByAsc('Seasons.end')
            ->first();

        $this->set(compact(
            'teamSeason',
            'teamSeasonRosters',
            'teamSeasonGames',
            'previousTeamSeason',
            'nextTeamSeason'
        ));
    }

    /**
     * Add new team season form and processing.
     *
     * @return \Cake\Http\Response|null
     */
    public function add(): ?Response
    {
        $teamSeason = $this->TeamSeasons->newEmptyEntity();

        // Pre-populate team_id if provided in query string
        if ($this->request->getQuery('team_id')) {
            $teamSeason->team_id = (int)$this->request->getQuery('team_id');
        }

        // Pre-populate season_id if provided in query string
        if ($this->request->getQuery('season_id')) {
            $teamSeason->season_id = (int)$this->request->getQuery('season_id');
        }

        if ($this->request->is('post')) {
            $data = $this->request->getData();
            // Mirror PersonsController pattern: allow numeric direct image ID storage
            if (isset($data['team_season_image']) && is_numeric($data['team_season_image'])) {
                $data['team_season_image'] = (int)$data['team_season_image'];
            }
            $teamSeason = $this->TeamSeasons->patchEntity($teamSeason, $data);

            if ($this->TeamSeasons->save($teamSeason)) {
                $this->Flash->success(__('The team season has been saved.'));

                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error(__('The team season could not be saved. Please, try again.'));
        }

        $teams = $this->fetchTable('Teams')->find('list', limit: 200)->all();
        $seasons = $this->fetchTable('Seasons')->find()
            ->select(['id', 'start', 'end'])
            ->orderByDesc('start')
            ->toArray();

        // Convert seasons to list format
        $seasonsList = [];
        foreach ($seasons as $season) {
            $seasonsList[$season->id] = $season->start . '-' . $season->end;
        }

        // Get sports for the popup modal
        $sports = $this->fetchTable('Sports')->find('list', limit: 200)->all();

        $this->set(compact('teamSeason', 'teams', 'seasonsList', 'sports'));

        return null;
    }

    /**
     * Edit team season form and processing.
     *
     * @param string $id TeamSeason ID
     * @return \Cake\Http\Response|null
     */
    public function edit(string $id): ?Response
    {
        $teamSeason = $this->TeamSeasons->get($id, contain: ['Teams', 'Seasons']);
        $teamSeason = $teamSeason->set('team_season_image_entity', null);
        if ($teamSeason->team_season_image) {
            try {
                $imageEntity = $this->fetchTable('Images')->get($teamSeason->team_season_image);
                $teamSeason = $teamSeason->set('team_season_image_entity', $imageEntity);
            } catch (RecordNotFoundException $e) {
                // Image ID exists but record is missing. Silently ignore.
            }
        }

        if ($this->request->is(['patch', 'post', 'put'])) {
            $data = $this->request->getData();
            if (isset($data['team_season_image']) && is_numeric($data['team_season_image'])) {
                $data['team_season_image'] = (int)$data['team_season_image'];
            }
            $teamSeason = $this->TeamSeasons->patchEntity($teamSeason, $data);

            if ($this->TeamSeasons->save($teamSeason)) {
                $this->Flash->success(__('The team season has been saved.'));

                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error(__('The team season could not be saved. Please, try again.'));
        }

        $teams = $this->fetchTable('Teams')->find('list', limit: 200)->all();
        $seasons = $this->fetchTable('Seasons')->find()
            ->select(['id', 'start', 'end'])
            ->orderByDesc('start')
            ->toArray();

        // Convert seasons to list format
        $seasonsList = [];
        foreach ($seasons as $season) {
            $seasonsList[$season->id] = $season->start . '-' . $season->end;
        }

        // Get sports list for the team popup
        $sports = $this->fetchTable('Sports')->find('list', limit: 200)->all();

        $this->set(compact('teamSeason', 'teams', 'seasonsList', 'sports'));

        return null;
    }

    /**
     * Delete a team season.
     *
     * @param string $id TeamSeason ID
     * @return \Cake\Http\Response
     */
    public function delete(string $id): Response
    {
        $this->request->allowMethod(['post', 'delete']);
        $teamSeason = $this->TeamSeasons->get($id);

        if ($this->TeamSeasons->delete($teamSeason)) {
            $this->Flash->success(__('The team season has been deleted.'));
        } else {
            $this->Flash->error(__('The team season could not be deleted. Please, try again.'));
        }

        return $this->redirect(['action' => 'index']);
    }

    /**
     * Bulk delete multiple team seasons.
     *
     * @return \Cake\Http\Response
     */
    public function bulkDelete(): Response
    {
        $this->request->allowMethod(['post']);
        $teamSeasonIds = (array)$this->request->getData('team_season_ids');
        // Remove empty/null/invalid values that can be introduced by placeholder hidden inputs
        $teamSeasonIds = array_values(array_filter($teamSeasonIds, function ($v) {
            return $v !== '' && $v !== null && ctype_digit((string)$v);
        }));

        if (empty($teamSeasonIds)) {
            $this->Flash->error('No team seasons selected for deletion.');

            return $this->redirect(['action' => 'index']);
        }

        $deletedCount = 0;
        foreach ($teamSeasonIds as $id) {
            try {
                $teamSeason = $this->TeamSeasons->get($id);

                if ($this->TeamSeasons->delete($teamSeason)) {
                    $deletedCount++;
                }
            } catch (RecordNotFoundException $e) {
                // Skip invalid id silently; could log if needed
                continue;
            }
        }

        if ($deletedCount > 0) {
            $this->Flash->success(__('Deleted {0} team season(s).', $deletedCount));
        } else {
            $this->Flash->error('No team seasons could be deleted.');
        }

        return $this->redirect(['action' => 'index']);
    }

    /**
     * Bulk action dispatcher for team seasons.
     *
     * @return \Cake\Http\Response
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
}
