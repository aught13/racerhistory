<?php
declare(strict_types=1);

namespace App\Controller\Admin;

use App\Service\GameEavMetaService;
use App\Service\GameEavUiService;
use App\Service\GameUpsertService;
use App\Service\GameViewService;
use Cake\Http\Response;

/**
 * Admin Games Controller
 *
 * Manages games and associated values (game types, opponent, site/place) and
 * EAV attributes such as period scores and officials.
 * Provides CRUD operations for games, as well as AJAX endpoints for dynamic form metadata and site selection.
 * The controller uses GameUpsertService to handle the business logic of adding and editing games,
 * GameViewService to assemble data for the view action, and GameEavMetaService/GameEavUiService to manage EAV metadata and UI generation.
 * The index action provides a listing of games with optional filtering by team season, while the ajax
 * ajaxList action supports server-side processing for DataTables with pagination, searching, and sorting.
 * The ajaxGameEavMeta action serves as a unified endpoint for retrieving EAV metadata for
 * both the add and edit forms, returning either JSON or rendered HTML fragments based on the request parameters. The ajaxSitesByPlace action allows for dynamic retrieval of sites based on a selected place, enhancing the user experience when managing game locations.
 * All actions should be protected by authentication and authorization checks to ensure that only authorized users can manage
 * games and related data. The controller is designed to be flexible and extensible, allowing for additional features or modifications to the game management workflow without significant changes to the core logic.
 * The delete and bulkDelete actions should be used with caution, as they will permanently remove game records from the database. Proper confirmation and safeguards should be implemented in the UI to prevent accidental deletions.
 * The add and edit actions handle both form display and submission, providing feedback to the user through flash messages and redirecting as appropriate based on the outcome of the operations. The controller relies on services to abstract away the business logic and data manipulation, keeping the controller focused on handling requests and formatting responses.
 * The setFormLists method is used to prepare data for select inputs in the add and edit forms, including options for teams, seasons, sports, sites, and other related entities. This method can be enhanced in the future to include additional filters or options as needed.
 * Sport-specific logic, such as determining if a sport has associated stats tables, is handled through the SportConfigService and integrated into the view data for the addResults and edit actions, allowing for dynamic adjustments to the UI based on the sport of the game being managed.
 * The controller is designed to be maintainable and scalable, with a clear separation of concerns between request handling, business logic, and data retrieval. This structure allows for easier testing and future enhancements to the game management features in the admin interface.
 *
 * Actions:
 * - index: Displays a list of games with optional filtering by team season.
 * - ajaxList: Provides server-side processing for DataTables, returning paginated, searchable,
 * and sortable game data in JSON format.
 * - ajaxGameEavMeta: Returns EAV metadata for a game or team season,
 * with support for both JSON and HTML responses based on request parameters.
 * - ajaxSitesByPlace: Returns a list of sites filtered by place ID in JSON format
 * - view: Displays detailed information about a specific game, including associated EAV attributes and related data.
 * - add: Handles both displaying the form for adding a new game and processing the form submission
 * to create the game. Requires a team_season_id query parameter to associate the new game with a team season.
 * - addResults: Handles adding results to an existing game, including scores and EAV fields
 * - edit: Handles both displaying the form for editing an existing game and processing the form submission
 * to update the game. Requires a game_id query parameter to identify the game being edited.
 * - delete: Handles the deletion of a single game. Requires a game_id query parameter to identify the game to be deleted.
 * - bulkDelete: Handles the deletion of multiple games. Requires an array of game_ids to identify the games to be deleted.
 *
 * Security:
 * - All actions should be protected by authentication and authorization checks to ensure that only authorized users can
 * manage games and related data. This is typically handled by middleware or components that are not shown in this code snippet.
 * - The delete and bulkDelete actions should be used with caution, as they will permanently remove
 * game records from the database. Proper confirmation and safeguards should be implemented in the UI to prevent accidental deletions.
 * - The add and edit actions should validate input data to prevent invalid or malicious data from being
 * saved to the database. This includes validating required fields, data types, and any business rules related to game management.
 * - The ajaxGameEavMeta and ajaxSitesByPlace actions should validate input parameters to
 * prevent potential issues with invalid input or unauthorized access to data. For example, the ajaxGameEavMeta action should ensure that the game_id or team_season_id parameters are valid and that the requesting user has permission to access the associated data.
 * - The controller should also implement proper error handling and feedback mechanisms to inform users of any issues that arise during game management operations, such as validation errors, database errors, or permission issues. This can be achieved through the use of flash messages and appropriate HTTP response codes for AJAX requests.
 * - The controller should also consider implementing logging for critical actions such as game creation, updates, and deletions to maintain an audit trail of changes made to game records in the admin interface.
 * - The controller should also ensure that any sensitive information related to games, such as internal IDs or metadata, is not exposed inappropriately through the views or AJAX responses. Proper access controls and data sanitization should be implemented to protect sensitive data.
 * - The controller should also consider implementing rate limiting or other protections for the AJAX endpoints to prevent abuse or excessive load on the server, especially for actions that involve data retrieval or manipulation.
 * - The controller should also ensure that any user-generated content or input is properly sanitized and escaped in the views to prevent cross-site scripting (XSS) vulnerabilities, especially in areas where game data or EAV attributes are displayed.
 * - The controller should also consider implementing CSRF protection for form submissions in the add, edit, delete, and bulkDelete actions to prevent cross-site request forgery attacks. This can be achieved through the use of CakePHP's built-in CSRF protection features.
 * - The controller should also ensure that any file uploads or media associated with games are properly handled and secured to prevent unauthorized access or malicious file uploads. This includes validating file types, sizes, and implementing proper storage and access controls for uploaded files.
 *
 * Dependencies:
 * - GameService: Provides methods for retrieving and manipulating game data, including building data for the DataTables AJAX response, retrieving sites by place, and handling bulk deletions. This service abstracts the business logic related to games and allows the controller to focus on request handling and response formatting.
 * - SportConfigService: Provides methods for retrieving sport configuration data, such as determining if a sport has associated stats tables. This service allows the controller to dynamically adjust the UI and functionality based on the sport of the game being managed.
 * - StatsService: Provides methods for retrieving sport-specific statistics and related data, which can be integrated into the game view and management interfaces to provide additional context and information about the games being managed.
 * - GameEavMetaService: Provides methods for retrieving EAV metadata for games and team seasons, which is used to dynamically generate form fields for game attributes in the add and edit interfaces. This service abstracts the logic of managing EAV metadata and allows for flexible handling of sport-specific attributes.
 * - GameEavUiService: Provides methods for generating UI components related to game EAV attributes, such as building variables for rendering sport-specific fields in the forms. This service helps to keep the controller focused on request handling while delegating UI generation logic to a dedicated service.
 *
 * Components:
 * - FlashComponent: Used to set success and error messages after create, update, and delete operations, providing feedback to the user about the outcome of their actions.
 * - AuthorizationComponent: Used to protect all actions in this controller, ensuring that only authorized users can manage games and related data. This is typically configured to require authentication and specific permissions for accessing game management features in the admin interface.
 * - RequestHandlerComponent: Can be used to automatically detect AJAX requests and set response types, although in this implementation we manually check for JSON requests in the ajaxGameEavMeta and ajaxSitesByPlace actions to adjust the response format accordingly.
 * - The controller should also consider implementing additional components or middleware for logging, rate limiting, or other cross-cutting concerns related to game management in the admin interface.
 *
 * Note: The delete and bulkDelete actions should be used with caution, as they will permanently remove game records from the database. Proper confirmation and safeguards should be implemented in the UI to prevent accidental deletions. Additionally, the add and edit actions should validate input data to prevent invalid or malicious data from being saved to the database, and the AJAX endpoints should validate input parameters to prevent potential issues with invalid input or unauthorized access to data. Proper error handling, feedback mechanisms, logging, and security measures should be implemented throughout the controller to ensure a secure and user-friendly experience for managing games in the admin interface.
 *
 * @property \App\Model\Table\GamesTable $Games
 * @property \App\Service\GameService $Game
 * @property \App\Service\SportConfigService $SportConfig
 * @property \App\Service\StatsService $Stats
 * @property \App\Service\GameEavMetaService $gameEavMeta
 * @property \App\Service\GameEavUiService $gameEavUi
 * @property \App\Service\GameViewService $gameView
 * @property \App\Service\GameUpsertService $gameUpsert
 * @property \Authorization\Controller\Component\AuthorizationComponent $Authorization
 * @property \Cake\Controller\Component\FlashComponent $Flash
 */
class GamesController extends AppController
{
    private GameEavUiService $gameEavUi;
    private GameEavMetaService $gameEavMeta;
    private GameViewService $gameView;
    private GameUpsertService $gameUpsert;

    /**
     * @var \App\Service\GameService Service for game-related business logic
     */
    protected \App\Service\GameService $Game;

    /**
     * @var \App\Service\SportConfigService Service for sport configuration management
     */
    protected \App\Service\SportConfigService $SportConfig;

    /**
     * @var \App\Service\StatsService Service for sport-specific statistics
     */
    protected \App\Service\StatsService $Stats;

    /**
     * Initialize controller
     *
     * @return void
     */
    public function initialize(): void
    {
        parent::initialize();
        $this->loadService('Game');
        $this->loadService('SportConfig');
        $this->loadService('Stats');

        $this->gameEavUi = new GameEavUiService();
        $this->gameEavMeta = new GameEavMetaService($this->Game, $this->gameEavUi);
        $this->gameView = new GameViewService($this->Game, $this->SportConfig, $this->Stats, $this->gameEavUi);
        $this->gameUpsert = new GameUpsertService($this->Games, $this->Game, $this->SportConfig, $this->gameEavUi);
    }

    /**
     * Unified AJAX meta endpoint.
     * Accepts one of:
     *  - game_id: load existing game, infer team_season/sport, include existing EAV values
     *  - team_season_id: load sport meta without existing values
     * Returns JSON: { success, sportId, sportName, configs, eavTemplate, values }
     *
     * @return \Cake\Http\Response|null
     */
    public function ajaxGameEavMeta(): ?Response
    {
        $this->request->allowMethod(['get']);
        $gameId = (int)$this->request->getQuery('game_id');
        $teamSeasonId = (int)$this->request->getQuery('team_season_id');

        $result = $this->gameEavMeta->getMetadataResult($gameId ?: null, $teamSeasonId ?: null);
        $payload = $result['payload'];

        // If HTML fragment requested, render the server-side element and return HTML
        $format = $this->request->getQuery('format');
        if ($format === 'html' && !empty($payload['success']) && !empty($result['metadata'])) {
            $vars = $this->gameEavMeta->buildSportSpecificFieldsElementVars($result['metadata']);

            $html = $this->viewBuilder()
                ->setClassName('App\View\AppView')
                ->build()
                ->element('Admin/Games/sport_specific_fields', $vars);

            return $this->response->withType('text/html')->withStringBody($html);
        }

        return $this->response
            ->withType('application/json')
            ->withStringBody(json_encode($payload));
    }

    /**
     * AJAX endpoint to get sites filtered by place_id
     *
     * @return \Cake\Http\Response|null
     */
    public function ajaxSitesByPlace(): ?Response
    {
        $this->request->allowMethod(['get']);
        $placeId = (int)$this->request->getQuery('place_id');

        $sites = $this->Game->getSitesByPlace($placeId);

        return $this->response
            ->withType('application/json')
            ->withStringBody(json_encode(['sites' => $sites]));
    }

    /**
     * List games with associations.
     */
    public function index(): void
    {
        // Check for team season filter context
        $teamSeasonId = $this->request->getQuery('team_season_id');
        if ($teamSeasonId) {
            $teamSeason = $this->fetchTable('TeamSeasons')->get($teamSeasonId, contain: ['Teams', 'Seasons']);
            $this->set('teamSeason', $teamSeason);
        }

        $this->set('teamSeasonId', $teamSeasonId);
    }

    /**
     * AJAX endpoint for DataTables server-side processing.
     * Returns JSON data with pagination, search, and sorting.
     */
    public function ajaxList(): void
    {
        $this->request->allowMethod(['get', 'post']);
        $this->viewBuilder()->setClassName('Json')->disableAutoLayout();
        $draw = (int)$this->request->getData('draw', $this->request->getQuery('draw', 1));
        $start = (int)$this->request->getData('start', $this->request->getQuery('start', 0));
        $length = (int)$this->request->getData('length', $this->request->getQuery('length', 25));
        $searchValue = $this->request->getData('search.value', $this->request->getQuery('search.value', ''));
        $teamSeasonId = $this->request->getQuery('team_season_id');
        $searchBuilder = $this->request->getData('searchBuilder', $this->request->getQuery('searchBuilder'));

        $result = $this->Game->buildGamesDataTable([
            'start' => $start,
            'length' => $length,
            'searchValue' => $searchValue,
            'teamSeasonId' => $teamSeasonId,
            'searchBuilder' => $searchBuilder,
        ]);

        $this->set([
            'draw' => $draw,
            'recordsTotal' => $result['recordsTotal'],
            'recordsFiltered' => $result['recordsFiltered'],
            'data' => $result['data'],
        ]);
        $this->viewBuilder()->setOption('serialize', ['draw', 'recordsTotal', 'recordsFiltered', 'data']);
    }

    /**
     * Apply SearchBuilder criteria to query
     *
     * @param \Cake\ORM\Query $query The query to modify
     * @param array $criteria SearchBuilder criteria array
     * @param string $logic Logic operator (AND/OR)
     * @return void
     */

    /**
     * View a game with associations and EAV attributes.
     *
     * @param string $id Game ID
     */
    public function view(string $id): void
    {
        $this->set($this->gameView->getViewData((int)$id));
    }

    /**
     * Add a new game.
     */
    public function add(): ?Response
    {
        // Require team_season_id for add
        $teamSeasonId = (int)$this->request->getQuery('team_season_id');
        if (!$teamSeasonId) {
            $this->Flash->error(__('You must add a game from within a team season.'));

            return $this->redirect(['prefix' => 'Admin', 'controller' => 'TeamSeasons', 'action' => 'index']);
        }

        if ($this->request->is('post')) {
            $result = $this->gameUpsert->processAdd($teamSeasonId, $this->request->getData());

            foreach (($result['flashErrors'] ?? []) as $error) {
                $this->Flash->error(__((string)$error));
            }
            if (!empty($result['flashSuccess'])) {
                $this->Flash->success(__((string)$result['flashSuccess']));
            }
            if (!empty($result['redirect'])) {
                return $this->redirect($result['redirect']);
            }

            $this->setFormLists();
            $this->set($result['viewData'] ?? []);

            return null;
        }

        $this->setFormLists();
        $this->set($this->gameUpsert->getAddViewData($teamSeasonId));

        return null;
    }

    /**
     * Add results to an existing game (scores, EAV fields).
     *
     * @param string $id Game ID
     */
    public function addResults(string $id): ?Response
    {
        $viewData = $this->gameUpsert->getEditViewData((int)$id);
        $game = $viewData['game'];

        // Determine if the sport has box-score stats
        $sportHasStats = false;
        $sportId = $viewData['sportId'] ?? 0;
        if ($sportId) {
            $statTables = $this->SportConfig->getAllStatTables($sportId);
            $sportHasStats = !empty($statTables);
        }
        $viewData['sportHasStats'] = $sportHasStats;

        if ($this->request->is(['patch', 'post', 'put'])) {
            $result = $this->gameUpsert->processEdit((int)$id, $this->request->getData());

            foreach (($result['flashErrors'] ?? []) as $error) {
                $this->Flash->error(__((string)$error));
            }
            if (!empty($result['flashSuccess'])) {
                $this->Flash->success(__((string)$result['flashSuccess']));
            }
            if (!empty($result['redirect'])) {
                // After results save with stats sport, offer box score
                if ($sportHasStats) {
                    $this->Flash->success(__('Would you like to enter box scores? {0}', sprintf(
                        '<a href="%s" class="alert-link">Enter Box Scores</a>',
                        \Cake\Routing\Router::url([
                            'prefix' => 'Admin',
                            'controller' => 'StatBasketGameBox',
                            'action' => 'gameBox',
                            $game->id,
                        ]),
                    )), ['escape' => false]);
                }

                return $this->redirect($result['redirect']);
            }

            $viewData = array_merge($viewData, $result['viewData'] ?? []);
            $viewData['sportHasStats'] = $sportHasStats;
        }

        $this->set($viewData);

        return null;
    }

    /**
     * Edit a game.
     *
     * @param string $id Game ID
     */
    public function edit(string $id): ?Response
    {
        if ($this->request->is(['patch', 'post', 'put'])) {
            $result = $this->gameUpsert->processEdit((int)$id, $this->request->getData());

            foreach (($result['flashErrors'] ?? []) as $error) {
                $this->Flash->error(__((string)$error));
            }
            if (!empty($result['flashSuccess'])) {
                $this->Flash->success(__((string)$result['flashSuccess']));
            }
            if (!empty($result['redirect'])) {
                if ($this->request->getData('save_action') === 'box_score') {
                    return $this->redirect([
                        'prefix' => 'Admin',
                        'controller' => 'StatBasketGameBox',
                        'action' => 'gameBox',
                        $id,
                    ]);
                }

                return $this->redirect($result['redirect']);
            }

            $this->setFormLists($result['placeId'] ?? null);
            $viewData = $result['viewData'] ?? [];
            $viewData['sportHasStats'] = $this->determineSportHasStats($viewData['sportId'] ?? 0);
            $this->set($viewData);

            return null;
        }

        $viewData = $this->gameUpsert->getEditViewData((int)$id);
        $viewData['sportHasStats'] = $this->determineSportHasStats($viewData['sportId'] ?? 0);
        $this->setFormLists($viewData['game']->place_id ?? null);
        $this->set($viewData);

        return null;
    }

    /**
     * Determine if a sport has stat tables configured.
     *
     * @param int $sportId Sport ID
     * @return bool
     */
    private function determineSportHasStats(int $sportId): bool
    {
        if (!$sportId) {
            return false;
        }

        return !empty($this->SportConfig->getAllStatTables($sportId));
    }

    /**
     * Delete a game.
     */
    public function delete(string $id): Response
    {
        $this->request->allowMethod(['post', 'delete']);
        $game = $this->Games->get($id);
        if ($this->Games->delete($game)) {
            $this->Flash->success(__('The game has been deleted.'));
        } else {
            $this->Flash->error(__('The game could not be deleted. Please, try again.'));
        }

        return $this->redirect(['action' => 'index']);
    }

    /**
     * Bulk action dispatcher for games.
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

    /**
     * Bulk delete selected games.
     */
    public function bulkDelete(): Response
    {
        $this->request->allowMethod(['post']);
        $ids = (array)$this->request->getData('game_ids');
        $result = $this->Game->bulkDeleteGames($ids);
        if ($result['deleted'] > 0) {
            $this->Flash->success(__('Deleted {0} game(s).', $result['deleted']));
        } else {
            $this->Flash->error(__('No games were deleted.'));
        }

        if ($result['teamSeasonId']) {
            return $this->redirect([
                'prefix' => 'Admin', 'controller' => 'TeamSeasons', 'action' => 'view', $result['teamSeasonId'],
            ]);
        }

        return $this->redirect(['action' => 'index']);
    }

    /**
     * Build lists for select inputs and provide inline create options.
     *
     * @param int|null $placeId Optional place ID to filter sites
     */
    private function setFormLists(?int $placeId = null): void
    {
        $lists = $this->Game->getFormLists($placeId);
        $extra = $this->Game->getTeamSeasonAndSportsLists();
        $this->set(array_merge($lists, $extra));
    }

    // Sport-aware UI vars and legacy EAV mapping are handled via GameService + GameEavUiService.
}
