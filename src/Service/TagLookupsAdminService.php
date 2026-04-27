<?php
declare(strict_types=1);

namespace App\Service;

/**
 * TagLookupsAdminService
 *
 * Owns the admin JSON lookup slice used by autocomplete/tagging widgets.
 * This service centralizes lookup orchestration and payload shaping so
 * Admin/TagLookupsController remains a thin HTTP adapter.
 *
 * Endpoints handled:
 * - persons: search persons for select/autocomplete fields
 * - games: search games with optional team-season scoping
 * - opponents: search opponents
 * - sites: search sites
 * - rosters: fetch roster entries for a selected person
 *
 * Payload contracts are intentionally stable because frontend widgets and
 * controller tests depend on exact key names.
 */
class TagLookupsAdminService
{
    /**
     * @var \App\Service\PersonService
     */
    private PersonService $personService;

    /**
     * @var \App\Service\GameService
     */
    private GameService $gameService;

    /**
     * @var \App\Service\OpponentService
     */
    private OpponentService $opponentService;

    /**
     * @var \App\Service\SiteService
     */
    private SiteService $siteService;

    /**
     * @var \App\Service\TeamSeasonRosterService
     */
    private TeamSeasonRosterService $teamSeasonRosterService;

    /**
     * @param \App\Service\PersonService|null $personService
     * @param \App\Service\GameService|null $gameService
     * @param \App\Service\OpponentService|null $opponentService
     * @param \App\Service\SiteService|null $siteService
     * @param \App\Service\TeamSeasonRosterService|null $teamSeasonRosterService
     */
    public function __construct(
        ?PersonService $personService = null,
        ?GameService $gameService = null,
        ?OpponentService $opponentService = null,
        ?SiteService $siteService = null,
        ?TeamSeasonRosterService $teamSeasonRosterService = null,
    ) {
        $this->personService = $personService ?? new PersonService();
        $this->gameService = $gameService ?? new GameService();
        $this->opponentService = $opponentService ?? new OpponentService();
        $this->siteService = $siteService ?? new SiteService();
        $this->teamSeasonRosterService = $teamSeasonRosterService ?? new TeamSeasonRosterService();
    }

    /**
     * Search persons and return lookup payload.
     *
     * @param string $query
     * @return array{success:bool,persons:array<int,array{id:int,label:string}>}
     */
    public function persons(string $query): array
    {
        $q = trim($query);
        if ($q === '') {
            return ['success' => true, 'persons' => []];
        }

        $rows = $this->personService->searchPersons($q, 25);

        $out = [];
        foreach ($rows as $row) {
            $out[] = [
                'id' => (int)$row['id'],
                'label' => (string)$row['label'],
            ];
        }

        return ['success' => true, 'persons' => $out];
    }

    /**
     * Search games and return lookup payload.
     *
     * @param string $query
     * @param int|null $teamSeasonId
     * @return array{success:bool,games:array<int,array{id:int,team_season_id:int,label:string}>}
     */
    public function games(string $query, ?int $teamSeasonId = null): array
    {
        $q = trim($query);
        $filterTeamSeasonId = $teamSeasonId !== null && $teamSeasonId > 0 ? $teamSeasonId : null;
        if ($q === '' && $filterTeamSeasonId === null) {
            return ['success' => true, 'games' => []];
        }

        $rows = $this->gameService->searchGamesForSelect($q, $filterTeamSeasonId, 25);

        $out = [];
        foreach ($rows as $row) {
            $out[] = [
                'id' => (int)$row['id'],
                'team_season_id' => (int)($row['team_season_id'] ?? 0),
                'label' => (string)$row['label'],
            ];
        }

        return ['success' => true, 'games' => $out];
    }

    /**
     * Search opponents and return lookup payload.
     *
     * @param string $query
     * @return array{success:bool,opponents:array<int,array{id:int,label:string}>}
     */
    public function opponents(string $query): array
    {
        $q = trim($query);
        if ($q === '') {
            return ['success' => true, 'opponents' => []];
        }

        $rows = $this->opponentService->searchOpponents($q, 25);

        $out = [];
        foreach ($rows as $row) {
            $id = (int)$row->id;
            $out[] = [
                'id' => $id,
                'label' => (string)($row->opponent_name ?? 'Opponent #' . $id),
            ];
        }

        return ['success' => true, 'opponents' => $out];
    }

    /**
     * Search sites and return lookup payload.
     *
     * @param string $query
     * @return array{success:bool,sites:array<int,array{id:int,label:string}>}
     */
    public function sites(string $query): array
    {
        $q = trim($query);
        if ($q === '') {
            return ['success' => true, 'sites' => []];
        }

        $rows = $this->siteService->searchSites($q, 25);

        $out = [];
        foreach ($rows as $row) {
            $id = (int)$row->id;
            $out[] = [
                'id' => $id,
                'label' => $this->siteService->getDisplayLabel($id),
            ];
        }

        return ['success' => true, 'sites' => $out];
    }

    /**
     * Return roster entries for a selected person.
     *
     * @param int $personId
     * @return array{success:bool,rosters:array<int,array{id:int,label:string}>}
     */
    public function rosters(int $personId): array
    {
        if ($personId <= 0) {
            return ['success' => true, 'rosters' => []];
        }

        $rows = $this->teamSeasonRosterService->getRostersForPersonLookup($personId, 200);

        $out = [];
        foreach ($rows as $row) {
            $out[] = [
                'id' => (int)$row['id'],
                'label' => (string)$row['label'],
            ];
        }

        return ['success' => true, 'rosters' => $out];
    }
}
