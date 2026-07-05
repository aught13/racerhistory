<?php
declare(strict_types=1);

/**
 * @var \App\View\AppView $this
 * @var array<\App\Model\Entity\BlogPost> $posts
 * @var array<\App\Model\Entity\BlogPost> $paginatedPosts
 * @var int $page
 * @var int $limit
 * @var int $total
 */

$page = $page ?? 1;
$limit = $limit ?? 10;
$total = $total ?? count($posts ?? []);
$paginatedPosts = $paginatedPosts ?? $posts ?? [];
$hasMore = $page * $limit < $total;
$featured = null;
$listPosts = $paginatedPosts;

if ($page === 1 && !empty($paginatedPosts)) {
    $featured = $paginatedPosts[0];
    $listPosts = array_slice($paginatedPosts, 1);
}
?>
<turbo-frame id="blog" target="_top" data-controller="blog-interactions">
    <div class="row">
        <!-- 70% Feed Column -->
        <div class="col-lg-8 pe-lg-4">
            <?php if (empty($paginatedPosts) && $page === 1) : ?>
                <div class="alert alert-info mb-0">No posts yet.</div>
            <?php else : ?>
                <!-- Featured Hero Post (only on page 1) -->
                <?php if ($page === 1 && $featured) : ?>
                <div class="blog-featured-frame mb-5 pb-4 border-bottom">
                    <!-- Main featured view: title → hero image → blurb -->
                    <a href="<?= $this->Url->build(['controller' => 'Blog', 'action' => 'view', $featured->slug]) ?>" class="text-decoration-none text-body" data-turbo-frame="_top">
                        <div class="blog-featured transition-hover">
                            <h1 class="h2 mb-2 blog-hero-title fw-bold"><?= h($featured->title) ?></h1>
                            <p class="text-muted small mb-3">
                                <?php if ($featured->published_at instanceof DateTimeInterface) : ?>
                                    <time datetime="<?= h($featured->published_at->format('Y-m-d')) ?>">
                                        <?= h($featured->published_at->format('F j, Y')) ?>
                                    </time>
                                <?php else : ?>
                                    <?= h($featured->published_at ?? '') ?>
                                <?php endif; ?>
                            </p>
                            <?php if (!empty($featured->hero_image_id)) : ?>
                            <div class="blog-featured-hero mb-3">
                                <?= $this->ImageServe->responsivePicture(
                                    $featured->hero_image_id,
                                    [600, 900, 1200],
                                    ['profile' => 'blog_featured'],
                                    [
                                        'alt' => h($featured->title),
                                        'class' => 'img-fluid rounded blog-hero-image shadow-sm',
                                        'sizes' => '(max-width: 991px) 100vw, 100%',
                                    ],
                                ) ?>
                            </div>
                            <?php endif; ?>
                            <p class="lead mb-0 blog-hero-excerpt"><?= h($featured->excerpt ?: mb_substr(strip_tags((string)$featured->body), 0, 220) . '...') ?></p>
                        </div>
                    </a>
                </div>
                <?php endif; ?>

                <!-- Paginated Posts List -->
                <div class="blog-list" id="blog-list">
                    <?= $this->element('blog/list_items', ['paginatedPosts' => $listPosts, 'page' => $page, 'limit' => $limit, 'total' => $total]) ?>
                </div>

                <!-- Pagination -->
                <div class="d-flex justify-content-between mt-4">
                    <?php if ($page > 1) : ?>
                        <a href="<?= $this->Url->build(['action' => 'index', '?' => ['page' => $page - 1, 'limit' => $limit]]) ?>" class="btn btn-outline-secondary" data-turbo-frame="blog">&larr; Newer Posts</a>
                    <?php else : ?>
                        <div></div>
                    <?php endif; ?>

                    <?php if ($hasMore) : ?>
                        <a href="<?= $this->Url->build(['action' => 'index', '?' => ['page' => $page + 1, 'limit' => $limit]]) ?>" class="btn btn-outline-secondary" data-turbo-frame="blog">Older Posts &rarr;</a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- 30% Sidebar Column -->
        <div class="col-lg-4">
            <?= $this->cell('BlogWidget::sidebar') ?>
        </div>
    </div>
</turbo-frame>
