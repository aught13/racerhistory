<?php
declare(strict_types=1);

namespace App\Service;

use App\Model\Entity\Person;
use App\Model\Entity\TeamSeasonRosters;
use App\Model\Table\PersonsTable;
use App\Model\Table\TeamSeasonRostersTable;
use Cake\ORM\Query\SelectQuery;
use Cake\Routing\Router;

class PeopleDirectoryService
{
    /**
     * Create the people directory service with its query and scope dependencies.
     *
     * @param \App\Model\Table\PersonsTable $personsTable The persons table.
     * @param \App\Model\Table\TeamSeasonRostersTable $teamSeasonRostersTable The roster table.
     * @param \App\Service\PersonService $personService The person service.
     * @param \App\Service\StatsService $statsService The statistics service.
     * @param \App\Service\TeamSportContextService $teamSportContextService The team sport context service.
     * @param \App\Service\PublicSportScopeService $publicSportScopeService The public sport scope service.
     */
    public function __construct(
        private PersonsTable $personsTable,
        private TeamSeasonRostersTable $teamSeasonRostersTable,
        private PersonService $personService,
        private StatsService $statsService,
        private TeamSportContextService $teamSportContextService,
        private PublicSportScopeService $publicSportScopeService,
    ) {
    }

    /**
     * Count people visible within the requested public scope.
     *
     * @param string|null $sportKey The selected sport key.
     * @param int|null $teamId The selected team ID.
     * @return int
     */
    public function countPeople(?string $sportKey, ?int $teamId): int
    {
        return $this->personService->applyPublicRosterScope(
            $this->personsTable->find(),
            $sportKey,
            $teamId,
        )->count();
    }

    /**
     * @param array<string,mixed> $queryParams
     * @param string|null $sportKey The selected sport key.
     * @param int|null $teamId The selected team ID.
     * @return array{draw:int,recordsTotal:int,recordsFiltered:int,data:array<int,array<int,string>>}
     */
    public function dataTablesPayload(
        array $queryParams,
        ?string $sportKey,
        ?int $teamId,
    ): array {
        $drawValue = $queryParams['draw'] ?? 0;
        $draw = is_scalar($drawValue) ? (int)$drawValue : 0;
        $startValue = $queryParams['start'] ?? 0;
        $start = is_scalar($startValue) ? max(0, (int)$startValue) : 0;
        $lengthValue = $queryParams['length'] ?? 0;
        $length = is_scalar($lengthValue) ? (int)$lengthValue : 0;
        if ($length < 1) {
            $length = 50;
        }
        $length = min($length, 200);

        $searchParams = $queryParams['search'] ?? null;
        $searchValue = is_array($searchParams) ? ($searchParams['value'] ?? '') : '';
        $search = is_scalar($searchValue) ? trim((string)$searchValue) : '';

        $total = $this->countPeople($sportKey, $teamId);
        $query = $this->personService->applyPublicRosterScope(
            $this->personsTable->find()->select(['id', 'first', 'last', 'full', 'display']),
            $sportKey,
            $teamId,
        );

        $this->applyDataTablesOrder($query, $queryParams['order'] ?? null);
        if ($search !== '') {
            $query->where([
                'OR' => [
                    'Persons.first LIKE' => '%' . $search . '%',
                    'Persons.last LIKE' => '%' . $search . '%',
                    'Persons.full LIKE' => '%' . $search . '%',
                    'Persons.display LIKE' => '%' . $search . '%',
                ],
            ]);
        }

        $filtered = $query->count();
        $people = $query
            ->limit($length)
            ->offset($start)
            ->all()
            ->toArray();
        $rows = $this->buildPeopleRows($people, $sportKey, $teamId);

        return [
            'draw' => $draw,
            'recordsTotal' => $total,
            'recordsFiltered' => $filtered,
            'data' => $this->formatPeopleRowsForDataTables($rows, $sportKey, $teamId),
        ];
    }

    /**
     * Find a person by ID.
     *
     * @param int $personId The person ID.
     * @return \App\Model\Entity\Person|null
     */
    public function getPersonById(int $personId): ?Person
    {
        return $this->personService->getPersonById($personId);
    }

    /**
     * @param int $personId The person ID.
     * @param array{valid:bool,error:string|null,sportKey:string|null,teamId:int|null} $scope
     * @param bool $showAllHistory Whether to include history outside the selected scope.
     * @return array<string,mixed>|null
     */
    public function profileViewData(int $personId, array $scope, bool $showAllHistory): ?array
    {
        $person = $this->personService->getPersonById($personId);
        if ($person === null) {
            return null;
        }

        $rosterEntries = $this->personService->getRosterEntriesForPeople(
            [$personId],
            $showAllHistory ? null : $scope['sportKey'],
            $showAllHistory ? null : $scope['teamId'],
        );
        $rostersBySport = [];
        $careerStatsBySport = [];

        foreach ($rosterEntries as $roster) {
            $teamSeason = $roster->team_season ?? null;
            if ($teamSeason === null) {
                continue;
            }

            $team = $teamSeason->team ?? null;
            if ($team === null) {
                continue;
            }

            $this->teamSportContextService->attachSportContextToTeam($team);
            $sport = $team->sport ?? null;
            $sportId = $this->teamSportContextService->resolveSportIdFromTeam($team);
            if ($sport === null || $sportId === null) {
                continue;
            }

            if (!isset($rostersBySport[$sportId])) {
                $rostersBySport[$sportId] = [
                    'sport' => $sport,
                    'rosters' => [],
                ];
            }
            $rostersBySport[$sportId]['rosters'][] = [
                'roster' => $roster,
                'teamSeason' => $teamSeason,
            ];

            if (!$this->statsService->hasSportSupport($sportId)) {
                continue;
            }

            if (!isset($careerStatsBySport[$sportId])) {
                $careerStatsBySport[$sportId] = [
                    'sport' => $sport,
                    'totals' => $this->statsService->initializeStats($sportId, 'player'),
                    'seasons' => [],
                    'minYear' => null,
                    'maxYear' => null,
                ];
            }

            $seasonStats = $this->statsService->getPersonSeasonStats($sportId, (int)$roster->id);
            if (!$seasonStats) {
                continue;
            }

            $careerStatsBySport[$sportId]['seasons'][] = [
                'teamSeason' => $teamSeason,
                'stats' => $seasonStats,
            ];

            $startYear = $teamSeason->season->start ?? null;
            $endYear = $teamSeason->season->end ?? null;
            if ($startYear !== null) {
                $minYear = $careerStatsBySport[$sportId]['minYear'];
                if ($minYear === null || $startYear < $minYear) {
                    $careerStatsBySport[$sportId]['minYear'] = $startYear;
                }
            }
            if ($endYear !== null) {
                $maxYear = $careerStatsBySport[$sportId]['maxYear'];
                if ($maxYear === null || $endYear > $maxYear) {
                    $careerStatsBySport[$sportId]['maxYear'] = $endYear;
                }
            }

            $this->statsService->addSeasonStats(
                $sportId,
                $careerStatsBySport[$sportId]['totals'],
                $seasonStats,
            );
        }

        return [
            'person' => $person,
            'rosterEntries' => $rosterEntries,
            'rostersBySport' => $rostersBySport,
            'careerStatsBySport' => $careerStatsBySport,
            'gameLogGroups' => $this->buildGameLogGroups($rosterEntries),
            'gameStats' => [],
        ];
    }

    /**
     * @param \App\Model\Entity\Person $person The person whose log is requested.
     * @param int $teamSeasonId The team season ID.
     * @param array{valid:bool,error:string|null,sportKey:string|null,teamId:int|null} $scope
     * @return array<string,mixed>|null
     */
    public function gameLogViewData(Person $person, int $teamSeasonId, array $scope): ?array
    {
        $roster = $this->teamSeasonRostersTable->find()
            ->contain(['TeamSeasons' => ['Teams', 'Seasons']])
            ->where([
                'TeamSeasonRosters.person_id' => $person->id,
                'TeamSeasonRosters.team_season_id' => $teamSeasonId,
            ])
            ->first();

        if (!$roster instanceof TeamSeasonRosters) {
            return null;
        }

        $teamSeason = $roster->team_season ?? null;
        if ($teamSeason === null) {
            return null;
        }

        $rosterTeamId = (int)($teamSeason->team_id ?? 0);
        if ($scope['teamId'] !== null && $scope['teamId'] !== $rosterTeamId) {
            return null;
        }
        if ($scope['sportKey'] !== null) {
            $teamScope = $this->publicSportScopeService->resolve([
                'sport' => $scope['sportKey'],
                'team' => (string)$rosterTeamId,
            ]);
            if (!$teamScope['valid']) {
                return null;
            }
        }

        $team = $teamSeason->team ?? null;
        if ($team !== null) {
            $this->teamSportContextService->attachSportContextToTeam($team);
        }
        $sport = $team?->sport;
        $sportId = $team === null
            ? null
            : $this->teamSportContextService->resolveSportIdFromTeam($team);
        $gameLogRows = [];
        $gameLogElement = null;
        if ($sport !== null && $sportId !== null && $this->statsService->hasSportSupport($sportId)) {
            $gameLogElement = $this->statsService->getPersonGameLogElement($sportId);
            $gameLogRows = $this->statsService->getPersonGameStats($sportId, (int)$roster->id);
        }

        return [
            'person' => $person,
            'teamSeason' => $teamSeason,
            'sport' => $sport,
            'gameLogRows' => $gameLogRows,
            'gameLogElement' => $gameLogElement,
            'frameId' => 'person-game-log-frame-' . $person->id . '-' . $teamSeasonId,
        ];
    }

    /**
     * @param \Cake\ORM\Query\SelectQuery $query The query to order.
     * @param array<int,mixed> $order
     */
    private function applyDataTablesOrder(SelectQuery $query, mixed $order): void
    {
        $direction = 'asc';
        if (is_array($order) && $order !== []) {
            $firstOrder = reset($order);
            $rawDirection = is_array($firstOrder) ? ($firstOrder['dir'] ?? 'asc') : 'asc';
            $requestedDirection = is_scalar($rawDirection)
                ? strtolower((string)$rawDirection)
                : 'asc';
            if (in_array($requestedDirection, ['asc', 'desc'], true)) {
                $direction = $requestedDirection;
            }
        }

        if ($direction === 'desc') {
            $query->orderByDesc('Persons.last')->orderByDesc('Persons.first');

            return;
        }

        $query->orderByAsc('Persons.last')->orderByAsc('Persons.first');
    }

    /**
     * @param array<int,\App\Model\Entity\Person> $people
     * @param string|null $sportKey The selected sport key.
     * @param int|null $teamId The selected team ID.
     * @return array<int,array{person:\App\Model\Entity\Person,teams:array<int,string>,years:array<int,array{id:int,label:string,start:int,sportKey:string,teamId:int}>}>
     */
    private function buildPeopleRows(array $people, ?string $sportKey, ?int $teamId): array
    {
        if ($people === []) {
            return [];
        }

        $personIds = [];
        foreach ($people as $person) {
            if ($person->id !== null) {
                $personIds[] = (int)$person->id;
            }
        }

        $rosterMap = [];
        if ($personIds !== []) {
            foreach ($this->personService->getRosterEntriesForPeople($personIds, $sportKey, $teamId) as $roster) {
                $personId = (int)($roster->person_id ?? 0);
                if ($personId > 0) {
                    $rosterMap[$personId][] = $roster;
                }
            }
        }

        $peopleRows = [];
        foreach ($people as $person) {
            $teams = [];
            $years = [];
            foreach ($rosterMap[(int)$person->id] ?? [] as $roster) {
                $teamSeason = $roster->team_season ?? null;
                $team = $teamSeason?->team;
                if ($team !== null) {
                    $this->teamSportContextService->attachSportContextToTeam($team);
                    $resolvedTeamId = (int)($team->id ?? 0);
                    $teamLabel = trim((string)($team->abbr ?? ''));
                    if ($teamLabel === '') {
                        $teamLabel = $team->getDisplayName();
                    }
                    if ($sportKey === null) {
                        $teamLabel .= ' (' . (string)($team->sport_name ?? 'Sport') . ')';
                    }
                    if ($resolvedTeamId > 0) {
                        $teams[$resolvedTeamId] = $teamLabel;
                    }
                }

                $season = $teamSeason?->season;
                if ($season === null || ($season->start === null && $season->end === null)) {
                    continue;
                }

                $start = (string)($season->start ?? '');
                $end = (string)($season->end ?? '');
                $label = trim($start . '-' . ($end !== '' ? substr($end, -2) : ''), '-');
                $teamSeasonId = (int)($teamSeason->id ?? 0);
                if ($label !== '' && $teamSeasonId > 0) {
                    $years[$teamSeasonId] = [
                        'id' => $teamSeasonId,
                        'label' => $label,
                        'start' => (int)($season->start ?? 0),
                        'sportKey' => $this->teamSportContextService->resolveSportKeyFromTeam($team) ?? '',
                        'teamId' => (int)($team->id ?? 0),
                    ];
                }
            }

            $teamList = array_values($teams);
            sort($teamList, SORT_NATURAL | SORT_FLAG_CASE);
            $yearLabels = array_values($years);
            usort(
                $yearLabels,
                static fn(array $left, array $right): int => $left['start'] <=> $right['start'],
            );

            $peopleRows[] = [
                'person' => $person,
                'teams' => $teamList,
                'years' => $yearLabels,
            ];
        }

        return $peopleRows;
    }

    /**
     * @param array<int,array{person:\App\Model\Entity\Person,teams:array<int,string>,years:array<int,array{id:int,label:string,start:int,sportKey:string,teamId:int}>}> $peopleRows
     * @param string|null $sportKey The selected sport key.
     * @param int|null $teamId The selected team ID.
     * @return array<int,array<int,string>>
     */
    private function formatPeopleRowsForDataTables(
        array $peopleRows,
        ?string $sportKey,
        ?int $teamId,
    ): array {
        $data = [];
        foreach ($peopleRows as $row) {
            $person = $row['person'];
            $teams = $row['teams'];
            $years = $row['years'];

            $first = (string)($person->first ?? '');
            $last = (string)($person->last ?? '');
            $full = (string)($person->full ?? '');
            $display = (string)($person->display ?? '');

            $name = $full !== '' ? $full : trim($first . ' ' . $last);
            if ($name === '') {
                $name = $display !== '' ? $display : 'Unknown';
            }

            $personUrl = Router::url([
                'controller' => 'People',
                'action' => 'view',
                $person->id,
                '?' => $this->publicScopeQuery($sportKey, $teamId),
            ]);
            $nameHtml = '<a href="' . $this->escape($personUrl) . '" class="fw-semibold text-decoration-none">'
                . $this->escape($name) . '</a>';
            if ($display !== '' && $display !== $name) {
                $nameHtml .= '<div class="people-display-name">' . $this->escape($display) . '</div>';
            }

            $teamsHtml = $teams !== []
                ? $this->escape(implode(', ', $teams))
                : '<span class="text-muted">&mdash;</span>';
            $yearsHtml = '<span class="text-muted">&mdash;</span>';
            if ($years !== []) {
                $yearLinks = [];
                foreach ($years as $year) {
                    $seasonUrl = Router::url([
                        'controller' => 'Seasons',
                        'action' => 'view',
                        $year['id'],
                        '?' => [
                            'sport' => $year['sportKey'],
                            'team' => (string)$year['teamId'],
                        ],
                    ]);
                    $yearLinks[] = '<a href="' . $this->escape($seasonUrl) . '">'
                        . $this->escape($year['label']) . '</a>';
                }
                $yearsHtml = implode(', ', $yearLinks);
            }

            $data[] = [$nameHtml, $teamsHtml, $yearsHtml];
        }

        return $data;
    }

    /**
     * @param array<int,\App\Model\Entity\TeamSeasonRosters> $rosterEntries
     * @return array<int,array{sportId:int,sport:object,seasons:array<int,array{teamSeason:object,rosterId:int,label:string,startYear:int}>,activeSeasonId:int|null}>
     */
    private function buildGameLogGroups(array $rosterEntries): array
    {
        $groups = [];
        foreach ($rosterEntries as $roster) {
            $teamSeason = $roster->team_season ?? null;
            $team = $teamSeason?->team;
            if ($teamSeason === null || $team === null) {
                continue;
            }

            $this->teamSportContextService->attachSportContextToTeam($team);
            $sport = $team->sport ?? null;
            $sportId = (int)($this->teamSportContextService->resolveSportIdFromTeam($team) ?? 0);
            if ($sport === null || $sportId <= 0 || !$this->statsService->hasSportSupport($sportId)) {
                continue;
            }

            $teamSeasonId = (int)($teamSeason->id ?? 0);
            if ($teamSeasonId <= 0) {
                continue;
            }

            $season = $teamSeason->season ?? null;
            $label = $this->formatSeasonLabel($season);
            if ($label === '') {
                $label = 'Season';
            }

            $groups[$sportId] ??= [
                'sportId' => $sportId,
                'sport' => $sport,
                'seasons' => [],
                'activeSeasonId' => null,
            ];
            $groups[$sportId]['seasons'][$teamSeasonId] = [
                'teamSeason' => $teamSeason,
                'rosterId' => (int)$roster->id,
                'label' => $label,
                'startYear' => (int)($season->start ?? 0),
            ];
        }

        foreach ($groups as $sportId => $group) {
            $seasons = array_values($group['seasons']);
            usort(
                $seasons,
                static fn(array $left, array $right): int => $right['startYear'] <=> $left['startYear'],
            );
            $groups[$sportId]['seasons'] = $seasons;
            $groups[$sportId]['activeSeasonId'] = $seasons[0]['teamSeason']->id ?? null;
        }

        return array_values($groups);
    }

    /**
     * @param string|null $sportKey The selected sport key.
     * @param int|null $teamId The selected team ID.
     * @return array{sport:string,team:string}
     */
    private function publicScopeQuery(?string $sportKey, ?int $teamId): array
    {
        return [
            'sport' => $sportKey ?? 'all',
            'team' => $teamId === null ? 'all' : (string)$teamId,
        ];
    }

    /**
     * Format the display label for a season entity.
     *
     * @param object|null $season The season to format.
     * @return string
     */
    private function formatSeasonLabel(?object $season): string
    {
        $seasonStart = (string)($season->start ?? '');
        $seasonEnd = (string)($season->end ?? '');
        if ($seasonStart === '' && $seasonEnd === '') {
            return '';
        }

        $suffix = $seasonEnd !== '' ? substr($seasonEnd, -2) : '';

        return trim($seasonStart . '-' . $suffix, '-');
    }

    /**
     * Escape a string for safe HTML output.
     *
     * @param string $value The value to escape.
     * @return string
     */
    private function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }
}
