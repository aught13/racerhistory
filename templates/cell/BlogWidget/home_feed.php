<?php if ($hero) : ?>
<div class="row mb-5">
    <div class="col-12">
        <a href="<?= $this->Url->build(['controller' => 'Blog', 'action' => 'view', $hero->slug]) ?>" class="text-decoration-none text-body">
            <div class="card bg-dark text-white border-0 overflow-hidden shadow-sm">
                <?php if (!empty($hero->hero_image_id)) : ?>
                    <div class="ratio overflow-hidden" style="--bs-aspect-ratio: 51.43%;">
                    <?= $this->ImageServe->responsivePicture(
                        $hero->hero_image_id,
                        [800, 1200],
                        ['profile' => 'blog_featured'],
                        [
                            'alt' => h($hero->title),
                            'class' => 'card-img opacity-50 w-100 h-100 object-fit-cover',
                        ],
                    ) ?>
                    </div>
                <?php endif; ?>
                <div class="card-img-overlay d-flex flex-column justify-content-end p-4 p-md-5 bg-gradient-dark">
                    <h1 class="card-title display-4 fw-bold"><?= h($hero->title) ?></h1>
                    <p class="card-text lead d-none d-md-block">
                        <?= h($this->BlogContent->decodeHtmlEntities((string)($hero->excerpt ?: mb_substr(strip_tags((string)$hero->body), 0, 200) . '...'))) ?>
                    </p>
                    <p class="card-text small">
                        <time><?= h($hero->published_at?->format('F j, Y')) ?></time>
                    </p>
                </div>
            </div>
        </a>
    </div>
</div>
<?php endif; ?>

<?php if (!empty($gridPosts)) : ?>
<div class="row g-4">
    <?php foreach ($gridPosts as $post) : ?>
    <div class="col-md-4">
        <div class="card h-100 border-0 shadow-sm transition-hover">
            <?php if (!empty($post->hero_image_id)) : ?>
            <div class="ratio overflow-hidden rounded-top" style="--bs-aspect-ratio: 51.43%;">
                <?= $this->ImageServe->responsivePicture(
                    $post->hero_image_id,
                    [600, 900],
                    ['profile' => 'blog_featured'],
                    [
                        'alt' => h($post->title),
                        'class' => 'w-100 h-100 object-fit-cover',
                    ],
                ) ?>
            </div>
            <?php endif; ?>
            <div class="card-body d-flex flex-column">
                <h5 class="card-title">
                    <a href="<?= $this->Url->build(['controller' => 'Blog', 'action' => 'view', $post->slug]) ?>" class="text-decoration-none text-body stretched-link">
                        <?= h($post->title) ?>
                    </a>
                </h5>
                <p class="card-text small text-muted mb-3">
                    <time><?= h($post->published_at?->format('M j, Y')) ?></time>
                </p>
                <p class="card-text small mb-0 mt-auto">
                    <?= h($this->BlogContent->decodeHtmlEntities((string)($post->excerpt ?: mb_substr(strip_tags((string)$post->body), 0, 100) . '...'))) ?>
                </p>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>
