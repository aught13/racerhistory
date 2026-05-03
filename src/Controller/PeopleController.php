<?php
declare(strict_types=1);

namespace App\Controller;

use App\Service\ImageProcessor;
use App\Service\PersonService;
use App\Service\StatsService;
use Cake\Event\EventInterface;
use Cake\Http\Exception\NotFoundException;
use Cake\ORM\Query\SelectQuery;
use Cake\Routing\Router;

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
    private PersonService $personService;
    private ImageProcessor $imageProcessor;
    protected StatsService $Stats;

    /**
     * Initialize controller.
     */
    public function initialize(): void
    {
        parent::initialize();
        $this->loadComponent('Authorization.Authorization');
        $this->Stats = new StatsService();
        $this->personService = new PersonService();
        $this->imageProcessor = new ImageProcessor();
    }

    /**
     * Skip authorization for public actions.
     */
    public function beforeFilter(EventInterface $event): void
    {
        parent::beforeFilter($event);
        $this->Authorization->skipAuthorization();
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

        $table = $this->fetchTable('Persons');
        $peopleCount = $table->find()->count();

        $this->set([
            'people' => [],
            'peopleRows' => [],
            'peopleCount' => $peopleCount,
        ]);
    }

    /**
     * Render People index data for DataTables (server-side).
     */
    private function renderPeopleIndexJson(): void
    {
        $this->autoRender = false;
        $table = $this->fetchTable('Persons');
        $draw = (int)$this->request->getQuery('draw');
        $start = max(0, (int)$this->request->getQuery('start'));
        $length = (int)$this->request->getQuery('length');
        if ($length < 1) {
            $length = 50;
        }
        $length = min($length, 200);

        $search = (string)($this->request->getQuery('search')['value'] ?? '');
        $search = trim($search);

        $total = $table->find()->count();

        $query = $table->find()
            ->select(['id', 'first', 'last', 'full', 'display']);

        $this->applyDataTablesOrderToPeopleQuery($query);

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

        $rowsByPerson = $this->buildPeopleRows($people);
        $data = $this->formatPeopleRowsForDataTables($rowsByPerson);

        $payload = [
            'draw' => $draw,
            'recordsTotal' => $total,
            'recordsFiltered' => $filtered,
            'data' => $data,
        ];

        $this->response = $this->response
            ->withType('application/json')
            ->withStringBody((string)json_encode($payload));
    }

    /**
     * Apply DataTables order settings to the People index query.
     *
     * Currently we only support server-side ordering by the Name column.
     */
    private function applyDataTablesOrderToPeopleQuery(SelectQuery $query): void
    {
        $order = $this->request->getQuery('order');
        $direction = 'asc';

        if (is_array($order) && !empty($order)) {
            $firstOrder = reset($order);
            if (is_array($firstOrder)) {
                $requestedDirection = strtolower((string)($firstOrder['dir'] ?? 'asc'));
                if (in_array($requestedDirection, ['asc', 'desc'], true)) {
                    $direction = $requestedDirection;
                }
            }
        }

        if ($direction === 'desc') {
            $query
                ->orderByDesc('Persons.last')
                ->orderByDesc('Persons.first');

            return;
        }

        $query
            ->orderByAsc('Persons.last')
            ->orderByAsc('Persons.first');
    }

    /**
     * @param array<int,\App\Model\Entity\Person> $people
     * @return array<int,array{person:\App\Model\Entity\Person,teams:array<int,string>,years:array<int,array{id:int,label:string,start:int}>}>
     */
    private function buildPeopleRows(array $people): array
    {
        if (empty($people)) {
            return [];
        }

        $personIds = [];
        foreach ($people as $person) {
            if (!empty($person->id)) {
                $personIds[] = (int)$person->id;
            }
        }

        $rosterMap = [];
        if (!empty($personIds)) {
            $rosterTable = $this->fetchTable('TeamSeasonRosters');
            $rosters = $rosterTable->find()
                ->contain(['TeamSeasons' => ['Teams', 'Seasons']])
                ->where(['TeamSeasonRosters.person_id IN' => $personIds])
                ->all();

            foreach ($rosters as $roster) {
                $pid = (int)($roster->person_id ?? 0);
                if ($pid > 0) {
                    $rosterMap[$pid][] = $roster;
                }
            }
        }

        $peopleRows = [];
        foreach ($people as $person) {
            $teams = [];
            $years = [];
            $rosters = $rosterMap[(int)$person->id] ?? [];

            foreach ($rosters as $roster) {
                $teamAbbr = $roster->team_season->team->abbr ?? null;
                if ($teamAbbr) {
                    $teams[$teamAbbr] = true;
                }

                $season = $roster->team_season->season ?? null;
                if ($season && ($season->start !== null || $season->end !== null)) {
                    $start = (string)($season->start ?? '');
                    $end = (string)($season->end ?? '');
                    $label = trim($start . '-' . ($end !== '' ? substr($end, -2) : ''), '-');
                    $seasonId = (int)($season->id ?? 0);
                    if ($label !== '' && $seasonId > 0) {
                        $years[$seasonId] = [
                            'id' => $seasonId,
                            'label' => $label,
                            'start' => (int)($season->start ?? 0),
                        ];
                    }
                }
            }

            $teamList = array_keys($teams);
            sort($teamList, SORT_NATURAL | SORT_FLAG_CASE);

            $yearLabels = [];
            if (!empty($years)) {
                uasort(
                    $years,
                    static fn(array $left, array $right): int => $left['start'] <=> $right['start'],
                );
                $yearLabels = array_values($years);
            }

            $peopleRows[] = [
                'person' => $person,
                'teams' => $teamList,
                'years' => $yearLabels,
            ];
        }

        return $peopleRows;
    }

    /**
     * @param array<int,array{person:\App\Model\Entity\Person,teams:array<int,string>,years:array<int,array{id:int,label:string,start:int}>}> $peopleRows
     * @return array<int,array<int,string>>
     */
    private function formatPeopleRowsForDataTables(array $peopleRows): array
    {
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
            ]);
            $nameHtml = '<a href="' . $this->escape($personUrl) . '" class="fw-semibold text-decoration-none">'
                . $this->escape($name) . '</a>';
            if ($display !== '' && $display !== $name) {
                $nameHtml .= '<div class="people-display-name">' . $this->escape($display) . '</div>';
            }

            $teamsHtml = !empty($teams)
                ? $this->escape(implode(', ', $teams))
                : '<span class="text-muted">&mdash;</span>';
            $yearsHtml = '<span class="text-muted">&mdash;</span>';
            if (!empty($years)) {
                $yearLinks = [];
                foreach ($years as $year) {
                    $seasonUrl = Router::url([
                        'controller' => 'Seasons',
                        'action' => 'view',
                        $year['id'],
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
     * @param string $value
     * @return string
     */
    private function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }

    /**
     * View a single person with related data.
     *
     * @param int $id Person ID
     */
    public function view(int $id): void
    {
        $person = $this->personService->getPersonById($id);
        if (!$person) {
            throw new NotFoundException('Person not found');
        }

        // Get related images via tagging
        $images = $this->imageProcessor->getImagesForPerson($id, 20);

        // Get related blog posts via tagging
        $blogPosts = $this->getBlogPostsByTag("person-{$id}");

        // Get roster entries (team seasons this person was on)
        $rosterEntries = $this->getRosterEntriesForPerson($id);

        // Organize roster entries by sport and calculate career stats
        $rostersBySport = [];
        $careerStatsBySport = [];

        foreach ($rosterEntries as $roster) {
            $teamSeason = $roster->team_season ?? null;
            $sport = $teamSeason?->team->sport ?? null;
            if (!$sport) {
                continue;
            }

            $sportId = $sport->id;
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

            if ($this->Stats->hasSportSupport($sportId)) {
                if (!isset($careerStatsBySport[$sportId])) {
                    $careerStatsBySport[$sportId] = [
                        'sport' => $sport,
                        'totals' => $this->Stats->initializeStats($sportId, 'player'),
                        'seasons' => [],
                        'minYear' => null,
                        'maxYear' => null,
                    ];
                }

                $seasonStats = $this->Stats->getPersonSeasonStats(
                    $sportId,
                    (int)$roster->id,
                );
                if ($seasonStats) {
                    $careerStatsBySport[$sportId]['seasons'][] = [
                        'teamSeason' => $teamSeason,
                        'stats' => $seasonStats,
                    ];

                    $startYear = $teamSeason?->season->start ?? null;
                    $endYear = $teamSeason?->season->end ?? null;
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

                    $this->Stats->addSeasonStats(
                        $sportId,
                        $careerStatsBySport[$sportId]['totals'],
                        $seasonStats,
                    );
                }
            }
        }

        $gameLogGroups = $this->buildGameLogGroups($rosterEntries);
        $gameStats = [];

        $this->set(
            compact(
                'person',
                'images',
                'blogPosts',
                'rosterEntries',
                'rostersBySport',
                'careerStatsBySport',
                'gameLogGroups',
                'gameStats',
            ),
        );
    }

    /**
     * Render the game log for a person and team season.
     *
     * @param int $personId Person ID
     * @param int $teamSeasonId Team season ID
     */
    public function gameLog(int $personId, int $teamSeasonId): void
    {
        $person = $this->personService->getPersonById($personId);
        if (!$person) {
            throw new NotFoundException('Person not found');
        }

        $rosterTable = $this->fetchTable('TeamSeasonRosters');
        $roster = $rosterTable->find()
            ->contain(['TeamSeasons' => ['Teams' => ['Sports'], 'Seasons']])
            ->where([
                'TeamSeasonRosters.person_id' => $personId,
                'TeamSeasonRosters.team_season_id' => $teamSeasonId,
            ])
            ->first();

        if (!$roster) {
            throw new NotFoundException('Roster entry not found');
        }

        $teamSeason = $roster->team_season ?? null;
        $sport = $teamSeason?->team->sport ?? null;
        $gameLogRows = [];
        $gameLogElement = null;
        if ($sport && $this->Stats->hasSportSupport((int)$sport->id)) {
            $gameLogElement = $this->Stats->getPersonGameLogElement((int)$sport->id);
            $gameLogRows = $this->Stats->getPersonGameStats(
                (int)$sport->id,
                (int)$roster->id,
            );
        }

        $frameId = 'person-game-log-frame-' . $personId . '-' . $teamSeasonId;

        $this->set(
            compact(
                'person',
                'teamSeason',
                'sport',
                'gameLogRows',
                'gameLogElement',
                'frameId',
            ),
        );

        $this->viewBuilder()->disableAutoLayout();
        $this->viewBuilder()->setTemplate('game_log');
    }

    /**
     * Get blog posts by tag slug.
     *
     * @param string $tagSlug Tag slug
     * @return array<int,\App\Model\Entity\BlogPost>
     */
    private function getBlogPostsByTag(string $tagSlug): array
    {
        $table = $this->fetchTable('BlogPosts');
        $posts = $table->find()
            ->contain(['BlogTags'])
            ->matching('BlogTags', function ($q) use ($tagSlug) {
                return $q->where(['BlogTags.slug' => $tagSlug]);
            })
            ->where(['BlogPosts.is_published' => true])
            ->orderByDesc('BlogPosts.published_at')
            ->limit(10)
            ->all()
            ->toArray();

        return $posts;
    }

    /**
     * Get roster entries for a person.
     *
     * @param int $personId Person ID
     * @return array<int,\App\Model\Entity\TeamSeasonRosters>
     */
    private function getRosterEntriesForPerson(int $personId): array
    {
        $table = $this->fetchTable('TeamSeasonRosters');
        $entries = $table->find()
            ->contain(['TeamSeasons' => ['Teams' => ['Sports'], 'Seasons']])
            ->where(['TeamSeasonRosters.person_id' => $personId])
            ->orderByDesc('Seasons.start')
            ->all()
            ->toArray();

        return $entries;
    }

    /**
     * Build game log groups for supported sports.
     *
     * @param array<int,\App\Model\Entity\TeamSeasonRosters> $rosterEntries
     * @return array<int,array{sportId:int,sport:\App\Model\Entity\Sport,seasons:array<int,array{teamSeason:\App\Model\Entity\TeamSeason,rosterId:int,label:string,startYear:int}>,activeSeasonId:int|null}>
     */
    private function buildGameLogGroups(array $rosterEntries): array
    {
        $groups = [];

        foreach ($rosterEntries as $roster) {
            $teamSeason = $roster->team_season ?? null;
            $sport = $teamSeason?->team->sport ?? null;
            if (!$sport) {
                continue;
            }

            $sportId = (int)$sport->id;
            if (!$this->Stats->hasSportSupport($sportId)) {
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

            if (!isset($groups[$sportId])) {
                $groups[$sportId] = [
                    'sportId' => $sportId,
                    'sport' => $sport,
                    'seasons' => [],
                    'activeSeasonId' => null,
                ];
            }

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
                static function (array $left, array $right): int {
                    return $right['startYear'] <=> $left['startYear'];
                },
            );
            $groups[$sportId]['seasons'] = $seasons;
            $groups[$sportId]['activeSeasonId'] = $seasons[0]['teamSeason']->id ?? null;
        }

        return array_values($groups);
    }

    /**
     * @param object|null $season
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
}
