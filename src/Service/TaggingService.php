<?php
declare(strict_types=1);

namespace App\Service;

use Cake\Http\ServerRequest;
use Cake\ORM\Association\BelongsToMany;
use Cake\ORM\Table;
use Cake\ORM\TableRegistry;
use Cake\Utility\Text;

/**
 * TaggingService centralizes the common tagging workflow for different entities.
 *
 * It can drive both image and blog post tagging (and any future taggable context)
 * by configuring the subject table, the tag table, and the association that links them.
 */
class TaggingService
{
    private string $subjectTableName;
    private string $tagsTableName;
    private string $associationName;
    private bool $pruneOrphans;
    private bool $allowContextTags;

    private PersonService $personService;
    private TeamSeasonService $teamSeasonService;
    private TeamSeasonRosterService $rosterService;

    private ?Table $subjectTable = null;
    private ?Table $tagsTable = null;

    /**
     * Initialize a tagging service for the given tables and association.
     *
     * @param string $subjectTableName Table alias backing the taggable subject.
     * @param string $tagsTableName Table alias backing the tag storage.
     * @param string $associationName The association name that links the two tables.
     * @param array $options Behavior toggles for pruning and context tags.
     * @param \App\Service\PersonService|null $personService Optional person display service.
     * @param \App\Service\TeamSeasonService|null $teamSeasonService Optional team season service.
     * @param \App\Service\TeamSeasonRosterService|null $rosterService Optional roster service.
     */
    public function __construct(
        string $subjectTableName,
        string $tagsTableName,
        string $associationName,
        array $options = [],
        ?PersonService $personService = null,
        ?TeamSeasonService $teamSeasonService = null,
        ?TeamSeasonRosterService $rosterService = null,
    ) {
        $this->subjectTableName = $subjectTableName;
        $this->tagsTableName = $tagsTableName;
        $this->associationName = $associationName;
        $this->pruneOrphans = (bool)($options['pruneOrphans'] ?? false);
        $this->allowContextTags = (bool)($options['allowContextTags'] ?? false);
        $this->personService = $personService ?? new PersonService();
        $this->teamSeasonService = $teamSeasonService ?? new TeamSeasonService();
        $this->rosterService = $rosterService ?? new TeamSeasonRosterService();
    }

    /**
     * Create a tagging service configured for the Images table.
     *
     * @param \App\Service\PersonService|null $personService Optional person service for dependency
     *     injection.
     * @param \App\Service\TeamSeasonService|null $teamSeasonService Optional team season service for
     *     dependency injection.
     * @param \App\Service\TeamSeasonRosterService|null $rosterService Optional roster service for
     *     dependency injection.
     * @return self
     */
    public static function forImages(
        ?PersonService $personService = null,
        ?TeamSeasonService $teamSeasonService = null,
        ?TeamSeasonRosterService $rosterService = null,
    ): self {
        return new self(
            'Images',
            'ImageTags',
            'ImageTags',
            ['pruneOrphans' => true, 'allowContextTags' => true],
            $personService,
            $teamSeasonService,
            $rosterService,
        );
    }

    /**
     * Create a tagging service configured for the BlogPosts table.
     *
     * @param \App\Service\PersonService|null $personService Optional person service for dependency
     *     injection.
     * @param \App\Service\TeamSeasonService|null $teamSeasonService Optional team season service for
     *     dependency injection.
     * @param \App\Service\TeamSeasonRosterService|null $rosterService Optional roster service for
     *     dependency injection.
     * @return self
     */
    public static function forBlogPosts(
        ?PersonService $personService = null,
        ?TeamSeasonService $teamSeasonService = null,
        ?TeamSeasonRosterService $rosterService = null,
    ): self {
        return new self(
            'BlogPosts',
            'BlogTags',
            'BlogTags',
            [],
            $personService,
            $teamSeasonService,
            $rosterService,
        );
    }

    /**
     * Attach a list of tags to the given subject.
     *
     * @param int $subjectId Subject entity primary key.
     * @param array $tags Normalized tags to attach.
     * @param array $options Additional flags such as skipExistingCheck.
     * @return array Slugs of tags that were attached.
     */
    public function attachTags(int $subjectId, array $tags, array $options = []): array
    {
        $table = $this->subjectTable();
        $association = $this->associationName;
        $entity = $table->get($subjectId, contain: [$association]);
        $existingTagIds = [];
        foreach ($entity->{$association} ?? [] as $tag) {
            $tagId = (int)($tag->get('id') ?? 0);
            if ($tagId > 0) {
                $existingTagIds[] = $tagId;
            }
        }

        $tagsTable = $this->tagsTable();
        $applied = [];
        $link = [];
        $skipExistingCheck = (bool)($options['skipExistingCheck'] ?? false);

        foreach ($this->normalizeTags($tags) as $tag) {
            $slug = $tag['slug'];
            $name = $tag['name'];

            $existing = $tagsTable->find()->where(['slug' => $slug])->first();
            if (!$existing) {
                $existing = $tagsTable->newEntity(['name' => $name, 'slug' => $slug]);
                $tagsTable->save($existing);
            } else {
                $existingName = (string)($existing->get('name') ?? '');
                if ($name !== '' && $this->shouldUpdateName($existingName)) {
                    $existing->set('name', $name);
                    $tagsTable->save($existing);
                }
            }

            $existingId = (int)($existing->get('id') ?? 0);
            if ($existingId > 0 && ($skipExistingCheck || !in_array($existingId, $existingTagIds, true))) {
                $link[] = $existing;
            }
            $applied[] = (string)($existing->get('slug') ?? '');
        }

        if ($link) {
            $table->{$association}->link($entity, $link);
        }

        return array_values(array_unique($applied));
    }

    /**
     * Replace all existing tags for the subject with a fresh list.
     *
     * @param int $subjectId Subject primary key.
     * @param array $tags Tags to persist.
     * @return array Applied tag slugs.
     */
    public function replaceTags(int $subjectId, array $tags): array
    {
        $this->deleteExistingAttachments($subjectId);
        $applied = $this->attachTags($subjectId, $tags, ['skipExistingCheck' => true]);
        if ($this->pruneOrphans) {
            $this->pruneOrphanedTags();
        }

        return $applied;
    }

    /**
     * Build tags from incoming data and persist them on the subject.
     *
     * @param int $subjectId Subject primary key.
     * @param array $data Incoming request data.
     * @return array Applied tag slugs.
     */
    public function applyFromData(int $subjectId, array $data): array
    {
        $tags = $this->buildTagsFromData($data);

        return $tags ? $this->replaceTags($subjectId, $tags) : [];
    }

    /**
     * Parse tag data from an HTTP request and deduplicate entries.
     *
     * @param \Cake\Http\ServerRequest $request HTTP request containing tags/context payload.
     * @return array Normalized tag list.
     */
    public function parseTagsFromRequest(ServerRequest $request): array
    {
        $tags = [];

        $raw = $request->getData('tags') ?? $request->getQuery('tags') ?? [];
        if (is_string($raw)) {
            $raw = array_map('trim', explode(',', $raw));
        }
        if (is_array($raw)) {
            $tags = array_values(array_filter(array_map(fn($t) => trim((string)$t), $raw), fn($t) => $t !== ''));
        }

        $contextJson = $request->getData('context') ?? $request->getQuery('context');
        if ($this->allowContextTags && $contextJson && is_string($contextJson)) {
            $context = json_decode($contextJson, true);
            if (is_array($context) && isset($context['type'], $context['id'])) {
                $type = strtolower((string)$context['type']);
                $id = (int)$context['id'];

                if ($type === 'person' && $id > 0) {
                    $tags[] = [
                        'slug' => "person-{$id}",
                        'name' => $this->personService->getDisplayLabel($id),
                    ];
                } elseif ($type === 'teamseason' && $id > 0) {
                    $tags[] = [
                        'slug' => "teamseason-{$id}",
                        'name' => $this->teamSeasonService->getSportDisplayLabel($id),
                    ];
                } elseif (in_array($type, ['blog', 'blogpost', 'post'], true) && $id > 0) {
                    $label = (new BlogPostService())->getDisplayLabel($id);
                    $tags[] = [
                        'slug' => "blogpost-{$id}",
                        'name' => $label,
                    ];
                } elseif ($type === 'game' && $id > 0) {
                    $tags[] = [
                        'slug' => "game-{$id}",
                        'name' => $this->getGameTagDisplayLabel($id),
                    ];
                }
            }
        }

        $unique = [];
        foreach ($tags as $tag) {
            if (is_array($tag) && isset($tag['slug'])) {
                $unique[(string)$tag['slug']] = $tag;
            } else {
                $slug = (string)$tag;
                $unique[$slug] = $slug;
            }
        }

        return array_values($unique);
    }

    /**
     * Remove tags that are no longer linked to any subject.
     *
     * @return void
     */
    public function pruneOrphanedTags(): void
    {
        if (!$this->pruneOrphans) {
            return;
        }

        $tagsTable = $this->tagsTable();
        if (!$tagsTable->associations()->has($this->subjectTableName)) {
            return;
        }

        $related = $this->subjectTableName;
        $query = $tagsTable->find()
            ->select(['id'])
            ->leftJoinWith($related)
            ->where(["{$related}.id IS" => null]);

        foreach ($query as $tag) {
            $tagsTable->delete($tag);
        }
    }

    /**
     * Normalize submitted form data into raw tag payloads.
     *
     * @param array $data Input array containing select values and raw tag strings.
     * @return array Structured tag data ready for persistence.
     */
    public function buildTagsFromData(array $data): array
    {
        $tagsToApply = [];
        $displayNames = [];
        $map = $this->entityTagMap();
        $hasRoster = !empty($data['roster_select']) && (int)$data['roster_select'] > 0;

        // Multi-person support: allow person_select to be a scalar, CSV string, or array of ids.
        $personIds = [];
        if (!$hasRoster && array_key_exists('person_select', $data)) {
            $personIds = $this->parseIdList($data['person_select']);
        }

        // Handle persons first (and remove from the generic map loop).
        if ($personIds) {
            foreach ($personIds as $pid) {
                $slug = 'person-' . $pid;
                $display = $this->personService->getDisplayLabel($pid);
                $tagsToApply[$slug] = ['slug' => $slug, 'name' => (string)$display];
                $displayNames[] = (string)$display;
            }
        }

        // Ensure generic loop doesn't treat person_select as scalar.
        if (isset($map['person_select'])) {
            unset($map['person_select']);
        }

        foreach ($map as $field => $meta) {
            if ($hasRoster && $field === 'teamseason_select') {
                continue;
            }
            $skipWhenRoster = ['game_select', 'site_select', 'opponent_select', 'team_select', 'sport_select'];
            if ($hasRoster && in_array($field, $skipWhenRoster, true)) {
                continue;
            }

            if (!empty($data[$field])) {
                $id = (int)$data[$field];
                if ($id > 0) {
                    $display = '';
                    if (isset($meta['service'])) {
                        if ($meta['service'] === 'person') {
                            $display = $this->personService->getDisplayLabel($id);
                        } else {
                            $display = $this->teamSeasonService->getSportDisplayLabel($id);
                        }
                    } else {
                        $table = TableRegistry::getTableLocator()->get($meta['table']);
                        $alias = $table->getAlias();
                        $query = $table->find()->select()->where([$alias . '.id' => $id]);
                        if (!empty($meta['contain']) && is_array($meta['contain'])) {
                            $query->contain($meta['contain']);
                        }
                        $row = $query->first();
                        $display = $meta['label']($row);
                    }

                    $slug = $meta['prefix'] . $id;
                    $tagsToApply[$slug] = ['slug' => $slug, 'name' => (string)$display];
                    $displayNames[] = (string)$display;
                }
            }
        }

        if ($hasRoster) {
            $rosterId = (int)$data['roster_select'];
            $rosterData = $this->rosterService->getRosterDisplayData($rosterId);

            $personTag = 'person-' . $rosterData['person_id'];
            if (!isset($tagsToApply[$personTag])) {
                $tagsToApply[$personTag] = [
                    'slug' => $personTag,
                    'name' => $rosterData['person_label'],
                ];
                $displayNames[] = $rosterData['person_label'];
            }

            $rosterSlug = 'team_season_roster-' . $rosterId;
            $tagsToApply[$rosterSlug] = [
                'slug' => $rosterSlug,
                'name' => $rosterData['team_season_label'],
            ];
        }

        $raw = $data['tags'] ?? [];
        if (is_string($raw)) {
            $raw = array_map('trim', explode(',', $raw));
        }
        if (is_array($raw)) {
            foreach ($raw as $value) {
                $value = trim((string)$value);
                if ($value === '') {
                    continue;
                }
                $matched = false;
                foreach ($displayNames as $dn) {
                    if (strcasecmp($dn, $value) === 0) {
                        $matched = true;
                        break;
                    }
                }
                if ($matched) {
                    continue;
                }
                $tagsToApply[$value] = $value;
            }
        }

        $final = [];
        foreach ($tagsToApply as $val) {
            $final[] = is_array($val) ? $val : (string)$val;
        }

        return $final;
    }

    /**
     * Normalize an incoming id list value (scalar, CSV, or array) into unique positive ints.
     *
     * @param mixed $value
     * @return array<int,int>
     */
    private function parseIdList(mixed $value): array
    {
        $raw = [];

        if (is_array($value)) {
            $raw = $value;
        } elseif (is_string($value)) {
            $trimmed = trim($value);
            if ($trimmed === '') {
                $raw = [];
            } elseif (str_contains($trimmed, ',')) {
                $raw = array_map('trim', explode(',', $trimmed));
            } else {
                $raw = [$trimmed];
            }
        } elseif ($value !== null) {
            $raw = [$value];
        }

        $ids = [];
        foreach ($raw as $item) {
            $id = (int)$item;
            if ($id > 0) {
                $ids[$id] = $id;
            }
        }

        return array_values($ids);
    }

    /**
     * Remove prior tag links for the provided subject.
     *
     * @param int $subjectId Subject primary key.
     */
    private function deleteExistingAttachments(int $subjectId): void
    {
        $table = $this->subjectTable();
        if (!$table->associations()->has($this->associationName)) {
            return;
        }
        $association = $table->associations()->get($this->associationName);
        if (!$association instanceof BelongsToMany) {
            return;
        }

        $entity = $table->get($subjectId, contain: [$this->associationName]);
        $linked = $entity->{$this->associationName} ?? [];
        if ($linked) {
            $table->{$this->associationName}->unlink($entity, $linked);
        }

        $junctionTable = $association->junction();
        $foreignKey = $association->getForeignKey();
        if ($foreignKey) {
            $junctionTable->deleteAll([$foreignKey => $subjectId]);
        }
    }

    /**
     * Define the metadata used to turn select fields into tags.
     *
     * @return array Metadata keyed by select element name.
     */
    private function entityTagMap(): array
    {
        return [
            'person_select' => [
                'prefix' => 'person-',
                'service' => 'person',
            ],
            'teamseason_select' => [
                'prefix' => 'teamseason-',
                'service' => 'teamseason',
            ],
            'game_select' => [
                'prefix' => 'game-',
                'table' => 'Games',
                'contain' => ['Opponents'],
                'label' => function ($row): string {
                    $id = (int)($row->id ?? 0);
                    if ($id <= 0) {
                        return 'game';
                    }

                    $opponentName = '';
                    if (!empty($row->opponent) && !empty($row->opponent->opponent_name)) {
                        $opponentName = (string)$row->opponent->opponent_name;
                    }

                    return (new GameService())->formatGameTagLabelFromRow(
                        $row->game_date ?? null,
                        $opponentName,
                        (int)($row->hrn ?? 0),
                        $id,
                    );
                },
            ],
            'site_select' => [
                'prefix' => 'site-',
                'table' => 'Sites',
                'label' => fn($row) => $row->site_name ?? 'site',
            ],
            'opponent_select' => [
                'prefix' => 'opponent-',
                'table' => 'Opponents',
                'label' => fn($row) => $row->opponent_name ?? 'opponent',
            ],
            'team_select' => [
                'prefix' => 'team-',
                'table' => 'Teams',
                'label' => fn($row) => $row->team_name ?? 'team',
            ],
            'sport_select' => [
                'prefix' => 'sport-',
                'table' => 'Sports',
                'label' => fn($row) => $row->sport_name ?? 'sport',
            ],
        ];
    }

    /**
     * Ensure tags contain both slug and human label.
     *
     * @param array $tags Raw tag inputs (strings or arrays).
     * @return array Normalized tags with slug/name pairs.
     */
    private function normalizeTags(array $tags): array
    {
        $normalized = [];
        foreach ($tags as $tag) {
            if (is_array($tag) && isset($tag['slug'])) {
                $slug = (string)$tag['slug'];
                $name = isset($tag['name']) ? (string)$tag['name'] : $slug;
            } else {
                $name = trim((string)$tag);
                if ($name === '') {
                    continue;
                }
                $slug = Text::slug($name) ?: strtolower($name);
            }
            $normalized[] = ['slug' => $slug, 'name' => $name];
        }

        return $normalized;
    }

    /**
     * Determine whether the name should be synced when saving a tag.
     *
     * @param string $existingName
     */
    private function shouldUpdateName(string $existingName): bool
    {
        if ($existingName === '') {
            return true;
        }
        if ($existingName === strtolower($existingName)) {
            return true;
        }
        if (preg_match('/^(?:roster|team ?season|team_season_roster|person)[\s_-]*\d+$/i', $existingName)) {
            return true;
        }

        return false;
    }

    /**
     * Resolve a display label for a game id used by context tagging.
     *
     * @param int $gameId
     */
    private function getGameTagDisplayLabel(int $gameId): string
    {
        return (new GameService())->getGameTagDisplayLabel($gameId);
    }

    /**
     * Lazily resolve the subject table for the current tagging context.
     */
    private function subjectTable(): Table
    {
        if ($this->subjectTable === null) {
            $this->subjectTable = TableRegistry::getTableLocator()->get($this->subjectTableName);
        }

        return $this->subjectTable;
    }

    /**
     * Lazily resolve the tag table for the current tagging context.
     */
    private function tagsTable(): Table
    {
        if ($this->tagsTable === null) {
            $this->tagsTable = TableRegistry::getTableLocator()->get($this->tagsTableName);
        }

        return $this->tagsTable;
    }
}
