<?php
/**
 * @var array<\App\Model\Entity\BlogPost> $recentPosts
 * @var array<\App\Model\Entity\BlogTag> $popularTags
 */

$recentPosts = $recentPosts ?? [];
$popularTags = $popularTags ?? [];
?>
<aside class="blog-sidebar">
    <!-- Search Widget -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <h5 class="card-title h6 fw-bold mb-3">Search News</h5>
            <form action="<?= $this->Url->build(['controller' => 'Blog', 'action' => 'index']) ?>" method="get">
                <div class="input-group">
                    <input type="text" class="form-control" name="q" placeholder="Search articles...">
                    <button class="btn btn-primary" type="submit"><i class="bi bi-search"></i></button>
                </div>
            </form>
        </div>
    </div>

    <!-- Tags Widget -->
    <?php if (!empty($popularTags)) : ?>
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <h5 class="card-title h6 fw-bold mb-3">Popular Tags</h5>
            <div class="d-flex flex-wrap gap-2">
                <?php foreach ($popularTags as $tag) : ?>
                <a href="<?= $this->Url->build(['controller' => 'Blog', 'action' => 'index', '?' => ['tag' => $tag->slug]]) ?>" class="btn btn-outline-secondary btn-sm badge rounded-pill text-body fw-normal d-inline-flex align-items-center column-gap-1">
                    <?= h($tag->name) ?>
                    <span class="badge bg-secondary rounded-pill"><?= h($tag->post_count) ?></span>
                </a>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <?= $this->element('Ads/block', ['slot' => 'news_sidebar_1']) ?>

    <!-- Recent Posts Widget -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <h5 class="card-title h6 fw-bold mb-3">Recent Posts</h5>
            <ul class="list-group list-group-flush list-unstyled mb-0">
                <?php foreach ($recentPosts as $recent) : ?>
                <li class="mb-3 d-flex align-items-center gap-3">
                    <?php if (!empty($recent->hero_image_id)) : ?>
                        <?= $this->ImageServe->picture($recent->hero_image_id, ['profile' => 'blog_index_card'], ['style' => 'width: 60px; height: 60px; object-fit: cover;', 'class' => 'rounded']) ?>
                    <?php endif; ?>
                    <div>
                        <a href="<?= $this->Url->build(['controller' => 'Blog', 'action' => 'view', $recent->slug]) ?>" class="text-decoration-none text-body fw-medium d-block lh-sm" style="font-size: 0.9rem;">
                            <?= h($recent->title) ?>
                        </a>
                        <small class="text-muted" style="font-size: 0.8rem;"><?= h($recent->published_at?->format('M j, Y')) ?></small>
                    </div>
                </li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>

    <?= $this->element('Ads/block', ['slot' => 'news_sidebar_2']) ?>
</aside>
