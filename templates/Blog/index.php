<?php
declare(strict_types=1);
/** @var \App\Model\Entity\BlogPost[] $posts */
?>
<div class="container py-4" aria-label="Public Blog">
    <div class="d-flex flex-column flex-md-row align-items-start align-items-md-center justify-content-between mb-4">
        <h1 class="h3 mb-2 mb-md-0">Blog</h1>
        <p class="text-muted mb-0">Latest stories, tagged and curated.</p>
    </div>

    <?= $this->element('blog/index_frame') ?>
</div>
