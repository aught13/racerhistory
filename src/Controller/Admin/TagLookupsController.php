<?php
declare(strict_types=1);

namespace App\Controller\Admin;

use App\Service\GameService;
use App\Service\OpponentService;
use App\Service\PersonService;
use App\Service\SiteService;
use App\Service\TeamSeasonRosterService;
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

        $service = new GameService();
        $rows = $service->searchGamesForSelect($q, $teamSeasonId > 0 ? $teamSeasonId : null, 25);

        $out = [];
        foreach ($rows as $row) {
            $out[] = [
                'id' => (int)$row['id'],
                'team_season_id' => (int)($row['team_season_id'] ?? 0),
                'label' => (string)$row['label'],
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

        $service = new SiteService();
        $rows = $service->searchSites($q, 25);

        $out = [];
        foreach ($rows as $row) {
            $out[] = [
                'id' => (int)$row->id,
                'label' => $service->getDisplayLabel((int)$row->id),
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

        $service = new TeamSeasonRosterService();
        $rows = $service->getRostersForPersonLookup($personId, 200);

        $out = [];
        foreach ($rows as $row) {
            $out[] = [
                'id' => (int)$row['id'],
                'label' => (string)$row['label'],
            ];
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
