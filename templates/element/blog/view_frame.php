<?php
declare(strict_types=1);
/**
 * Blog Post View Frame Element
 *
 * Displays the full blog post content within a Turbo Frame for SPA-like navigation.
 * Uses picture elements with WebP support for responsive images.
 *
 * @var \App\Model\Entity\BlogPost $post
 * @var \App\View\AppView $this
 */
?>
<turbo-frame id="blog-post-view-<?= h($post->slug) ?>" class="blog-expanded-frame" data-view-frame>
    <article class="blog-post-view p-4 rh-surface rounded mb-3" data-blog-post="<?= h($post->slug) ?>">
        <div class="blog-collapse-row mb-3">
            <button class="blog-collapse" type="button" aria-label="Collapse post">
                <i class="bi bi-caret-up-fill" aria-hidden="true"></i>
            </button>
        </div>

        <header class="d-flex flex-column gap-2 mb-4">
            <h1 class="mb-0"><?= h($post->title) ?></h1>
            <p class="text-muted mb-0">
                <?php if ($post->published_at instanceof \DateTimeInterface): ?>
                    <time datetime="<?= h($post->published_at->format('Y-m-d')) ?>">
                        <?= h($post->published_at->format('F j, Y')) ?>
                    </time>
                <?php else: ?>
                    <?= h($post->published_at ?? '') ?>
                <?php endif; ?>
            </p>
        </header>

        <?php if (!empty($post->hero_image)): ?>
        <figure class="mb-4 text-center">
            <?= $this->ImageServe->picture(
                $post->hero_image,
                ['w' => 1200, 'fit' => 'contain'],
                [
                    'alt' => h($post->title),
                    'class' => 'img-fluid rounded',
                    'style' => 'object-fit: contain; max-height: 500px;',
                ]
            ) ?>
        </figure>
        <?php endif; ?>

        <div class="blog-content fs-5 lh-lg">
            <?= $post->body ?>
        </div>

        <?php if (!empty($post->blog_tags)): ?>
        <footer class="blog-tags">
            <?php foreach ((array)$post->blog_tags as $tag): ?>
                <span class="blog-tag badge bg-secondary"><?= h($tag->name) ?></span>
            <?php endforeach; ?>
        </footer>
        <?php endif; ?>
    </article>
</turbo-frame>
