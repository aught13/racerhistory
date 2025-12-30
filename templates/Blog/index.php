<?php
declare(strict_types=1);
/** @var \App\Model\Entity\BlogPost[] $posts */
?>
<div class="container py-4" aria-label="Public Blog">
    <div class="d-flex flex-column flex-md-row align-items-start align-items-md-center justify-content-between mb-4">
        <h1 class="h3 mb-2 mb-md-0">Blog</h1>
        <p class="text-muted mb-0">Latest stories, tagged and curated.</p>
    </div>
    <div class="row g-4">
        <?php if (empty($posts)): ?>
            <div class="col-12">
                <div class="alert alert-info mb-0">No posts yet.</div>
            </div>
        <?php endif; ?>
        <?php foreach ($posts as $post): ?>
            <div class="col-md-6 col-lg-4">
                <div class="card h-100 shadow-sm">
                    <?php if (!empty($post->hero_image_id)): ?>
                        <img src="/images/serve/<?= h($post->hero_image_id) ?>?w=600&h=360&fit=cover" class="card-img-top" alt="<?= h($post->title) ?>">
                    <?php endif; ?>
                    <div class="card-body d-flex flex-column">
                        <h2 class="h5"><?= h($post->title) ?></h2>
                        <p class="text-muted small mb-2">
                            <?php if ($post->published_at instanceof \DateTimeInterface): ?>
                                <?= h($post->published_at->format('M j, Y')) ?>
                            <?php else: ?>
                                <?= h($post->published_at ?? '') ?>
                            <?php endif; ?>
                        </p>
                        <p class="flex-grow-1"><?= h($post->excerpt ?: mb_substr((string)$post->body, 0, 140) . '...') ?></p>
                        <div class="mt-auto d-flex flex-wrap gap-1 mb-2">
                            <?php foreach ((array)$post->blog_tags as $tag): ?>
                                <span class="badge bg-light text-dark border"><?= h($tag->name) ?></span>
                            <?php endforeach; ?>
                        </div>
                        <?= $this->Html->link('Read More', ['action' => 'view', $post->slug], ['class' => 'btn btn-primary w-100']) ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>
