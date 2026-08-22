<?php
declare(strict_types=1);

use Cake\Core\Configure;

/**
 * @var \App\View\AppView $this
 */

$adScript = trim((string)Configure::read('SiteOptions.ad_script', ''));
if ($adScript === '') {
    return;
}

if (!str_contains(strtolower($adScript), '<script')) {
    $adScript = '<script>' . $adScript . '</script>';
}
echo $adScript;
