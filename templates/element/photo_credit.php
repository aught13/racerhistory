<?php
/**
 * @var \App\Model\Entity\Image|null $image
 */
if (!isset($image) || empty($image->photo_credit)) {
    return;
}
?>
<div class="position-relative d-inline-block w-100">
    <!-- Image placeholder/slot - this element can wrap an existing img tag -->
    <?= $this->fetch('content') ?>
    
    <div class="position-absolute bottom-0 end-0 p-1 m-2 rounded text-white" 
         style="background-color: rgba(0, 0, 0, 0.6); font-size: 0.75rem; z-index: 10;">
        <i class="bi bi-camera-fill me-1"></i> <?= h($image->photo_credit) ?>
    </div>
</div>
