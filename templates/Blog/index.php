<?php
declare(strict_types=1);
/**
 * Blog Index Page
 *
 * Displays the public blog listing with featured posts and infinite scroll.
 * Uses Turbo Frames for SPA-like navigation and picture elements for responsive WebP images.
 *
 * @var \App\Model\Entity\BlogPost[] $posts
 * @var int $page
 * @var int $limit
 * @var int $total
 * @var \App\View\AppView $this
 */

$this->assign('title', 'Blog');
?>
<?php $this->start('css'); ?>
<?= $this->Html->css('blog-content') ?>
<?php $this->end(); ?>

<?= $this->element('blog/index_frame') ?>

<?php $this->start('script'); ?>
<?= $this->Html->script('blog-view-init-loader', ['type' => 'module']) ?>
<?php $this->end(); ?>
