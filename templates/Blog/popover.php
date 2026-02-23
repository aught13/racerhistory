<?php
declare(strict_types=1);

/** @var \App\Model\Entity\BlogPost $post */
?>
<div class="blog-popover-content">
    <h3 class="h6 mb-2"><?= h($post->title) ?></h3>
    <p class="text-muted small mb-2">
        <?php if ($post->published_at instanceof \DateTimeInterface) : ?>
            <?= h($post->published_at->format('M j, Y')) ?>
        <?php else : ?>
            <?= h($post->published_at ?? '') ?>
        <?php endif; ?>
    </p>
    <p class="small mb-3">
        <?= h($post->excerpt ?: mb_substr((string)$post->body, 0, 180) . '...') ?>
    </p>
    <a
        href="<?= $this->Url->build(['controller' => 'Blog', 'action' => 'view', $post->slug]) ?>"
        class="btn btn-sm btn-outline-primary">
        Read full story
    </a>
</div>
