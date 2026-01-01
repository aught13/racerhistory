<?php
declare(strict_types=1);

namespace App\Service;

/**
 * ImageTagUiService
 *
 * UI-focused helpers for formatting image tags for admin views.
 */
class ImageTagUiService
{
    private TeamSeasonRosterService $rosterService;

    /**
     * @param \App\Service\TeamSeasonRosterService|null $rosterService Optional roster service override (useful for testing).
     */
    public function __construct(?TeamSeasonRosterService $rosterService = null)
    {
        $this->rosterService = $rosterService ?? new TeamSeasonRosterService();
    }

    /**
     * Format tags for the admin tag management UI.
     *
     * - Sorts entity tags (ending with -ID) before freeform tags
     * - Replaces roster tag display name with team-season label when available
     * - Builds the comma-separated freeform tag string for the textarea
     *
     * @param iterable<int,object|array> $currentTags
     * @return array{currentTags: array<int,object|array>, tagString: string}
     */
    public function formatTagsForUi(iterable $currentTags): array
    {
        $formattedTags = [];
        $freeformTags = [];

        foreach ($currentTags as $tag) {
            $slug = (string)($tag->slug ?? $tag['slug'] ?? '');
            if ($slug === '') {
                $freeformTags[] = $tag;
                continue;
            }

            if (preg_match('/-[0-9]+$/', $slug)) {
                if (str_starts_with($slug, 'team_season_roster-')) {
                    $rid = (int)substr($slug, strlen('team_season_roster-'));
                    if ($rid > 0) {
                        $display = $this->rosterService->getRosterDisplayData($rid);
                        $label = (string)($display['team_season_label'] ?? '');
                        if ($label !== '') {
                            if (is_object($tag)) {
                                $tag->name = $label;
                            } elseif (is_array($tag)) {
                                $tag['name'] = $label;
                            }
                        }
                    }
                }

                $formattedTags[] = $tag;
                continue;
            }

            $freeformTags[] = $tag;
        }

        $tagString = implode(', ', array_map(
            static fn($t) => (string)($t->name ?? $t['name'] ?? ''),
            $freeformTags
        ));

        return [
            'currentTags' => array_merge($formattedTags, $freeformTags),
            'tagString' => $tagString,
        ];
    }
}
