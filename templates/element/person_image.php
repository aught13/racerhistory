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

$normalizeImageUrl = static function (?string $value): string {
    $value = trim((string)$value);
    if ($value === '') {
        return '';
    }

    if (str_starts_with($value, 'http://') || str_starts_with($value, 'https://') || str_starts_with($value, 'data:')) {
        return $value;
    }
    if (str_starts_with($value, '/img/storage/')) {
        return $value;
    }
    if (str_starts_with($value, 'img/storage/')) {
        return '/' . $value;
    }
    if (str_starts_with($value, '/')) {
        return $value;
    }

    if (str_contains($value, '/')) {
        return '/img/storage/' . ltrim($value, '/');
    }

    return '/img/' . ltrim($value, '/');
};

$imageId = 0;
foreach ([$person->person_image ?? null, $person->person_image_id ?? null, $person->image_id ?? null] as $candidate) {
    if (is_numeric((string)$candidate) && (int)$candidate > 0) {
        $imageId = (int)$candidate;
        break;
    }
}

if ($imageId <= 0 && isset($person->image) && is_object($person->image) && is_numeric((string)($person->image->id ?? null))) {
    $imageId = (int)$person->image->id;
}

$directImageUrl = '';
if ($imageId <= 0) {
    foreach (
        [
        $person->person_image_url ?? null,
        $person->image_url ?? null,
        is_string($person->person_image ?? null) ? $person->person_image : null,
        ] as $candidate
    ) {
        if (!is_string($candidate) || trim($candidate) === '') {
            continue;
        }
        $directImageUrl = $normalizeImageUrl($candidate);
        if ($directImageUrl !== '') {
            break;
        }
    }
}

// Build image URL
if ($imageId > 0 || $directImageUrl !== '') {
    $imageParams = [];
    if (is_string($profile) && $profile !== '') {
        $imageParams['profile'] = $profile;
    } elseif (is_string($variant) && $variant !== '') {
        $imageParams['variant'] = $variant;
    }

    $resolvedImageUrl = $imageId > 0 ? $this->ImageServe->url($imageId, $imageParams) : $directImageUrl;

    if ($deferred) {
        $thumbUrl = $resolvedImageUrl;
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
        if ($imageId > 0) {
            echo $this->ImageServe->picture(
                $imageId,
                $imageParams,
                [
                    'alt' => (string)($person->display ?? $person->first . ' ' . $person->last),
                    'class' => $cssClass,
                    'style' => $cssStyle,
                    'loading' => 'lazy',
                    'decoding' => 'async',
                ],
            );
        } else {
            echo $this->Html->image($resolvedImageUrl, [
                'alt' => (string)($person->display ?? $person->first . ' ' . $person->last),
                'class' => $cssClass,
                'style' => $cssStyle,
                'loading' => 'lazy',
                'decoding' => 'async',
                'width' => $width,
                'height' => $height,
            ]);
        }
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
