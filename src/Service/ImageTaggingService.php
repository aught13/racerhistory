<?php
declare(strict_types=1);

namespace App\Service;

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
        if ($finalTags) {
            $processor = new ImageProcessor();
            $processor->attachTags($imageId, $finalTags);
        }

        // Return canonical slugs applied (for API/consumers): map arrays to slug strings
        return array_values(array_map(fn($t) => is_array($t) ? $t['slug'] : (string)$t, $finalTags));
    }
}
