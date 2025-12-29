<?php
declare(strict_types=1);
/** @var \App\Model\Entity\BlogPost[] $posts */
?>
<div class="container mt-4" aria-label="Public Blog">
    <h1 class="h3">Blog</h1>
    <div class="list-group">
        <?php foreach ($posts as $p): ?>
            <a class="list-group-item list-group-item-action" href="<?= $this->Url->build(['/blog/' . h($p->slug)]) ?>">
                <strong><?= h($p->title) ?></strong>
                <div class="small text-muted">
                    <?= $p->published_at instanceof \DateTimeInterface ? h($p->published_at->format('M j, Y')) : h((string)$p->published_at) ?>
                </div>
            </a>
        <?php endforeach; ?>
    </div>
</div>
<div class="container py-4">
    <h1 class="mb-4">Blog</h1>
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
