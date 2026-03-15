<?php
declare(strict_types=1);

/**
 * Import map for browser-native ES modules.
 *
 * This is consumed by `HtmlHelper::importmap()` in the layout.
 *
 * @return array{imports: array<string, string>}
 */
return [
    'imports' => [
        // Hotwire (via ESM CDN).
        '@hotwired/turbo' => 'https://esm.sh/@hotwired/turbo@8.0.13',
        '@hotwired/stimulus' => 'https://esm.sh/@hotwired/stimulus@3.2.2',

        // Hotwire Native Bridge (optional, used when embedded in iOS/Android WebViews).
        '@hotwired/hotwire-native-bridge' => 'https://esm.sh/@hotwired/hotwire-native-bridge@1.0.0',
    ],
];
