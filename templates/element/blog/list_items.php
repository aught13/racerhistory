<?php
declare(strict_types=1);
/** @var \App\Model\Entity\BlogPost[] $paginatedPosts */
/** @var int $page */
/** @var int $limit */
/** @var int $total */
?>
<?php foreach ($paginatedPosts as $post): ?>
<turbo-frame id="blog-post-<?= h($post->slug) ?>" class="blog-list-item-frame mb-3 pb-3 border-bottom">
    <div class="blog-list-item cursor-pointer d-flex gap-3" data-blog-post="<?= h($post->slug) ?>" style="cursor: pointer;">
        <?php if (!empty($post->hero_image_id)): ?>
        <div style="flex-shrink: 0; width: 120px; height: 90px;">
            <img src="/images/serve/<?= h($post->hero_image_id) ?>?w=200&h=150&fit=cover" class="img-fluid rounded" alt="<?= h($post->title) ?>" style="object-fit: cover; width: 100%; height: 100%;">
        </div>
        <?php endif; ?>
        <div class="flex-grow-1">
            <h4 class="h6 mb-1"><?= h($post->title) ?></h4>
            <p class="text-muted small mb-2">
                <?php if ($post->published_at instanceof \DateTimeInterface): ?>
                    <?= h($post->published_at->format('M j, Y')) ?>
                <?php else: ?>
                    <?= h($post->published_at ?? '') ?>
                <?php endif; ?>
            </p>
            <p class="small mb-0 blog-list-excerpt"><?= h($post->excerpt ?: mb_substr((string)$post->body, 0, 120) . '...') ?></p>
        </div>
    </div>
    <turbo-frame id="blog-post-view-<?= h($post->slug) ?>" data-view-frame></turbo-frame>
</turbo-frame>
<?php endforeach; ?>

