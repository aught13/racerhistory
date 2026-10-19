<?php
declare(strict_types=1);

namespace App\Controller;

use App\Service\PublicSportScopeService;
use App\Service\SeasonViewService;
use App\Service\TeamSeasonService;
use Cake\Event\EventInterface;
use Cake\Http\Exception\BadRequestException;
use Cake\Http\Exception\NotFoundException;

/**
 * Public Seasons Controller
 *
 * Displays team seasons (filtered to Men's Basketball by default),
 * with related images and blog posts via the tagging system.
 * Provides both a standard list view and a splits view, which groups seasons by team.
 * The season view page shows detailed information about a specific season, including stats and related content.
 * All actions are publicly accessible and skip authorization checks.
 * The controller relies on TeamSeasonService for data retrieval and processing, and
 * SeasonViewService for assembling view data for the season detail page.
 * The index and splits actions support Turbo Frames for seamless updates when filtering by
 * team or sport.
 * The controller is designed to be flexible and extensible, allowing for additional filters or view modes
 * in the future without significant changes to the core logic.
 *
 * Actions:
 * - index: Lists seasons with optional filtering by team and sport. Supports Turbo Frames for dynamic updates.
 * - splits: Similar to index but groups seasons by team. Also supports Turbo Frames.
 * - view: Displays detailed information about a specific season, including stats and related content. Throws
 * NotFoundException if the season does not exist.
 *
 * Security:
 * - All actions skip authorization to allow public access to season information.
 * - The view action checks for the existence of the season and throws a NotFoundException
 * if it does not exist, preventing access to invalid season IDs.
 *
 * Dependencies:
 * - TeamSeasonService: Provides methods for retrieving and processing team season data, including public seasons
 * list, season stats, and record summaries.
 * - SeasonViewService: Assembles comprehensive view data for the season detail page, including
 * team season information, stats, and related content.
 *
 * Components:
 * - AuthorizationComponent: Used to skip authorization checks for all actions in this controller, as the
 * season information is intended to be publicly accessible.
 *
 * @property \Authorization\Controller\Component\AuthorizationComponent $Authorization
 * @property \App\Model\Table\SeasonsTable $Seasons
 */
class SeasonsController extends AppController
{
    private TeamSeasonService $teamSeasonService;
    private SeasonViewService $seasonViewService;
    private PublicSportScopeService $publicSportScopeService;

    /**
     * Initialize controller.
     */
    public function initialize(): void
    {
        parent::initialize();
        $this->loadComponent('Authorization.Authorization');
        $this->teamSeasonService = new TeamSeasonService();
        $this->seasonViewService = new SeasonViewService($this->teamSeasonService);
        $this->publicSportScopeService = new PublicSportScopeService();
    }

    /**
     * Skip authorization for public actions.
     *
     * @param \Cake\Event\EventInterface $event Event instance.
     * @return void
     */
    public function beforeFilter(EventInterface $event): void
    {
        parent::beforeFilter($event);
        $this->Authorization->skipAuthorization();
    }

    /**
     * List seasons.
     *
     * @return void
     */
    public function index(): void
    {
        $data = $this->buildIndexData();
        $scope = $data['publicScope'];
        $this->set($data + [
            'viewMode' => 'standard',
            'teamFilter' => $scope['teamId'] === null ? 'all' : (string)$scope['teamId'],
        ]);

        if ($this->request->getHeaderLine('Turbo-Frame') === 'seasons-table-frame') {
            $this->viewBuilder()
                ->setLayout(null)
                ->setTemplate('frame');
        }
    }

    /**
     * Splits view.
     *
     * @return void
     */
    public function splits(): void
    {
        $data = $this->buildIndexData();
        $scope = $data['publicScope'];
        $this->set($data + [
            'viewMode' => 'splits',
            'teamFilter' => $scope['teamId'] === null ? 'all' : (string)$scope['teamId'],
        ]);

        if ($this->request->getHeaderLine('Turbo-Frame') === 'seasons-table-frame') {
            $this->viewBuilder()
                ->setLayout(null)
                ->setTemplate('frame');
        }
    }

    /**
     * @return array<string,mixed>
     */
    private function buildIndexData(): array
    {
        $scope = $this->resolvePublicScope();
        $gender = $this->request->getQuery('gender', '');
        if (!is_string($gender) || !in_array($gender, ['', 'M', 'F', 'C'], true)) {
            throw new BadRequestException('Invalid gender filter.');
        }

        $teamSeasons = $this->teamSeasonService->getPublicSeasonsList(
            $scope['sportKey'] ?? '',
            $gender,
            $scope['teamId'],
        );

        $teamSeasonIds = array_map(static fn($ts) => (int)$ts->id, $teamSeasons);
        $seasonStats = $this->teamSeasonService->calculateSeasonStats($teamSeasonIds);

        $recordSummaries = [];
        foreach ($teamSeasons as $teamSeason) {
            $recordSummaries[(int)$teamSeason->id] = $this->teamSeasonService->getRecordSummary((int)$teamSeason->id);
        }

        return compact('teamSeasons', 'seasonStats', 'recordSummaries', 'scope') + [
            'publicScope' => $scope,
        ];
    }

    /**
     * @return array{valid:bool,error:string|null,sportKey:string|null,teamId:int|null}
     */
    private function resolvePublicScope(): array
    {
        $scope = $this->publicSportScopeService->resolve($this->request->getQueryParams());
        if (!$scope['valid']) {
            throw new BadRequestException('Invalid sport or team selection.');
        }

        return $scope;
    }

    /**
     * View a season.
     *
     * @param int $id TeamSeason ID.
     * @return void
     */
    public function view(int $id): void
    {
        $scope = $this->resolvePublicScope();
        $viewData = $this->seasonViewService->getViewData($id);
        $teamSeason = $viewData['teamSeason'] ?? null;
        if (!$teamSeason) {
            throw new NotFoundException('Season not found');
        }

        if ($scope['teamId'] !== null && $scope['teamId'] !== (int)$teamSeason->team_id) {
            throw new NotFoundException('Season not found');
        }

        if ($scope['sportKey'] !== null) {
            $teamScope = $this->publicSportScopeService->resolve([
                'sport' => $scope['sportKey'],
                'team' => (string)$teamSeason->team_id,
            ]);
            if (!$teamScope['valid']) {
                throw new NotFoundException('Season not found');
            }
        }

        unset($viewData['teamSeason']);
        $this->set(['teamSeason' => $teamSeason, 'publicScope' => $scope] + $viewData);
    }
}
