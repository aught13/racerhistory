<?php
declare(strict_types=1);

/**
 * @var \App\View\AppView $this
 */

$adScript = trim($this->Ad->globalScript());
if ($adScript === '') {
    return;
}

if (!str_contains(strtolower($adScript), '<script')) {
    $adScript = '<script>' . $adScript . '</script>';
}
echo $adScript;
