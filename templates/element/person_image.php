<?php
/**
 * Person Image Element
 *
 * Displays a person's image using the Images controller serve endpoint
 *
 * Variables:
 * - $person: Person entity with person_image field
 * - $size: Optional size ('small', 'medium', 'large') - defaults to 'medium'
 * - $variant: Optional image variant to use (e.g., 'thumb', 'medium')
 * - $class: Optional CSS classes
 * - $style: Optional inline styles
 */

$size = $size ?? 'medium';
$variant = $variant ?? '';
$class = $class ?? '';
$style = $style ?? '';

// Size presets
$sizeMap = [
    'small' => ['width' => 48, 'height' => 48, 'class' => 'rounded-circle'],
    'medium' => ['width' => 100, 'height' => 100, 'class' => 'rounded'],
    'large' => ['width' => 200, 'height' => 200, 'class' => 'rounded'],
];

$sizeConfig = $sizeMap[$size] ?? $sizeMap['medium'];
$width = $sizeConfig['width'];
$height = $sizeConfig['height'];
$defaultClass = $sizeConfig['class'];

// Build CSS
$cssClass = trim($defaultClass . ' ' . $class);
$cssStyle = "width: {$width}px; height: {$height}px; object-fit: cover; " . $style;

// Build image URL
if (!empty($person->person_image) && is_numeric($person->person_image)) {
    $params = [
        'w' => $width,
        'h' => $height,
        'fit' => 'cover',
    ];
    if ($variant) {
        $params = ['variant' => $variant] + $params;
    }
    $imageUrl = $this->ImageServe->url((int)$person->person_image, $params);

    // Use direct img tag instead of Html->image() to avoid URL processing issues
    echo '<img src="' . h($imageUrl) . '" alt="' . h($person->display ?? $person->first . ' ' . $person->last) . '" class="' . h($cssClass) . '" style="' . h($cssStyle) . '" loading="lazy" decoding="async">';
} else {
    // Show placeholder if no image
    echo $this->Html->div('placeholder-image ' . $cssClass,
        $this->Html->tag('span', h(substr($person->display ?? $person->first ?? '?', 0, 1)), [
            'class' => 'd-flex align-items-center justify-content-center h-100 bg-secondary text-white fw-bold',
            'style' => 'font-size: ' . ($width * 0.4) . 'px;'
        ]),
        ['style' => $cssStyle . ' background-color: #6c757d;']
    );
}
?>
