<?php
declare(strict_types=1);
/** @var \App\Model\Entity\BlogPost[] $posts */
?>
<?= $this->element('blog/index_frame') ?>

<?php $this->start('script'); ?>
<?= $this->Html->script('blog-view-init-loader', ['type' => 'module']) ?>
<?php $this->end(); ?>
