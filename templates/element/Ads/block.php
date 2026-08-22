<?php
declare(strict_types=1);

use Cake\Core\Configure;

/**
 * Generic ad slot renderer backed by SiteOptions.
 *
 * Expected option keys per slot:
 * - ad_{slot}_active (checkbox)
 * - ad_{slot}_html (text/textarea)
 * - ad_{slot}_google_mode (checkbox)
 *
 * @var \App\View\AppView $this
 * @var string $slot
 */

$slot = isset($slot) ? trim((string)$slot) : '';
if ($slot === '') {
    return;
}

$active = (bool)Configure::read('SiteOptions.ad_' . $slot . '_active', false);
$htmlBlock = trim((string)Configure::read('SiteOptions.ad_' . $slot . '_html', ''));
$googleMode = (bool)Configure::read('SiteOptions.ad_' . $slot . '_google_mode', false);

if (!$active || $htmlBlock === '') {
    return;
}

$slotClass = str_replace('_', '-', $slot);
?>
<section class="rh-ad-slot rh-ad-slot--<?= h($slotClass) ?><?= $googleMode ? ' rh-ad-slot--google' : '' ?>" data-ad-slot="<?= h($slot) ?>" data-google-mode="<?= $googleMode ? '1' : '0' ?>">
    <div class="rh-ad-slot__inner text-center">
        <?= $htmlBlock ?>
    </div>
</section>
