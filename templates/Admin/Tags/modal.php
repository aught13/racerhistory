<?php
/**
 * Tag modal partial
 * Expects view variables: teams, teamSeasons, games, sites, opponents, sports,
 * currentTags, tagString, subject, subjectId
 */

$modalId = 'tag-modal-' . ($subject ?? 'generic') . '-' . (int)($subjectId ?? 0);
$formUrl = ['controller' => 'Tags', 'action' => 'apply', $subject ?? 'generic', (int)($subjectId ?? 0)];
$applyUrl = $this->Url->build($formUrl);
?>

<!-- Note: the tag-modal Stimulus controller is attached to the trigger wrapper
    element. Avoid attaching a nested controller on the modal itself so the
    host wrapper's controller handles open/save actions correctly. -->
<div class="modal fade" id="<?= h($modalId) ?>" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Manage Tags</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div data-tag-modal-fields="1" data-apply-url="<?= h($applyUrl) ?>">
                    <?= $this->element('Admin/tag_selection', [
                        'teams' => $teams ?? [],
                        'teamSeasons' => $teamSeasons ?? [],
                        'games' => $games ?? [],
                        'sites' => $sites ?? [],
                        'opponents' => $opponents ?? [],
                        'sports' => $sports ?? [],
                        'currentTags' => $currentTags ?? [],
                        'tagString' => $tagString ?? '',
                    ]) ?>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" data-action="tag-modal#save">Save Tags</button>
            </div>
        </div>
    </div>
</div>
