<?php
declare(strict_types=1);

/**
 * Blog List Items Element
 *
 * Renders paginated blog post list items within Turbo Frames.
 * Uses picture elements with WebP support for responsive thumbnails.
 *
 * @var array<\App\Model\Entity\BlogPost> $paginatedPosts
 * @var int $page
 * @var int $limit
 * @var int $total
 * @var \App\View\AppView $this
 */
?>
<?php
$indexOffset = max(0, ($page - 1) * $limit);

// Page 1 list starts after the featured post, so global feed positions shift by one.
if ($page === 1) {
    $indexOffset += 1;
}
?>
<?php foreach ($paginatedPosts as $index => $post) : ?>
<div class="blog-list-item-wrapper mb-3 pb-3 border-bottom">
    <a href="<?= $this->Url->build(['controller' => 'Blog', 'action' => 'view', $post->slug]) ?>" class="text-decoration-none text-body">
        <article class="blog-list-item d-flex gap-3">
            <?php if (!empty($post->hero_image_id)) : ?>
            <figure style="flex-shrink: 0; width: 120px; height: 90px; margin: 0;">
                <?= $this->ImageServe->picture(
                    $post->hero_image_id,
                    ['profile' => 'blog_index_card'],
                    [
                        'alt' => h($post->title),
                        'class' => 'img-fluid rounded',
                        'style' => 'object-fit: cover; width: 100%; height: 100%;',
                    ],
                ) ?>
            </figure>
            <?php endif; ?>
            <div class="flex-grow-1">
                <h2 class="h6 mb-1 fw-bold"><?= h($post->title) ?></h2>
                <p class="text-muted small mb-2">
                    <?php if ($post->published_at instanceof DateTimeInterface) : ?>
                        <time datetime="<?= h($post->published_at->format('Y-m-d')) ?>">
                            <?= h($post->published_at->format('M j, Y')) ?>
                        </time>
                    <?php else : ?>
                        <?= h($post->published_at ?? '') ?>
                    <?php endif; ?>
                </p>
                <p class="small mb-0 blog-list-excerpt"><?= h($post->excerpt ?: mb_substr(strip_tags((string)$post->body), 0, 120) . '...') ?></p>
            </div>
        </article>
    </a>
</div>

    <?php if (($indexOffset + $index + 1) % 5 === 0) : ?>
        <?= $this->element('Ads/block', ['slot' => 'news_every_fifth']) ?>
    <?php endif; ?>
<?php endforeach; ?>

