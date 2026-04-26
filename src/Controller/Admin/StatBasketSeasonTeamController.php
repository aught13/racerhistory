<?php
declare(strict_types=1);

namespace App\Controller\Admin;

use Cake\Http\Response;

/**
 * StatBasketSeasonTeam Controller (Admin)
 *
 * Manages basketball team season statistics. Provides actions to edit and delete team season stats for a given team season. The edit action allows for creating or updating team season stats, while the delete action removes the team season stats for a specific team season. Both actions include appropriate success and error flash messages to inform the user of the outcome of their actions. The controller relies on the StatBasketSeasonTeam model for data management and assumes that proper authentication and authorization checks are handled by middleware or components not shown in this code snippet. The edit action retrieves the existing stats for the given team season ID or creates a new entity if none exist, and then processes form submissions to save the stats. The delete action ensures that the request method is POST or DELETE to prevent accidental deletions via GET requests, and it attempts to find and delete the relevant stats, providing feedback to the user based on the success of the operation. After successful edits or deletions, the user is redirected back to the team season view page for continuity in their workflow.
 * The view action is not included in this controller, as team season stats are typically displayed on the team season view page rather than having a dedicated view page for the stats themselves. Instead, the edit and delete actions focus on managing the stats data, while the display of the stats is handled in the context of the team season view. Proper validation, error handling, and feedback mechanisms are crucial for maintaining data integrity and providing a good user experience when managing team season stats in the admin interface.
 * The controller is designed to be straightforward and focused on the specific task of managing team season stats, while relying on other parts of the application to handle the display and context of these stats within the broader team season information. This separation of concerns helps keep the controller simple and maintainable, while still providing the necessary functionality for administrators to manage team season stats effectively.
 *
 * Actions:
 * - edit: Handles both the creation and updating of team season stats for a given team season. It retrieves existing stats or creates a new entity, processes form submissions, and provides feedback through flash messages. After a successful save, it redirects the user back to the team season view page.
 * - delete: Handles the deletion of team season stats for a given team season. It ensures that the request method is appropriate, attempts to find and delete the relevant stats, and provides feedback through flash messages. After a successful deletion, it redirects the user back to the team season view page.
 *
 * Security:
 * - Both actions should be protected by authentication and authorization checks to ensure that only authorized users can edit or delete team season stats. This is typically handled by middleware or components that are not shown in this code snippet.
 * - The edit action should validate the input data to prevent invalid or malicious data from being saved to the database. Proper error handling and feedback mechanisms should be implemented to inform the user of any issues during the save process.
 * - The delete action should ensure that the request method is POST or DELETE to prevent accidental deletions via GET requests. Proper error handling and feedback mechanisms should be implemented to inform the user of any issues during the deletion process.
 *
 * Dependencies:
 * - StatBasketSeasonTeam: The model used for managing team season stats data. The controller interacts with this model to retrieve, create, update, and delete team season stats based on the team season ID. The model should include appropriate validation rules to ensure data integrity when saving stats.
 *
 * Components:
 * - FlashComponent: Used to set success and error messages after attempting to save or delete stats, providing feedback to the user about the outcome of their actions.
 * - AuthorizationComponent: Used to manage access control, ensuring that only authorized users can perform edit and delete actions on team season stats. This component should be configured to check the user's permissions for these specific actions, providing an additional layer of security to protect sensitive data.
 *
 * Note: The edit and delete actions rely on the team season ID to identify the relevant team season stats. The edit action ensures that both creating new stats and updating existing stats are handled seamlessly, while the delete action ensures that only the relevant stats for the specified team season are removed. Proper validation, error handling, and feedback mechanisms are crucial for maintaining data integrity and providing a good user experience when managing team season stats in the admin interface. After successful edits or deletions, redirecting back to the team season view page helps maintain continuity in the user's workflow when managing team season stats in the admin interface.
 *
 * @property \App\Model\Table\StatBasketSeasonTeamTable $StatBasketSeasonTeam
 * @property \Authorization\Controller\Component\AuthorizationComponent $Authorization
 * @property \Cake\Controller\Component\FlashComponent $Flash
 */
class StatBasketSeasonTeamController extends AppController
{
    /**
     * Edit method - create or update team season stats
     *
     * @param int $teamSeasonId Team Season ID
     * @return \Cake\Http\Response|null|void Redirects on successful edit, renders view otherwise.
     */
    public function edit(int $teamSeasonId)
    {
        // Try to find existing stats, or create new
        $stat = $this->StatBasketSeasonTeam
            ->find()
            ->where(['team_season_id' => $teamSeasonId])
            ->first();

        if (!$stat) {
            $stat = $this->StatBasketSeasonTeam->newEmptyEntity();
            assert($stat instanceof \App\Model\Entity\StatBasketSeasonTeam);
            $stat->team_season_id = $teamSeasonId;
        }
        assert($stat instanceof \App\Model\Entity\StatBasketSeasonTeam);

        if ($this->request->is(['patch', 'post', 'put'])) {
            $stat = $this->StatBasketSeasonTeam->patchEntity($stat, $this->request->getData());
            assert($stat instanceof \App\Model\Entity\StatBasketSeasonTeam);
            if ($this->StatBasketSeasonTeam->save($stat)) {
                $this->Flash->success(__('The team season stats have been saved.'));

                return $this->redirect(['controller' => 'TeamSeasons', 'action' => 'view', $teamSeasonId]);
            }
            $this->Flash->error(__('The team season stats could not be saved. Please, try again.'));
        }

        $teamSeason = $this->fetchTable('TeamSeasons')->get($teamSeasonId, contain: ['Teams', 'Seasons']);

        $this->set(compact('stat', 'teamSeason'));
    }

    /**
     * Delete method - remove team season stats
     *
     * @param int $teamSeasonId Team Season ID
     * @return \Cake\Http\Response|null Redirects to team season view
     */
    public function delete(int $teamSeasonId): ?Response
    {
        $this->request->allowMethod(['post', 'delete']);
        $stat = $this->StatBasketSeasonTeam
            ->find()
            ->where(['team_season_id' => $teamSeasonId])
            ->first();

        if ($stat && $this->StatBasketSeasonTeam->delete($stat)) {
            $this->Flash->success(__('The team season stats have been deleted.'));
        } else {
            $this->Flash->error(__('The team season stats could not be deleted. Please, try again.'));
        }

        return $this->redirect(['controller' => 'TeamSeasons', 'action' => 'view', $teamSeasonId]);
    }
}
