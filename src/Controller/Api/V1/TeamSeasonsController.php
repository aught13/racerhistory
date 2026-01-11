<?php
declare(strict_types=1);

namespace App\Controller\Api\V1;

use App\Service\TeamSeasonService;

class TeamSeasonsController extends AppController
{
    private TeamSeasonService $teamSeasonService;

    /**
     * @inheritDoc
     */
    public function initialize(): void
    {
        parent::initialize();
        $this->teamSeasonService = new TeamSeasonService();
    }

    /**
     * List team seasons (supports ?include=details).
     */
    public function index(): void
    {
        $this->request->allowMethod(['get']);

        $limit = $this->getLimit(200, 500);
        $include = trim((string)$this->getRequest()->getQuery('include', ''));

        if ($include === 'details') {
            $rows = $this->teamSeasonService->getAllTeamSeasons();
            $rows = array_slice($rows, 0, $limit);

            $data = [];
            foreach ($rows as $ts) {
                $data[] = [
                    'id' => (int)$ts->id,
                    'label' => $this->teamSeasonService->getSportDisplayLabel((int)$ts->id),
                    'team_id' => $ts->team_id ?? null,
                    'season_id' => $ts->season_id ?? null,
                    'team' => [
                        'id' => $ts->team->id ?? null,
                        'team_name' => $ts->team->team_name ?? null,
                        'gender' => $ts->team->gender ?? null,
                        'sport' => [
                            'id' => $ts->team->sport->id ?? null,
                            'sport_name' => $ts->team->sport->sport_name ?? null,
                        ],
                    ],
                    'season' => [
                        'id' => $ts->season->id ?? null,
                        'start' => $ts->season->start ?? null,
                        'end' => $ts->season->end ?? null,
                    ],
                ];
            }

            $this->respond([
                'data' => $data,
                'meta' => [
                    'count' => count($data),
                    'limit' => $limit,
                ],
            ]);

            return;
        }

        $rows = $this->teamSeasonService->getTeamSeasonsForSelect();
        $data = array_slice($rows, 0, $limit);

        $this->respond([
            'data' => $data,
            'meta' => [
                'count' => count($data),
                'limit' => $limit,
            ],
        ]);
    }

    /**
     * Get a single team season.
     */
    public function view(int $id): void
    {
        $this->request->allowMethod(['get']);

        $ts = $this->teamSeasonService->getTeamSeasonById($id);
        if (!$ts) {
            $this->respondError('Team season not found', 404);

            return;
        }

        $this->respond([
            'data' => [
                'id' => (int)$ts->id,
                'label' => $this->teamSeasonService->getDisplayLabel((int)$ts->id),
                'sport_label' => $this->teamSeasonService->getSportDisplayLabel((int)$ts->id),
                'team_id' => $ts->team_id ?? null,
                'season_id' => $ts->season_id ?? null,
                'team' => [
                    'id' => $ts->team->id ?? null,
                    'team_name' => $ts->team->team_name ?? null,
                    'gender' => $ts->team->gender ?? null,
                    'sport' => [
                        'id' => $ts->team->sport->id ?? null,
                        'sport_name' => $ts->team->sport->sport_name ?? null,
                    ],
                ],
                'season' => [
                    'id' => $ts->season->id ?? null,
                    'start' => $ts->season->start ?? null,
                    'end' => $ts->season->end ?? null,
                ],
            ],
        ]);
    }
}
