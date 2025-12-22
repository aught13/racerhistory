<?php
declare(strict_types=1);

namespace App\Service;

use Cake\Http\ServerRequest;
use Cake\ORM\TableRegistry;

/**
 * ImageTaggingService
 *
 * Small service to build canonical tag slugs from domain entities and
 * apply them to images via the ImageProcessor service.
 * Delegates to entity-specific services for friendly display labels.
 */
class ImageTaggingService
{
    private PersonService $personService;
    private TeamSeasonService $teamSeasonService;
    private TeamSeasonRosterService $rosterService;

    /**
     * Constructor.
     *
     * @param \App\Service\PersonService|null $personService Person service instance
     * @param \App\Service\TeamSeasonService|null $teamSeasonService Team season service instance
     * @param \App\Service\TeamSeasonRosterService|null $rosterService Roster service instance
     */
    public function __construct(
        ?PersonService $personService = null,
        ?TeamSeasonService $teamSeasonService = null,
        ?TeamSeasonRosterService $rosterService = null,
    ) {
        $this->personService = $personService ?? new PersonService();
        $this->teamSeasonService = $teamSeasonService ?? new TeamSeasonService();
        $this->rosterService = $rosterService ?? new TeamSeasonRosterService();
    }

    /**
     * Normalize and attach tags to an image, creating tags on demand.
     * Accepts string slugs or ['slug' => 'teamseason-1', 'name' => 'Display'].
     *
     * @param int $imageId Image id
     * @param array<int|string,string|array> $tags
     * @return array<int,string> Applied slugs
     */
    public function attachTags(int $imageId, array $tags): array
    {
        $tagsTable = TableRegistry::getTableLocator()->get('ImageTags');
        $imagesTable = TableRegistry::getTableLocator()->get('Images');
        /** @var \App\Model\Entity\Image $image */
        $image = $imagesTable->get($imageId, contain: ['ImageTags']);

        $existingTagIds = array_map(fn($t) => $t->id, (array)$image->image_tags);
        $applied = [];
        $tagEntities = [];

        foreach ($this->normalizeTags($tags) as $tag) {
            $slug = $tag['slug'];
            $name = $tag['name'];

            $existing = $tagsTable->find()->where(['slug' => $slug])->first();
            if (!$existing) {
                $existing = $tagsTable->newEntity(['name' => $name, 'slug' => $slug]);
                $tagsTable->save($existing);
            } else {
                // Update generic names with nicer provided name
                $existingName = (string)($existing->name ?? '');
                $shouldUpdate = false;
                if ($existingName === $existing->slug || strcasecmp($existingName, $existing->slug) === 0) {
                    $shouldUpdate = true;
                } elseif (
                    preg_match(
                        '/^(?:roster|team ?season|team_season_roster|person)[\s_-]*\d+$/i',
                        $existingName,
                    )
                ) {
                    $shouldUpdate = true;
                }
                if ($name !== '' && $shouldUpdate) {
                    $existing->name = $name;
                    $tagsTable->save($existing);
                }
            }

            if (!in_array($existing->id, $existingTagIds)) {
                $tagEntities[] = $existing;
            }
            $applied[] = $existing->slug;
        }

        if ($tagEntities) {
            // @phpstan-ignore property.notFound
            $imagesTable->ImageTags->link($image, $tagEntities);
        }

        return array_values(array_unique($applied));
    }

    /**
     * Replace all tags for an image and prune orphaned tag rows.
     *
     * @param int $imageId
     * @param array<int|string,string|array> $tags
     * @return array<int,string> Applied slugs
     */
    public function replaceTags(int $imageId, array $tags): array
    {
        $imagesImageTags = TableRegistry::getTableLocator()->get('ImagesImageTags');
        $imagesImageTags->deleteAll(['image_id' => $imageId]);

        $applied = $this->attachTags($imageId, $tags);
        $this->pruneOrphanedTags();

        return $applied;
    }

    /**
     * Delete tag rows that are no longer linked to any image.
     */
    public function pruneOrphanedTags(): void
    {
        $tagsTable = TableRegistry::getTableLocator()->get('ImageTags');
        $orphaned = $tagsTable->find()
            ->select(['id'])
            ->leftJoinWith('Images')
            ->where(['Images.id IS' => null]);
        foreach ($orphaned as $tag) {
            $tagsTable->delete($tag);
        }
    }

    /**
     * Build tags from request data (tags or context json) used during uploads.
     *
     * @param \Cake\Http\ServerRequest $request
     * @return array<int,string>
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
        if ($contextJson && is_string($contextJson)) {
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
                } elseif ($type === 'game' && $id > 0) {
                    // Game has no dedicated service label; keep slug as fallback name
                    $tags[] = [
                        'slug' => "game-{$id}",
                        'name' => "game-{$id}",
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
                if ($slug !== '') {
                    $unique[$slug] = $slug;
                }
            }
        }

        return array_values($unique);
    }

    /**
     * Build tags from request-like data and attach them to the image.
     * Returns the list of slugs that were applied.
     *
     * @param int $imageId
     * @param array<string,mixed> $data
     * @return array<int,string>
     */
    public function applyFromData(int $imageId, array $data): array
    {
        $tagsToApply = [];

        // Build record-based tags first as ['slug' => ..., 'name' => ...]
        $map = [
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
                'label' => fn($r) => $r->id ?? 'game',
            ],
            'site_select' => [
                'prefix' => 'site-',
                'table' => 'Sites',
                'label' => fn($r) => $r->site_name ?? 'site',
            ],
            'opponent_select' => [
                'prefix' => 'opponent-',
                'table' => 'Opponents',
                'label' => fn($r) => $r->opponent_name ?? 'opponent',
            ],
            'team_select' => [
                'prefix' => 'team-',
                'table' => 'Teams',
                'label' => fn($r) => $r->team_name ?? 'team',
            ],
            'sport_select' => [
                'prefix' => 'sport-',
                'table' => 'Sports',
                'label' => fn($r) => $r->sport_name ?? 'sport',
            ],
        ];

        $displayNames = [];

        // Check if roster is being set
        $hasRoster = !empty($data['roster_select']) && (int)$data['roster_select'] > 0;

        foreach ($map as $field => $meta) {
            // Skip teamseason_select if roster is being set (roster takes priority)
            if ($hasRoster && $field === 'teamseason_select') {
                continue;
            }

            // Skip other entity tags if roster is being set (only person allowed with roster)
            $skipFields = ['game_select', 'site_select', 'opponent_select', 'team_select', 'sport_select'];
            if ($hasRoster && in_array($field, $skipFields)) {
                continue;
            }

            if (!empty($data[$field])) {
                $id = (int)$data[$field];
                if ($id > 0) {
                    $slug = $meta['prefix'] . $id;

                    // Use service layer for entities with dedicated services
                    if (isset($meta['service'])) {
                        if ($meta['service'] === 'person') {
                            $display = $this->personService->getDisplayLabel($id);
                        } else {
                            // teamseason case
                            $display = $this->teamSeasonService->getSportDisplayLabel($id);
                        }
                    } else {
                        // Fallback to direct table lookup for entities without services yet
                        $table = TableRegistry::getTableLocator()->get($meta['table']);
                        $row = $table->find()->select()->where(['id' => $id])->first();
                        $display = $meta['label']($row);
                    }

                    $tagsToApply[$slug] = ['slug' => $slug, 'name' => (string)$display];
                    $displayNames[] = (string)$display;
                }
            }
        }

        // Team season roster (dependent tag). Expect roster_select => roster id
        // Roster tag takes priority and clears all other entity tags (except person)
        if ($hasRoster) {
            $rosterId = (int)$data['roster_select'];
            // Get roster display data from service
            $rosterData = $this->rosterService->getRosterDisplayData($rosterId);

            // Ensure person tag is present
            $personTag = 'person-' . $rosterData['person_id'];
            if (!isset($tagsToApply[$personTag])) {
                $tagsToApply[$personTag] = [
                    'slug' => $personTag,
                    'name' => $rosterData['person_label'],
                ];
                $displayNames[] = $rosterData['person_label'];
            }

            // Add roster tag with friendly label from service
            $rosterSlug = 'team_season_roster-' . $rosterId;
            $tagsToApply[$rosterSlug] = [
                'slug' => $rosterSlug,
                'name' => $rosterData['team_season_label'],
            ];
        }

        // Freeform tags (comma separated or array). Exclude any that match record-based display names.
        $raw = $data['tags'] ?? [];
        if (is_string($raw)) {
            $raw = array_map('trim', explode(',', $raw));
        }
        if (is_array($raw)) {
            foreach ($raw as $r) {
                $r = trim((string)$r);
                if ($r === '') {
                    continue;
                }
                // Do not include freeform tag if it matches a record-based display name
                $matched = false;
                foreach ($displayNames as $dn) {
                    if (strcasecmp($dn, $r) === 0) {
                        $matched = true;
                        break;
                    }
                }
                if ($matched) {
                    continue;
                }
                // Keep freeform as-is (string)
                $tagsToApply[$r] = $r;
            }
        }

        // Normalize unique by slug/key and collect for attachTags
        $finalTags = [];
        foreach ($tagsToApply as $val) {
            if (is_array($val)) {
                $finalTags[] = $val;
            } else {
                $finalTags[] = (string)$val;
            }
        }

        // Apply via processor
        $applied = $finalTags ? $this->replaceTags($imageId, $finalTags) : [];

        return $applied;
    }

    /**
     * Normalize tag inputs into ['slug' => string, 'name' => string].
     *
     * @param array<int|string,string|array> $tags
     * @return array<int,array{slug:string,name:string}>
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
                $slug = \Cake\Utility\Text::slug($name) ?: strtolower($name);
            }
            $normalized[] = ['slug' => $slug, 'name' => $name];
        }

        return $normalized;
    }
}
