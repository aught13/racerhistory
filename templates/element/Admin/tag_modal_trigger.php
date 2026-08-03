<?php
/**
 * Tag modal trigger element
 * Variables:
 * - subject: string (e.g., 'images' or 'blogposts')
 * - subjectId: int|null
 * - currentTags: iterable
 * - syncHiddenInputs: bool (when true, writes hidden fields for parent form sync)
 */

$subject = $subject ?? 'generic';
$subjectId = isset($subjectId) ? (int)$subjectId : null;
$syncHiddenInputs = isset($syncHiddenInputs) ? (bool)$syncHiddenInputs : false;
$containerId = 'tag-modal-host-' . $subject . '-' . ($subjectId ?? 'new');
$triggerId = 'tag-modal-trigger-' . $subject . '-' . ($subjectId ?? 'new');
?>

<div class="tag-modal-trigger" data-controller="tag-modal" data-tag-modal-subject-value="<?= h($subject) ?>" data-tag-modal-subject-id-value="<?= h((string)($subjectId ?? '0')) ?>">
    <div class="d-flex align-items-center gap-2">
        <div class="tag-badges">
            <?php if (!empty($currentTags)) : ?>
                <?php foreach ($currentTags as $tag) : ?>
                    <span class="badge bg-secondary me-1 mb-1"><?= h($tag->name ?? $tag['name'] ?? '') ?></span>
                <?php endforeach; ?>
            <?php else : ?>
                <span class="text-muted small">No tags</span>
            <?php endif; ?>
        </div>
        <button type="button" id="<?= h($triggerId) ?>" class="btn btn-sm btn-outline-primary" data-action="click->tag-modal#open">Edit Tags</button>
    </div>
    <div id="<?= h($containerId) ?>" class="tag-modal-host"></div>
    <?php if ($syncHiddenInputs) : ?>
        <!-- Hidden inputs container used for pages that need modal values in parent form submit (bulk upload). -->
        <div class="tag-modal-hidden-inputs visually-hidden" aria-hidden="true"></div>
    <?php endif; ?>
</div>
