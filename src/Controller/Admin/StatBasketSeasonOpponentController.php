<?php
declare(strict_types=1);

namespace App\Controller\Admin;

use Cake\Http\Response;

/**
 * StatBasketSeasonOpponent Controller (Admin)
 *
 * Manages basketball opponent season statistics. Provides actions to edit and delete opponent season stats for a given team season. The edit action allows for creating or updating opponent season stats, while the delete action removes the opponent season stats for a specific team season. Both actions include appropriate success and error flash messages to inform the user of the outcome of their actions. The controller relies on the StatBasketSeasonOpponent model for data management and assumes that proper authentication and authorization checks are handled by middleware or components not shown in this code snippet. The edit action retrieves the existing stats for the given team season ID or creates a new entity if none exist, and then processes form submissions to save the stats. The delete action ensures that the request method is POST or DELETE to prevent accidental deletions via GET requests, and it attempts to find and delete the relevant stats, providing feedback to the user based on the success of the operation. After successful edits or deletions, the user is redirected back to the team season view page for continuity in their workflow.
 *
 * Actions:
 * - edit: Handles both the creation and updating of opponent season stats for a given team season. It retrieves existing stats or creates a new entity, processes form submissions, and provides feedback through flash messages. After a successful save, it redirects the user back to the team season view page.
 * - delete: Handles the deletion of opponent season stats for a given team season. It ensures that the request method is appropriate, attempts to find and delete the relevant stats, and provides feedback through flash messages. After a successful deletion, it redirects the user back to the team season view page.
 *
 * Security:
 * - Both actions should be protected by authentication and authorization checks to ensure that only authorized users can edit or delete opponent season stats. This is typically handled by middleware or components that are not shown in this code snippet.
 * - The edit action should validate the input data to prevent invalid or malicious data from being saved to the database. Proper error handling and feedback mechanisms should be implemented to inform the user of any issues during the save process.
 * - The delete action should ensure that the request method is POST or DELETE to prevent accidental deletions via GET requests. Proper error handling and feedback mechanisms should be implemented to inform the user of any issues during the deletion process.
 *
 * Dependencies:
 * - StatBasketSeasonOpponent: The model used for managing opponent season stats data. The controller interacts with this model to retrieve, create, update, and delete opponent season stats based on the team season ID. The model should include appropriate validation rules to ensure data integrity when saving stats.
 *
 * Components:
 * - FlashComponent: Used to set success and error messages after attempting to save or delete stats, providing feedback to the user about the outcome of their actions.
 * - AuthorizationComponent: Used to manage access control, ensuring that only authorized users can perform edit and delete actions on opponent season stats. This component should be configured to check the user's permissions for these specific actions, providing an additional layer of security to protect sensitive data.
 *
 * Note: The edit and delete actions rely on the team season ID to identify the relevant opponent season stats. The edit action ensures that both creating new stats and updating existing stats are handled seamlessly, while the delete action ensures that only the relevant stats for the specified team season are removed. Proper validation, error handling, and feedback mechanisms are crucial for maintaining data integrity and providing a good user experience when managing opponent season stats in the admin interface.
 *
 * @property \App\Model\Table\StatBasketSeasonOpponentTable $StatBasketSeasonOpponent
 * @property \Authorization\Controller\Component\AuthorizationComponent $Authorization
 * @property \Cake\Controller\Component\FlashComponent $Flash
 */
class StatBasketSeasonOpponentController extends AppController
{
    /**
     * Edit method - create or update opponent season stats
     *
     * @param int $teamSeasonId Team Season ID
     * @return \Cake\Http\Response|null|void Redirects on successful edit, renders view otherwise.
     */
    public function edit(int $teamSeasonId)
    {
        // Try to find existing stats, or create new
        $stat = $this->StatBasketSeasonOpponent
            ->find()
            ->where(['team_season_id' => $teamSeasonId])
            ->first();

        if (!$stat) {
            $stat = $this->StatBasketSeasonOpponent->newEmptyEntity();
            assert($stat instanceof \App\Model\Entity\StatBasketSeasonOpponent);
            $stat->team_season_id = $teamSeasonId;
        }
        assert($stat instanceof \App\Model\Entity\StatBasketSeasonOpponent);

        if ($this->request->is(['patch', 'post', 'put'])) {
            $stat = $this->StatBasketSeasonOpponent->patchEntity($stat, $this->request->getData());
            assert($stat instanceof \App\Model\Entity\StatBasketSeasonOpponent);
            if ($this->StatBasketSeasonOpponent->save($stat)) {
                $this->Flash->success(__('The opponent season stats have been saved.'));

                return $this->redirect(['controller' => 'TeamSeasons', 'action' => 'view', $teamSeasonId]);
            }
            $this->Flash->error(__('The opponent season stats could not be saved. Please, try again.'));
        }

        $teamSeason = $this->fetchTable('TeamSeasons')->get($teamSeasonId, contain: ['Teams', 'Seasons']);

        $this->set(compact('stat', 'teamSeason'));
    }

    /**
     * Delete method - remove opponent season stats
     *
     * @param int $teamSeasonId Team Season ID
     * @return \Cake\Http\Response|null Redirects to team season view
     */
    public function delete(int $teamSeasonId): ?Response
    {
        $this->request->allowMethod(['post', 'delete']);
        $stat = $this->StatBasketSeasonOpponent
            ->find()
            ->where(['team_season_id' => $teamSeasonId])
            ->first();

        if ($stat && $this->StatBasketSeasonOpponent->delete($stat)) {
            $this->Flash->success(__('The opponent season stats have been deleted.'));
        } else {
            $this->Flash->error(__('The opponent season stats could not be deleted. Please, try again.'));
        }

        return $this->redirect(['controller' => 'TeamSeasons', 'action' => 'view', $teamSeasonId]);
    }
}
