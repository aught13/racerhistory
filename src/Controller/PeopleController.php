<?php
declare(strict_types=1);

namespace App\Controller;

use App\Service\BlogPostService;
use App\Service\ImageProcessor;
use App\Service\PeopleDirectoryService;
use Cake\Controller\ComponentRegistry;
use Cake\Event\EventInterface;
use Cake\Http\Exception\BadRequestException;
use Cake\Http\Exception\NotFoundException;
use Cake\Http\ServerRequest;

/**
 * Public People Controller
 *
 * Displays persons (players/coaches/staff) with related images and blog posts.
 * Supports listing all people with server-side pagination and searching for DataTables.
 * Provides a detailed view for each person, showing their team seasons, related images,
 * blog posts, and career stats.
 * Game log view for supported sports showing per-game stats for a specific team season.
 * This controller is read-only and does not require authentication or authorization for
 * any actions.
 * The controller relies on the PersonService for fetching person data, the ImageProcessor
 * for retrieving related images, and the StatsService for calculating career stats and game logs.
 *
 * Actions:
 * - index: List all people with server-side support for DataTables (pagination, searching,
 *  and ordering). Returns JSON for DataTables requests or renders a view for regular requests.
 * - view: Display detailed information about a single person, including related images,
 * blog posts, team seasons, and career stats.
 * - gameLog: Render a game log for a person and team season, showing per-game stats for supported
 * sports.
 *
 * Data Flow:
 * - The index action handles both regular and DataTables requests. For DataTables, it processes
 * query parameters for pagination, searching, and ordering, and returns a JSON response formatted
 * for DataTables consumption. For regular requests, it renders a view with the total count of people.
 * - The view action retrieves a person by ID, fetches related images and blog posts via
 * tagging, gets roster entries for the person, organizes them by sport, calculates career
 * stats using the StatsService, and sets all this data for the view.
 * - The gameLog action retrieves a person and a specific roster entry for a team season,
 * checks if the sport supports stats, and if so, retrieves game log data to render in a view.
 *
 * Security:
 * - The controller skips authorization for all actions, making it publicly accessible.
 * - Input parameters are validated and sanitized, and appropriate exceptions are thrown for not
 * found resources.
 *
 * Dependencies:
 * - PersonService: Handles business logic and data retrieval for person entities.
 * - ImageProcessor: Manages image processing and retrieval for person-related images.
 * - StatsService: Calculates career stats and game logs for persons.
 *
 * Components:
 * - AuthorizationComponent: Used to skip authorization checks for all actions, as the person
 * information is intended to be publicly accessible.
 * - RequestHandlerComponent: Can be used to automatically detect AJAX requests and set response types,
 * although in this implementation we manually check for JSON requests in each action.
 *
 * Design Considerations:
 * - The controller is designed to be read-only and focused on displaying data. It does not
 * handle any data modification operations. It relies on services for business logic and data retrieval,
 * keeping the controller thin and focused on request handling and response formatting.
 *
 * @property \Authorization\Controller\Component\AuthorizationComponent $Authorization
 */
class PeopleController extends AppController
{
    private BlogPostService $blogPostService;
    private ImageProcessor $imageProcessor;
    private PeopleDirectoryService $peopleDirectoryService;

    /**
     * @var array{valid:bool,error:string|null,sportKey:string|null,teamId:int|null}
     */
    private array $publicScope = ['valid' => true, 'error' => null, 'sportKey' => null, 'teamId' => null];

    /**
     * Create the controller with its people and view services.
     *
     * @param \Cake\Http\ServerRequest $request The current request.
     * @param \App\Service\BlogPostService $blogPostService The blog post service.
     * @param \App\Service\ImageProcessor $imageProcessor The image processor.
     * @param \App\Service\PeopleDirectoryService $peopleDirectoryService The people directory service.
     * @param \Cake\Controller\ComponentRegistry|null $components The component registry.
     */
    public function __construct(
        ServerRequest $request,
        BlogPostService $blogPostService,
        ImageProcessor $imageProcessor,
        PeopleDirectoryService $peopleDirectoryService,
        ?ComponentRegistry $components = null,
    ) {
        parent::__construct($request, null, null, $components);
        $this->blogPostService = $blogPostService;
        $this->imageProcessor = $imageProcessor;
        $this->peopleDirectoryService = $peopleDirectoryService;
    }

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
     *
     * @param \Cake\Event\EventInterface $event
     */
    public function beforeFilter(EventInterface $event): void
    {
        parent::beforeFilter($event);
        $this->Authorization->skipAuthorization();

        $this->publicScope = $this->PublicScope->resolve($this->request->getQueryParams());
        if (!$this->publicScope['valid']) {
            throw new BadRequestException('Invalid sport or team selection.');
        }
    }

    /**
     * List all people.
     */
    public function index(): void
    {
        if (
            $this->request->getQuery('format') === 'json'
            || $this->request->accepts('application/json')
        ) {
            $this->renderPeopleIndexJson();

            return;
        }

        $peopleCount = $this->peopleDirectoryService->countPeople(
            $this->publicScope['sportKey'],
            $this->publicScope['teamId'],
        );

        $this->set([
            'people' => [],
            'peopleRows' => [],
            'peopleCount' => $peopleCount,
            'publicScope' => $this->publicScope,
        ]);
    }

    /**
     * Render People index data for DataTables (server-side).
     */
    private function renderPeopleIndexJson(): void
    {
        $this->autoRender = false;
        $payload = $this->peopleDirectoryService->dataTablesPayload(
            $this->request->getQueryParams(),
            $this->publicScope['sportKey'],
            $this->publicScope['teamId'],
        );

        $this->response = $this->response
            ->withType('application/json')
            ->withStringBody(json_encode($payload, JSON_THROW_ON_ERROR));
    }

    /**
     * View a single person with related data.
     *
     * @param int $id
     */
    public function view(int $id): void
    {
        $showAllHistory = $this->request->getQuery('show_all_history') === '1';
        $viewData = $this->peopleDirectoryService->profileViewData(
            $id,
            $this->publicScope,
            $showAllHistory,
        );
        if ($viewData === null) {
            throw new NotFoundException('Person not found');
        }

        $images = $this->imageProcessor->getImagesForPerson($id, 20);
        $personPosts = $this->blogPostService->getPublishedForPerson($id);
        $this->set(array_merge($viewData, [
            'images' => $images,
            'bioPosts' => $personPosts['bioPosts'],
            'stories' => $personPosts['stories'],
            'showAllHistory' => $showAllHistory,
            'publicScope' => $this->publicScope,
        ]));
    }

    /**
     * Render the game log for a person and team season.
     *
     * @param int $personId
     * @param int $teamSeasonId
     */
    public function gameLog(int $personId, int $teamSeasonId): void
    {
        $person = $this->peopleDirectoryService->getPersonById($personId);
        if ($person === null) {
            throw new NotFoundException('Person not found');
        }

        $viewData = $this->peopleDirectoryService->gameLogViewData(
            $person,
            $teamSeasonId,
            $this->publicScope,
        );
        if ($viewData === null) {
            throw new NotFoundException('Roster entry not found');
        }

        $this->set($viewData);

        $this->viewBuilder()->disableAutoLayout();
        $this->viewBuilder()->setTemplate('game_log');
    }
}
