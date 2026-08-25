<?php
declare(strict_types=1);

/**
 * Generic ad slot renderer backed by SiteOptions through AdHelper payloads.
 *
 * @var \App\View\AppView $this
 * @var string $slot
 */

$slotName = isset($slot) ? trim((string)$slot) : '';
if ($slotName === '') {
    return;
}

$slotConfig = $this->Ad->slot($slotName);
if (!$slotConfig['active']) {
    return;
}

$isGoogle = $slotConfig['is_google'];
$classes = 'rh-ad-slot rh-ad-slot--' . $slotConfig['slot_class'];
if ($isGoogle) {
    $classes .= ' rh-ad-slot--google';
}

$attributes = [
    'class' => $classes,
    'data-controller' => 'ad-delivery',
    'data-ad-slot' => $slotConfig['slot'],
    'data-google-mode' => $isGoogle ? '1' : '0',
    'data-ad-delivery-mode-value' => $slotConfig['mode'],
    'data-ad-delivery-slot-value' => $slotConfig['slot'],
];

if ($slotConfig['google_slot_id'] !== '') {
    $attributes['data-ad-delivery-google-slot-id-value'] = $slotConfig['google_slot_id'];
}
if ($slotConfig['google_client'] !== '') {
    $attributes['data-ad-delivery-google-client-value'] = $slotConfig['google_client'];
}
if ($slotConfig['google_format'] !== '') {
    $attributes['data-ad-delivery-google-format-value'] = $slotConfig['google_format'];
}
if ($slotConfig['google_layout'] !== '') {
    $attributes['data-ad-delivery-google-layout-value'] = $slotConfig['google_layout'];
}
if ($slotConfig['google_layout_key'] !== '') {
    $attributes['data-ad-delivery-google-layout-key-value'] = $slotConfig['google_layout_key'];
}
if ($slotConfig['google_full_width_responsive'] !== '') {
    $attributes['data-ad-delivery-google-full-width-responsive-value'] = $slotConfig['google_full_width_responsive'];
}
?>
<section
<?php foreach ($attributes as $attributeName => $attributeValue) : ?>
    <?= h($attributeName) ?>="<?= h((string)$attributeValue) ?>"
<?php endforeach; ?>
>
    <div class="rh-ad-slot__inner text-center" data-ad-delivery-target="container"></div>
    <?php if (!$isGoogle) : ?>
        <template data-ad-delivery-target="template"><?= $slotConfig['html'] ?></template>
    <?php endif; ?>
</section>
