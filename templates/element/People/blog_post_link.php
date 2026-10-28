<?php
declare(strict_types=1);

/**
 * @var \App\Model\Entity\BlogPost $post
 * @var \App\View\AppView $this
 */

$viewUrl = $this->Url->build([
    'controller' => 'Blog',
    'action' => 'view',
    $post->slug,
]);
?>
<a href="<?= h($viewUrl) ?>" class="list-group-item list-group-item-action">
    <div class="d-flex gap-3">
        <?php if (!empty($post->hero_image_id)) : ?>
            <div style="flex-shrink: 0; width: 120px; height: 90px;">
                <?= $this->ImageServe->picture(
                    (int)$post->hero_image_id,
                    ['w' => 200, 'h' => 150, 'fit' => 'cover'],
                    [
                        'class' => 'img-fluid rounded',
                        'alt' => (string)$post->title,
                        'style' => 'object-fit: cover; width: 100%; height: 100%;',
                    ],
                ) ?>
            </div>
        <?php endif; ?>
        <div class="flex-grow-1">
            <h3 class="h6 mb-1"><?= h($post->title) ?></h3>
            <p class="text-muted small mb-2">
                <?php if ($post->published_at instanceof DateTimeInterface) : ?>
                    <?= h($post->published_at->format('M j, Y')) ?>
                <?php else : ?>
                    <?= h($post->published_at ?? '') ?>
                <?php endif; ?>
            </p>
            <?php if (!empty($post->excerpt)) : ?>
                <p class="small mb-0"><?= h($post->excerpt) ?></p>
            <?php endif; ?>
        </div>
    </div>
</a>
