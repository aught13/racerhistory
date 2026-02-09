<?php
declare(strict_types=1);

namespace App\Controller;

use App\Service\ImageProcessor;
use App\Service\PersonService;
use Cake\Event\EventInterface;
use Cake\Http\Exception\NotFoundException;
use Cake\Routing\Router;

/**
 * Public People Controller
 *
 * Displays persons (players/coaches/staff) with related images and blog posts.
 *
 * @property \Authorization\Controller\Component\AuthorizationComponent $Authorization
 */
class PeopleController extends AppController
{
    private PersonService $personService;
    private ImageProcessor $imageProcessor;

    /**
     * Initialize controller.
     */
    public function initialize(): void
    {
        parent::initialize();
        $this->loadComponent('Authorization.Authorization');
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

        $searchBuilder = $this->request->getData(
            'searchBuilder',
            $this->request->getQuery('searchBuilder'),
        );

        $total = $table->find()->count();

        $query = $table->find()
            ->select(['id', 'first', 'last', 'full', 'display'])
            ->orderByAsc('Persons.last')
            ->orderByAsc('Persons.first');

        $needsRosterJoin = false;
        if (!empty($searchBuilder['criteria'])) {
            $needsRosterJoin = $this->applyPeopleSearchBuilderCriteria(
                $query,
                $searchBuilder['criteria'],
                $searchBuilder['logic'] ?? 'AND',
            );
        }

        if ($search !== '' && empty($searchBuilder['criteria'])) {
            $query->where([
                'OR' => [
                    'Persons.first LIKE' => '%' . $search . '%',
                    'Persons.last LIKE' => '%' . $search . '%',
                    'Persons.full LIKE' => '%' . $search . '%',
                    'Persons.display LIKE' => '%' . $search . '%',
                ],
            ]);
        }

        if ($needsRosterJoin) {
            $query->distinct(['Persons.id']);
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
                $teamName = $roster->team_season->team->team_name ?? null;
                if ($teamName) {
                    $teams[$teamName] = true;
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

            $nameHtml = '<div class="fw-semibold">' . $this->escape($name) . '</div>';
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

            $actionUrl = Router::url([
                'controller' => 'People',
                'action' => 'view',
                $person->id,
            ]);
            $actionHtml = '<a href="' . $this->escape($actionUrl) . '"'
                . ' class="btn btn-sm btn-outline-primary">'
                . '<i class="bi bi-eye"></i> View Profile</a>';

            $data[] = [$nameHtml, $teamsHtml, $yearsHtml, $actionHtml];
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
     * @param \Cake\ORM\Query $query
     * @param array $criteria
     * @param string $logic
     * @return bool True when roster joins are required.
     */
    private function applyPeopleSearchBuilderCriteria(
        \Cake\ORM\Query $query,
        array $criteria,
        string $logic = 'AND',
    ): bool {
        $conditions = [];
        $needsRosterJoin = false;

        foreach ($criteria as $criterion) {
            if (isset($criterion['criteria'])) {
                $subQuery = $this->fetchTable('Persons')->find();
                $subJoin = $this->applyPeopleSearchBuilderCriteria(
                    $subQuery,
                    $criterion['criteria'],
                    $criterion['logic'] ?? 'AND',
                );
                $needsRosterJoin = $needsRosterJoin || $subJoin;
                $subConditions = $subQuery->clause('where');
                if ($subConditions) {
                    $conditions[] = $subConditions;
                }
                continue;
            }

            $origData = $criterion['origData'] ?? $criterion['data'] ?? null;
            $condition = $criterion['condition'] ?? '=';
            $value1 = $criterion['value1'] ?? $criterion['value'] ?? '';
            $value2 = $criterion['value2'] ?? '';

            $field = match ($origData) {
                '0', 'name' => 'Persons.full',
                '1', 'teams' => 'Teams.team_name',
                '2', 'years' => 'Seasons.start',
                default => null,
            };

            if (!$field) {
                continue;
            }

            if ($field === 'Teams.team_name' || $field === 'Seasons.start') {
                $needsRosterJoin = true;
            }

            if ($field === 'Persons.full') {
                $conditions[] = $this->buildSearchCondition(
                    ['Persons.first', 'Persons.last', 'Persons.full', 'Persons.display'],
                    $condition,
                    $value1,
                    $value2,
                );
                continue;
            }

            if ($field === 'Seasons.start') {
                $conditions[] = $this->buildSearchCondition(
                    ['Seasons.start', 'Seasons.end'],
                    $condition,
                    $value1,
                    $value2,
                );
                continue;
            }

            $conditions[] = $this->buildSearchCondition(
                [$field],
                $condition,
                $value1,
                $value2,
            );
        }

        if ($needsRosterJoin) {
            $query->leftJoinWith('TeamSeasonRosters.TeamSeasons.Teams')
                ->leftJoinWith('TeamSeasonRosters.TeamSeasons.Seasons');
        }

        if ($conditions) {
            $query->where([$logic => $conditions]);
        }

        return $needsRosterJoin;
    }

    /**
     * @param array<int,string> $fields
     * @param string $condition
     * @param mixed $value1
     * @param mixed $value2
     * @return array
     */
    private function buildSearchCondition(
        array $fields,
        string $condition,
        mixed $value1,
        mixed $value2,
    ): array {
        $value1 = (string)$value1;
        $value2 = (string)$value2;

        $buildFieldCondition = function (string $field) use (
            $condition,
            $value1,
            $value2,
        ): array {
            return match ($condition) {
                '=' => [$field => $value1],
                '!=' => [$field . ' !=' => $value1],
                'contains' => [$field . ' LIKE' => '%' . $value1 . '%'],
                '!contains' => [$field . ' NOT LIKE' => '%' . $value1 . '%'],
                'starts' => [$field . ' LIKE' => $value1 . '%'],
                '!starts' => [$field . ' NOT LIKE' => $value1 . '%'],
                'ends' => [$field . ' LIKE' => '%' . $value1],
                '!ends' => [$field . ' NOT LIKE' => '%' . $value1],
                '>' => [$field . ' >' => $value1],
                '<' => [$field . ' <' => $value1],
                '>=' => [$field . ' >=' => $value1],
                '<=' => [$field . ' <=' => $value1],
                'between' => [$field . ' >=' => $value1, $field . ' <=' => $value2],
                '!between' => ['OR' => [$field . ' <' => $value1, $field . ' >' => $value2]],
                'null' => [$field . ' IS' => null],
                '!null' => [$field . ' IS NOT' => null],
                default => [$field . ' LIKE' => '%' . $value1 . '%'],
            };
        };

        $clauses = [];
        foreach ($fields as $field) {
            $clauses[] = $buildFieldCondition($field);
        }

        if (count($clauses) === 1) {
            return $clauses[0];
        }

        return ['OR' => $clauses];
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

        // Get game stats if available
        $gameStats = $this->getGameStatsForPerson($id);

        $this->set(compact('person', 'images', 'blogPosts', 'rosterEntries', 'gameStats'));
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
            ->contain(['BlogTags', 'HeroImages'])
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
            ->contain(['TeamSeasons' => ['Teams', 'Seasons']])
            ->where(['TeamSeasonRosters.person_id' => $personId])
            ->orderByDesc('Seasons.start')
            ->all()
            ->toArray();

        return $entries;
    }

    /**
     * Get game stats for a person.
     *
     * @param int $personId Person ID
     * @return array<int,\App\Model\Entity\StatBasketGamePerson>
     */
    private function getGameStatsForPerson(int $personId): array
    {
        try {
            $table = $this->fetchTable('StatBasketGamePersons');
            $stats = $table->find()
                ->contain(['Games' => ['Opponents', 'TeamSeasons' => ['Seasons']]])
                ->where(['StatBasketGamePersons.person_id' => $personId])
                ->orderByDesc('Games.game_date')
                ->limit(20)
                ->all()
                ->toArray();

            return $stats;
        } catch (\Exception $e) {
            // Table might not exist if basketball stats not enabled
            return [];
        }
    }
}
