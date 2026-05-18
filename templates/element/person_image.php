<?php
/**
 * Person Image Element
 *
 * Displays a person's image using the Images controller serve endpoint
 *
 * Variables:
 * - $person: Person entity with person_image field
 * - $size: Optional size ('small', 'medium', 'large') - defaults to 'medium'
 * - $profile: Optional image profile to use
 * - $variant: Optional image variant to use (e.g., 'thumb', 'medium')
 * - $class: Optional CSS classes
 * - $style: Optional inline styles
 *
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Person $person
 */

$size = $size ?? 'medium';
$profile = $profile ?? null;
$variant = $variant ?? 'thumb';
$class = $class ?? '';
$style = $style ?? '';
$deferred = $deferred ?? false;

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
$cssStyle = "width: {$width}px; height: {$height}px; object-fit: cover; border-radius: 50%; " . $style;

// Build image URL
if (!empty($person->person_image) && is_numeric($person->person_image)) {
    $imageParams = [];
    if (is_string($profile) && $profile !== '') {
        $imageParams['profile'] = $profile;
    } elseif (is_string($variant) && $variant !== '') {
        $imageParams['variant'] = $variant;
    }

    if ($deferred) {
        $thumbUrl = $this->ImageServe->url((int)$person->person_image, $imageParams);
        echo $this->Html->image('data:image/gif;base64,R0lGODlhAQABAAAAACw=', [
            'alt' => (string)($person->display ?? $person->first . ' ' . $person->last),
            'class' => trim($cssClass . ' js-person-thumb'),
            'style' => $cssStyle,
            'loading' => 'lazy',
            'decoding' => 'async',
            'width' => $width,
            'height' => $height,
            'data-thumb-src' => $thumbUrl,
            'data-rh-no-retry' => '1',
        ]);
    } else {
        echo $this->ImageServe->picture(
            (int)$person->person_image,
            $imageParams,
            [
                'alt' => (string)($person->display ?? $person->first . ' ' . $person->last),
                'class' => $cssClass,
                'style' => $cssStyle,
                'loading' => 'lazy',
                'decoding' => 'async',
            ],
        );
    }
} else {
    // Show placeholder if no image - perfect circle to match photo avatars
    echo $this->Html->div(
        'placeholder-image ' . $cssClass,
        $this->Html->tag('span', h(substr($person->display ?? $person->first ?? '?', 0, 1)), [
            'class' => 'd-flex align-items-center justify-content-center h-100 bg-secondary text-white fw-bold',
            'style' => 'font-size: ' . ($width * 0.4) . 'px;',
        ]),
        ['style' => $cssStyle . ' background-color: #6c757d; border-radius: 50%; overflow: hidden;'],
    );
}
