<?php
declare(strict_types=1);
/** @var \App\Model\Entity\BlogPost $post */
?>
<div class="container py-4" aria-label="Blog Post">
    <div class="row justify-content-center">
        <div class="col-lg-10 col-xl-8">
            <?= $this->element('blog/view_frame') ?>
        </div>
    </div>
</div>

<?php $this->start('script'); ?>
<?= $this->Html->script('blog-view-init-loader', ['type' => 'module']) ?>
<?php $this->end(); ?>
