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
?>
<?php $this->start('css'); ?>
<?= $this->Html->css('blog-content') ?>
<?php $this->end(); ?>

<div class="container py-4" aria-label="Blog Post">
    <?= $this->element('blog/view_frame') ?>
</div>
