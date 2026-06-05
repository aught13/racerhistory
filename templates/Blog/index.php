<?php
declare(strict_types=1);

/**
 * Blog Index Page
 *
 * Displays the public blog listing with featured posts and infinite scroll.
 * Uses Turbo Frames for SPA-like navigation and picture elements for responsive WebP images.
 *
 * @var array<\App\Model\Entity\BlogPost> $posts
 * @var int $page
 * @var int $limit
 * @var int $total
 * @var \App\View\AppView $this
 */

$this->assign('title', 'Blog');
$this->start('css');
echo $this->Html->css('blog-content');
$this->end();
echo $this->element('blog/index_frame');
