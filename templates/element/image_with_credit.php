<?php
/**
 * @var \App\Model\Entity\Image $image
 * @var string $imgContent The rendered img or picture html
 */
$credit = $image->photo_credit ?? null;
?>
<?php if ($credit) : ?>
<div class="position-relative d-inline-block">
    <?= $imgContent ?>
    <div class="position-absolute bottom-0 end-0 p-1 m-2 rounded text-white" 
         style="background-color: rgba(0, 0, 0, 0.6); font-size: 0.75rem; z-index: 10;">
        <i class="bi bi-camera-fill me-1"></i> <?= h($credit) ?>
    </div>
</div>
<?php else : ?>
    <?= $imgContent ?>
<?php endif; ?>
