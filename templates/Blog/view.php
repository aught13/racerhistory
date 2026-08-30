<?php
declare(strict_types=1);

/**
 * Blog Post View Page
 *
 * Displays a single published blog post.
 * Uses Turbo Frames for SPA-like navigation and picture elements for responsive WebP images.
 *
 * @var \App\Model\Entity\BlogPost $post
 * @var \App\View\AppView $this
 */

$this->assign('title', h($post->title));
$this->assign('socialTitle', (string)$post->title);

$excerpt = trim((string)($post->excerpt ?? ''));
if ($excerpt === '') {
    $excerpt = trim((string)preg_replace('/\s+/', ' ', strip_tags((string)($post->body ?? ''))));
}
if ($excerpt !== '') {
    $this->assign('socialDescription', $excerpt);
}

if (!empty($post->hero_image_id)) {
    $heroImageUrl = $this->ImageServe->url((int)$post->hero_image_id, ['profile' => 'blog_featured']);
    if ($heroImageUrl !== '') {
        $this->assign('socialImageUrl', $this->Url->build($heroImageUrl, ['fullBase' => true]));
    }
}
?>
<?php $this->start('css'); ?>
<?= $this->Html->css('blog-content') ?>
<?php $this->end(); ?>

<div class="blog-page blog-page--view" aria-label="Blog Post">
    <?= $this->element('blog/view_frame') ?>
</div>
