<?php
declare(strict_types=1);

namespace App\Controller\Admin;

use App\Service\OpponentService;
use App\Service\PersonService;
use App\Service\PlaceService;
use Cake\Http\Response;

/**
 * TagLookupsController
 *
 * Lightweight JSON endpoints for tag UI autocomplete.
 */
class TagLookupsController extends AppController
{
    /**
     * Search persons for autocomplete.
     * Query param: q
     */
    public function persons(): Response
    {
        $this->request->allowMethod(['get']);

        $q = trim((string)$this->request->getQuery('q'));
        if ($q == '') {
            return $this->json(['success' => true, 'persons' => []]);
        }

        $service = new PersonService();
        $persons = $service->searchPersons($q, 25);

        $out = [];
        foreach ($persons as $p) {
            $out[] = [
                'id' => (int)$p['id'],
                'label' => (string)$p['label'],
            ];
        }

        return $this->json(['success' => true, 'persons' => $out]);
    }

    /**
     * Search games for autocomplete.
     * Query params: q, teamseason_id (optional)
     */
    public function games(): Response
    {
        $this->request->allowMethod(['get']);

        $q = trim((string)$this->request->getQuery('q'));
        $teamSeasonId = (int)$this->request->getQuery('teamseason_id');

        if ($q === '' && $teamSeasonId <= 0) {
            return $this->json(['success' => true, 'games' => []]);
        }

        /** @var \App\Model\Table\GamesTable $games */
        $games = $this->fetchTable('Games');
        $query = $games->find()
            ->contain(['TeamSeason' => ['Teams'], 'Opponents'])
            ->orderByDesc('Games.game_date')
            ->limit(25);

        if ($teamSeasonId > 0) {
            $query->where(['Games.team_season_id' => $teamSeasonId]);
        }

        if ($q !== '') {
            $like = '%' . str_replace('%', '\\%', $q) . '%';
            $query->where([
                'OR' => [
                    ['Opponents.opponent_name LIKE' => $like],
                    ['Teams.team_name LIKE' => $like],
                ],
            ]);
        }

        $out = [];
        foreach ($query->all() as $g) {
            $teamName = $g->team_season->team->team_name ?? 'Team';
            $oppName = $g->opponent->opponent_name ?? 'Opponent';

            $date = '';
            if (!empty($g->game_date)) {
                if ($g->game_date instanceof \Cake\I18n\Date) {
                    $date = $g->game_date->i18nFormat('yyyy-MM-dd');
                } elseif ($g->game_date instanceof \DateTimeInterface) {
                    $date = $g->game_date->format('Y-m-d');
                } else {
                    $date = (string)$g->game_date;
                }
            }

            $score = $g->pts_mur !== null && $g->pts_opp !== null ? " {$g->pts_mur}-{$g->pts_opp}" : '';

            $separator = match ((int)($g->hrn ?? 0)) {
                1 => ' Vs ',
                2 => ' @ ',
                3 => ' vs ',
                default => ' vs ',
            };

            $label = $teamName . $separator . $oppName
                . ($date ? ' (' . $date . ')' : '') . $score;

            $out[] = [
                'id' => (int)$g->id,
                'team_season_id' => (int)$g->team_season_id,
                'label' => $label,
            ];
        }

        return $this->json(['success' => true, 'games' => $out]);
    }

    /**
     * Search opponents for autocomplete.
     * Query param: q
     */
    public function opponents(): Response
    {
        $this->request->allowMethod(['get']);

        $q = trim((string)$this->request->getQuery('q'));
        if ($q === '') {
            return $this->json(['success' => true, 'opponents' => []]);
        }

        $service = new OpponentService();
        $rows = $service->searchOpponents($q, 25);

        $out = [];
        foreach ($rows as $row) {
            $out[] = [
                'id' => (int)$row->id,
                'label' => (string)($row->opponent_name ?? 'Opponent #' . $row->id),
            ];
        }

        return $this->json(['success' => true, 'opponents' => $out]);
    }

    /**
     * Search sites for autocomplete.
     * Query param: q
     */
    public function sites(): Response
    {
        $this->request->allowMethod(['get']);

        $q = trim((string)$this->request->getQuery('q'));
        if ($q === '') {
            return $this->json(['success' => true, 'sites' => []]);
        }

        $service = new PlaceService();
        $rows = $service->searchSites($q, 25);

        $out = [];
        foreach ($rows as $row) {
            $out[] = [
                'id' => (int)$row->id,
                'label' => $service->getSiteDisplayLabel((int)$row->id),
            ];
        }

        return $this->json(['success' => true, 'sites' => $out]);
    }

    /**
     * Return roster entries for a given person.
     * Query param: person_id
     */
    public function rosters(): Response
    {
        $this->request->allowMethod(['get']);

        $personId = (int)$this->request->getQuery('person_id');
        if ($personId <= 0) {
            return $this->json(['success' => true, 'rosters' => []]);
        }

        /** @var \App\Model\Table\TeamSeasonRostersTable $rosters */
        $rosters = $this->fetchTable('TeamSeasonRosters');
        $rows = $rosters->find()
            ->contain(['Persons', 'TeamSeasons' => ['Teams', 'Seasons']])
            ->where(['TeamSeasonRosters.person_id' => $personId])
            ->all();

        $out = [];
        foreach ($rows as $r) {
            $pname = trim(($r->person->first ?? '') . ' ' . ($r->person->last ?? '')) ?: '#' . $r->person_id;

            $teamSeason = $r->team_season ?? null;
            $seasonLabel = '';
            if ($teamSeason) {
                $teamName = $teamSeason->team->team_name ?? 'Team';
                if (!empty($teamSeason->season)) {
                    $start = $teamSeason->season->start ?? null;
                    $end = $teamSeason->season->end ?? null;
                    if ($start && $end && $start != $end) {
                        $seasonLabel = " ({$start}-{$end})";
                    } elseif ($start) {
                        $seasonLabel = " ({$start})";
                    }
                }
                $label = $pname . ' — ' . $teamName . $seasonLabel;
            } else {
                $label = $pname;
            }

            $out[] = ['id' => (int)$r->id, 'label' => $label];
        }

        return $this->json(['success' => true, 'rosters' => $out]);
    }

    /**
     * Return JSON response helper.
     *
     * @param array<string,mixed> $payload
     */
    private function json(array $payload): Response
    {
        return $this->response->withType('application/json')->withStringBody(json_encode($payload));
    }
}
