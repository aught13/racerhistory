<?php
/**
 * Team Season Image Element
 *
 * Displays a team season image using the Images controller serve endpoint (same pattern as person_image).
 *
 * Variables:
 * - $teamSeason: TeamSeason entity with team_season_image field (numeric id referencing Images table)
 * - $size: Optional size ('small','medium','large') default medium
 * - $variant: Optional image variant query param
 * - $class: Optional CSS classes
 * - $style: Optional inline styles
 */

$size = $size ?? 'medium';
$variant = $variant ?? '';
$class = $class ?? '';
$style = $style ?? '';

$sizeMap = [
    'small' => ['width' => 48, 'height' => 48, 'class' => 'rounded'],
    'medium' => ['width' => 120, 'height' => 120, 'class' => 'rounded'],
    'large' => ['width' => 240, 'height' => 240, 'class' => 'rounded'],
];
$sizeConfig = $sizeMap[$size] ?? $sizeMap['medium'];
$width = $sizeConfig['width'];
$height = $sizeConfig['height'];
$defaultClass = $sizeConfig['class'];
$cssClass = trim($defaultClass . ' ' . $class);
$cssStyle = "width: {$width}px; height: {$height}px; object-fit: cover; " . $style;

if (!empty($teamSeason->team_season_image) && is_numeric($teamSeason->team_season_image)) {
    $params = [
        'w' => $width,
        'h' => $height,
        'fit' => 'cover',
    ];
    if ($variant) {
        $params = ['variant' => $variant] + $params;
    }
    $imageUrl = $this->ImageServe->url((int)$teamSeason->team_season_image, $params);
    echo '<img src="' . h($imageUrl) . '" alt="Season image" class="' . h($cssClass) . '" style="' . h($cssStyle) . '" loading="lazy" decoding="async">';
} else {
    echo $this->Html->div('placeholder-image ' . $cssClass,
        $this->Html->tag('span', 'TS', [
            'class' => 'd-flex align-items-center justify-content-center h-100 bg-secondary text-white fw-bold',
            'style' => 'font-size: ' . ($width * 0.4) . 'px;'
        ]),
        ['style' => $cssStyle . ' background-color: #6c757d;']
    );
}
?>
