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

        $seasonStats = $this->calculateSeasonStats($teamSeasons);

        $this->set(compact('teamSeasons', 'seasonStats'));
    }

    /**
     * @param array<int,\App\Model\Entity\TeamSeason> $teamSeasons
     * @return array<int,array<string,int|float|null>>
     */
    private function calculateSeasonStats(array $teamSeasons): array
    {
        if (empty($teamSeasons)) {
            return [];
        }

        $teamSeasonIds = array_map(static fn($ts) => (int)$ts->id, $teamSeasons);

        /** @var \App\Model\Table\GamesTable $gamesTable */
        $gamesTable = $this->fetchTable('Games');
        $query = $gamesTable->find();

        $rawStats = $query
            ->select([
                'team_season_id' => 'Games.team_season_id',
                'overall_wins' => $query->newExpr(
                    "SUM(CASE WHEN Games.w IN ('1','W') THEN 1 ELSE 0 END)",
                ),
                'overall_losses' => $query->newExpr(
                    "SUM(CASE WHEN Games.l IN ('1','L') THEN 1 ELSE 0 END)",
                ),
                'conf_wins' => $query->newExpr(
                    "SUM(CASE WHEN GameTypes.conf = 1 AND Games.w IN ('1','W') THEN 1 ELSE 0 END)",
                ),
                'conf_losses' => $query->newExpr(
                    "SUM(CASE WHEN GameTypes.conf = 1 AND Games.l IN ('1','L') THEN 1 ELSE 0 END)",
                ),
            ])
            ->where(['Games.team_season_id IN' => $teamSeasonIds])
            ->leftJoinWith('GameTypes')
            ->groupBy(['Games.team_season_id'])
            ->enableHydration(false)
            ->toArray();

        $stats = [];
        foreach ($rawStats as $row) {
            $id = (int)$row['team_season_id'];
            $ow = (int)$row['overall_wins'];
            $ol = (int)$row['overall_losses'];
            $cw = (int)$row['conf_wins'];
            $cl = (int)$row['conf_losses'];
            $overallTotal = $ow + $ol;
            $confTotal = $cw + $cl;

            $stats[$id] = [
                'overall_wins' => $ow,
                'overall_losses' => $ol,
                'overall_pct' => $overallTotal > 0 ? round($ow / $overallTotal, 3) : null,
                'conf_wins' => $cw,
                'conf_losses' => $cl,
                'conf_pct' => $confTotal > 0 ? round($cw / $confTotal, 3) : null,
            ];
        }

        return $stats;
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
