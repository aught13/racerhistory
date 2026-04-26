<?php
declare(strict_types=1);

namespace App\Controller\Admin;

use Cake\Http\Response;

/**
 * StatBasketSeasonPerson Controller (Admin)
 *
 * Manages basketball player season statistics. Provides actions to add, edit, and delete player season stats for a given team season. The add and edit actions allow for creating or updating player season stats, while the delete action removes the player season stats for a specific team season. All actions include appropriate success and error flash messages to inform the user of the outcome of their actions. The controller relies on the StatBasketSeasonPerson model for data management and assumes that proper authentication and authorization checks are handled by middleware or components not shown in this code snippet. The add action creates a new player season stat entry, while the edit action retrieves the existing stats for the given stat ID and processes form submissions to save the updated stats. The delete action ensures that the request method is POST or DELETE to prevent accidental deletions via GET requests, and it attempts to find and delete the relevant stats, providing feedback to the user based on the success of the operation. After successful edits or deletions, the user is redirected back to the team season view page for continuity in their workflow.
 * The add and edit actions also retrieve the roster for the relevant team season to provide a dropdown selection of players when creating or updating player season stats. The roster entries are organized by sport and include the player's name and number for easy identification. Proper validation, error handling, and feedback mechanisms are implemented in all actions to ensure data integrity and a good user experience when managing player season stats in the admin interface.
 *
 * Actions:
 * - add: Handles the creation of a new player season stat entry for a given team season. It processes form submissions, validates the input data, and provides feedback through flash messages. After a successful save, it redirects the user back to the team season view page.
 * - edit: Handles the editing of an existing player season stat entry for a given stat ID. It retrieves the existing stats, processes form submissions to update the stats, validates the input data, and provides feedback through flash messages. After a successful save, it redirects the user back to the team season view page.
 * - delete: Handles the deletion of a player season stat entry for a given stat ID. It ensures that the request method is POST or DELETE to prevent accidental deletions via GET requests, attempts to find and delete the relevant stats, and provides feedback through flash messages. After a successful deletion, it redirects the user back to the team season view page.
 *
 * Security:
 * - All actions should be protected by authentication and authorization checks to ensure that only authorized users can manage player season stats. This is typically handled by middleware or components that are not shown in this code snippet.
 * - The add and edit actions should validate the input data to prevent invalid or malicious data from being saved to the database. Proper error handling and feedback mechanisms should be implemented to inform the user of any issues during the save process.
 * - The delete action should ensure that the request method is POST or DELETE to prevent accidental deletions via GET requests. Proper error handling and feedback mechanisms should be implemented to inform the user of any issues during the deletion process.
 *
 * Dependencies:
 * - StatBasketSeasonPerson: The model used for managing player season stats data. The controller interacts with this model to create, retrieve, update, and delete player season stats based on the team season ID and stat ID. The model should include appropriate validation rules to ensure data integrity when saving stats.
 *
 * Components:
 * - FlashComponent: Used to set success and error messages after attempting to save or delete stats, providing feedback to the user about the outcome of their actions.
 * - AuthorizationComponent: Used to manage access control, ensuring that only authorized users can perform add, edit, and delete actions on player season stats. This component should be configured to check the user's permissions for these specific actions, providing an additional layer of security to protect sensitive data.
 *
 * Note: The add and edit actions rely on the team season ID and stat ID to identify the relevant player season stats. They also retrieve the roster for the relevant team season to provide a dropdown selection of players when creating or updating player season stats. Proper validation, error handling, and feedback mechanisms are crucial for maintaining data integrity and providing a good user experience when managing player season stats in the admin interface. The delete action ensures that only the relevant stats for the specified stat ID are removed, and it provides appropriate feedback to the user based on the success of the deletion operation. After successful edits or deletions, redirecting back to the team season view page helps maintain continuity in the user's workflow when managing player season stats.
 *
 * @property \App\Model\Table\StatBasketSeasonPersonTable $StatBasketSeasonPerson
 * @property \Authorization\Controller\Component\AuthorizationComponent $Authorization
 * @property \Cake\Controller\Component\FlashComponent $Flash
 */
class StatBasketSeasonPersonController extends AppController
{
    /**
     * Add method - create new player season stat entry
     *
     * @param int $teamSeasonId Team Season ID
     * @return \Cake\Http\Response|null|void Redirects on successful add, renders view otherwise.
     */
    public function add(int $teamSeasonId)
    {
        $stat = $this->StatBasketSeasonPerson->newEmptyEntity();
        assert($stat instanceof \App\Model\Entity\StatBasketSeasonPerson);

        if ($this->request->is('post')) {
            $stat = $this->StatBasketSeasonPerson->patchEntity($stat, $this->request->getData());
            if ($this->StatBasketSeasonPerson->save($stat)) {
                $this->Flash->success(__('The player season stats have been saved.'));

                return $this->redirect(['controller' => 'TeamSeasons', 'action' => 'view', $teamSeasonId]);
            }
            $this->Flash->error(__('The player season stats could not be saved. Please, try again.'));
        }

        $teamSeason = $this->fetchTable('TeamSeasons')->get($teamSeasonId, contain: ['Teams', 'Seasons']);
        assert($teamSeason instanceof \App\Model\Entity\TeamSeason);

        // Get roster for this team season
        $roster = $this->fetchTable('TeamSeasonRosters')
            ->find()
            ->contain(['Persons'])
            ->where(['team_season_id' => $teamSeasonId])
            ->orderBy(['roster_number' => 'ASC'])
            ->all();

        $teamSeasonRosters = $roster->combine('id', function ($row) {
            $person = $row->person;
            $name = $person->display ?? $person->full ?? '';
            $number = $row->roster_number ?? '';

            return ($number ? "#{$number} " : '') . $name;
        })->toArray();

        $this->set(compact('stat', 'teamSeason', 'teamSeasonRosters'));
    }

    /**
     * Edit method - update existing player season stat entry
     *
     * @param int $id Stat ID
     * @return \Cake\Http\Response|null|void Redirects on successful edit, renders view otherwise.
     */
    public function edit(int $id)
    {
        $stat = $this->StatBasketSeasonPerson->get($id, contain: ['TeamSeasonRosters' => ['Persons']]);
        assert($stat instanceof \App\Model\Entity\StatBasketSeasonPerson);

        // Get team_season_id from roster
        $roster = $stat->team_season_roster ?? null;
        if (!$roster) {
            $this->Flash->error(__('Unable to find team season roster for this stat.'));

            return $this->redirect(['controller' => 'TeamSeasons', 'action' => 'index']);
        }
        assert($roster instanceof \App\Model\Entity\TeamSeasonRosters);
        $teamSeasonId = $roster->team_season_id;

        if ($this->request->is(['patch', 'post', 'put'])) {
            $stat = $this->StatBasketSeasonPerson->patchEntity($stat, $this->request->getData());
            assert($stat instanceof \App\Model\Entity\StatBasketSeasonPerson);
            if ($this->StatBasketSeasonPerson->save($stat)) {
                $this->Flash->success(__('The player season stats have been saved.'));

                return $this->redirect(['controller' => 'TeamSeasons', 'action' => 'view', $teamSeasonId]);
            }
            $this->Flash->error(__('The player season stats could not be saved. Please, try again.'));
        }

        $teamSeason = $this->fetchTable('TeamSeasons')->get($teamSeasonId, contain: ['Teams', 'Seasons']);
        assert($teamSeason instanceof \App\Model\Entity\TeamSeason);

        // Get roster for this team season
        $roster = $this->fetchTable('TeamSeasonRosters')
            ->find()
            ->contain(['Persons'])
            ->where(['team_season_id' => $teamSeasonId])
            ->orderBy(['roster_number' => 'ASC'])
            ->all();

        $teamSeasonRosters = $roster->combine('id', function ($row) {
            $person = $row->person;
            $name = $person->display ?? $person->full ?? '';
            $number = $row->roster_number ?? '';

            return ($number ? "#{$number} " : '') . $name;
        })->toArray();

        $this->set(compact('stat', 'teamSeason', 'teamSeasonRosters'));
    }

    /**
     * Delete method - remove player season stat entry
     *
     * @param int $id Stat ID
     * @return \Cake\Http\Response|null Redirects to team season view
     */
    public function delete(int $id): ?Response
    {
        $this->request->allowMethod(['post', 'delete']);
        $stat = $this->StatBasketSeasonPerson->get($id, contain: ['TeamSeasonRosters']);
        assert($stat instanceof \App\Model\Entity\StatBasketSeasonPerson);
        $roster = $stat->team_season_roster ?? null;
        $teamSeasonId = null;
        if ($roster) {
            assert($roster instanceof \App\Model\Entity\TeamSeasonRosters);
            $teamSeasonId = $roster->team_season_id;
        }

        if ($this->StatBasketSeasonPerson->delete($stat)) {
            $this->Flash->success(__('The player season stats have been deleted.'));
        } else {
            $this->Flash->error(__('The player season stats could not be deleted. Please, try again.'));
        }

        return $this->redirect(['controller' => 'TeamSeasons', 'action' => 'view', $teamSeasonId]);
    }
}
