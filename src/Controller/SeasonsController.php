<?php
declare(strict_types=1);

namespace App\Controller;

use App\Service\SeasonViewService;
use App\Service\TeamSeasonService;
use Cake\Event\EventInterface;
use Cake\Http\Exception\NotFoundException;

/**
 * Public Seasons Controller
 *
 * Displays team seasons (filtered to Men's Basketball by default),
 * with related images and blog posts via the tagging system.
 *
 * @property \Authorization\Controller\Component\AuthorizationComponent $Authorization
 */
class SeasonsController extends AppController
{
    private TeamSeasonService $teamSeasonService;
    private SeasonViewService $seasonViewService;

    /**
     * Initialize controller.
     */
    public function initialize(): void
    {
        parent::initialize();
        $this->loadComponent('Authorization.Authorization');
        $this->teamSeasonService = new TeamSeasonService();
        $this->seasonViewService = new SeasonViewService($this->teamSeasonService);
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
        $teamFilter = (string)$this->request->getQuery('team', 'all');
        $this->set($this->buildIndexData() + [
            'viewMode' => 'standard',
            'teamFilter' => $teamFilter,
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
        $teamFilter = (string)$this->request->getQuery('team', 'all');
        $this->set($this->buildIndexData() + [
            'viewMode' => 'splits',
            'teamFilter' => $teamFilter,
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
        $sport = ucfirst(strtolower((string)$this->request->getQuery('sport', 'Basketball')));
        $gender = (string)$this->request->getQuery('gender', 'M');

        $teamSeasons = $this->teamSeasonService->getPublicSeasonsList($sport, $gender);

        $teamSeasonIds = array_map(static fn($ts) => (int)$ts->id, $teamSeasons);
        $seasonStats = $this->teamSeasonService->calculateSeasonStats($teamSeasonIds);

        $recordSummaries = [];
        foreach ($teamSeasons as $teamSeason) {
            $recordSummaries[(int)$teamSeason->id] = $this->teamSeasonService->getRecordSummary((int)$teamSeason->id);
        }

        return compact('teamSeasons', 'seasonStats', 'recordSummaries');
    }

    /**
     * View a season.
     *
     * @param int $id TeamSeason ID.
     * @return void
     */
    public function view(int $id): void
    {
        $viewData = $this->seasonViewService->getViewData($id);
        $teamSeason = $viewData['teamSeason'] ?? null;
        if (!$teamSeason) {
            throw new NotFoundException('Season not found');
        }

        unset($viewData['teamSeason']);
        $this->set(['teamSeason' => $teamSeason] + $viewData);
    }
}
