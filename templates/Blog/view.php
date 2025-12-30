<?php
declare(strict_types=1);
/** @var \App\Model\Entity\BlogPost $post */
?>
<div class="container py-4" aria-label="Blog Post">
    <div class="row justify-content-center">
        <div class="col-lg-10 col-xl-8">
            <p><a href="<?= $this->Url->build(['action' => 'index']) ?>" class="text-decoration-none">← Back to blog</a></p>
            <div class="d-flex flex-column gap-2 mb-3">
                <h1 class="mb-0"><?= h($post->title) ?></h1>
                <p class="text-muted mb-0">
                    <?php if ($post->published_at instanceof \DateTimeInterface): ?>
                        <?= h($post->published_at->format('F j, Y')) ?>
                    <?php else: ?>
                        <?= h($post->published_at ?? '') ?>
                    <?php endif; ?>
                </p>
            </div>
            <?php if (!empty($post->hero_image_id)): ?>
                <div class="mb-4 text-center">
                    <img src="/images/serve/<?= h($post->hero_image_id) ?>?w=1200&fit=contain" class="img-fluid rounded" alt="<?= h($post->title) ?>">
                </div>
            <?php endif; ?>
            <?php if (!empty(trim((string)$post->excerpt))): ?>
                <p class="lead mb-4"><?= h((string)$post->excerpt) ?></p>
            <?php endif; ?>
            <div class="mb-4 d-flex flex-wrap gap-2">
                <?php foreach ((array)$post->blog_tags as $tag): ?>
                    <span class="badge bg-light text-dark border"><?= h($tag->name) ?></span>
                <?php endforeach; ?>
            </div>
            <div class="fs-5 lh-lg">
                <?= $this->Text->autoParagraph($post->body) ?>
            </div>
        </div>
    </div>
</div>
