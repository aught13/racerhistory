<?php
declare(strict_types=1);
/**
 * Blog List Items Element
 *
 * Renders paginated blog post list items within Turbo Frames.
 * Uses picture elements with WebP support for responsive thumbnails.
 *
 * @var \App\Model\Entity\BlogPost[] $paginatedPosts
 * @var int $page
 * @var int $limit
 * @var int $total
 * @var \App\View\AppView $this
 */
?>
<?php foreach ($paginatedPosts as $post): ?>
<turbo-frame id="blog-post-<?= h($post->slug) ?>" class="blog-list-item-frame mb-3 pb-3 border-bottom">
    <article class="blog-list-item cursor-pointer d-flex gap-3" data-blog-post="<?= h($post->slug) ?>" style="cursor: pointer;">
        <?php if (!empty($post->hero_image_id)): ?>
        <figure style="flex-shrink: 0; width: 120px; height: 90px; margin: 0;">
            <?= $this->ImageServe->picture(
                $post->hero_image_id,
                ['w' => 200, 'h' => 150, 'fit' => 'cover'],
                [
                    'alt' => h($post->title),
                    'class' => 'img-fluid rounded',
                    'style' => 'object-fit: cover; width: 100%; height: 100%;',
                ]
            ) ?>
        </figure>
        <?php endif; ?>
        <div class="flex-grow-1">
            <h2 class="h6 mb-1"><?= h($post->title) ?></h2>
            <p class="text-muted small mb-2">
                <?php if ($post->published_at instanceof \DateTimeInterface): ?>
                    <time datetime="<?= h($post->published_at->format('Y-m-d')) ?>">
                        <?= h($post->published_at->format('M j, Y')) ?>
                    </time>
                <?php else: ?>
                    <?= h($post->published_at ?? '') ?>
                <?php endif; ?>
            </p>
            <p class="small mb-0 blog-list-excerpt"><?= h($post->excerpt ?: mb_substr(strip_tags((string)$post->body), 0, 120) . '...') ?></p>
        </div>
    </article>
    <turbo-frame id="blog-post-view-<?= h($post->slug) ?>" data-view-frame></turbo-frame>
</turbo-frame>
<?php endforeach; ?>

