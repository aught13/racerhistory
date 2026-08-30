<?php
declare(strict_types=1);

namespace App\View\Helper;

use Cake\View\Helper;

/**
 * SocialLinksHelper
 *
 * Renders an array or newline-separated string of social URLs into
 * badge links showing a small site icon and an @handle derived from
 * the last path segment (or domain) of the URL.
 */
class SocialLinksHelper extends Helper
{
    /**
     * Mapping of host -> Font Awesome brand class.
     *
     * @var array<string,string>
     */
    protected array $icons = [
        'twitter.com' => 'fa-brands fa-twitter',
        'x.com' => 'fa-brands fa-x-twitter',
        't.co' => 'fa-brands fa-twitter',
        'facebook.com' => 'fa-brands fa-facebook',
        'fb.com' => 'fa-brands fa-facebook',
        'instagram.com' => 'fa-brands fa-instagram',
        'threads.com' => 'fa-brands fa-threads',
        'threads.net' => 'fa-brands fa-threads',
        'youtube.com' => 'fa-brands fa-youtube',
        'youtu.be' => 'fa-brands fa-youtube',
        'github.com' => 'fa-brands fa-github',
        'linkedin.com' => 'fa-brands fa-linkedin',
        'linkedin.cn' => 'fa-brands fa-linkedin',
        'pinterest.com' => 'fa-brands fa-pinterest',
        'tiktok.com' => 'fa-brands fa-tiktok',
        'mastodon.social' => 'fa-brands fa-mastodon',
    ];

    /**
     * Render social links as HTML badges.
     *
     * @param array<string>|string|null $links
     * @return string HTML
     */
    public function render(mixed $links): string
    {
        $arr = [];
        if (is_string($links)) {
            $decoded = json_decode($links, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                $arr = $decoded;
            } else {
                $parts = preg_split("/\r\n|\n|\r/", (string)$links);
                $arr = array_values(array_filter(array_map('trim', $parts), fn($v) => $v !== ''));
            }
        } elseif (is_array($links)) {
            $arr = $links;
        } elseif ($links === null) {
            $arr = [];
        } else {
            $arr = [(string)$links];
        }

        if ($arr === []) {
            return '';
        }

        $out = '<div class="d-flex gap-2 mt-1 social-links">';
        foreach ($arr as $link) {
            $url = trim((string)$link);
            if ($url === '') {
                continue;
            }

            // Ensure URL has a scheme for parse_url
            if (!preg_match('#^[a-z][a-z0-9+.-]*://#i', $url)) {
                $url = 'https://' . ltrim($url, '/');
            }

            $parsed = parse_url($url);
            $host = strtolower($parsed['host'] ?? '');
            $path = $parsed['path'] ?? '';
            $path = rtrim($path, '/');

            $icon = $this->resolveIconClass($host);
            $label = $this->resolveLabel($host, $path);

            $out .=
                '<a href="' . h($url) . '" target="_blank" rel="noopener noreferrer" '
                . 'class="badge bg-primary text-decoration-none">';

            if ($icon !== null) {
                $out .= '<i class="' . $icon . ' me-1"></i>';
            }
            $out .= h($label);
            $out .= '</a>';
        }
        $out .= '</div>';

        return $out;
    }

    /**
     * Get a Font Awesome brand class for the host when it is known.
     *
     * @param string $host The host part of the URL
     * @return string|null The Font Awesome class or null if unknown
     */
    protected function resolveIconClass(string $host): ?string
    {
        $host = $this->normalizeHost($host);
        if ($host === '') {
            return null;
        }

        foreach ($this->icons as $knownHost => $iconClass) {
            if ($host === $knownHost || str_ends_with($host, '.' . $knownHost)) {
                return $iconClass;
            }
        }

        return null;
    }

    /**
     * Resolve the badge label. For a known brand we display the handle; otherwise show the hostname.
     *
     * @param string $host The host part of the URL
     * @param string $path The path part of the URL
     * @return string The label to display on the badge
     */
    protected function resolveLabel(string $host, string $path): string
    {
        $host = $this->normalizeHost($host);
        if ($host === '') {
            return '';
        }

        $handle = '';
        if ($path !== '') {
            $trimmedPath = rtrim($path, '/');
            if ($trimmedPath !== '') {
                $parts = explode('/', $trimmedPath);
                $handle = end($parts) ?: '';
            }
        }

        // If $path was empty or evaluated to an empty string after rtrim
        if ($handle === '') {
            $hp = explode('.', $host);
            if (count($hp) >= 2) {
                $handle = $hp[count($hp) - 2];
            } else {
                $handle = $host;
            }
        }

        if ($this->resolveIconClass($host) !== null) {
            return '@' . preg_replace('/[^A-Za-z0-9_.-]/', '', (string)$handle);
        }

        return $host;
    }

    /**
     * Normalize a host by lowercasing, trimming, and removing "www." prefix.
     *
     * @param string  $host
     * @return string Normalized host
     */
    protected function normalizeHost(string $host): string
    {
        $host = strtolower(trim($host, '.'));
        $host = preg_replace('/^www\./i', '', $host) ?? $host;

        return $host;
    }
}
