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
     * Mapping of host -> bootstrap-icon class
     *
     * @var array<string,string>
     */
    protected array $icons = [
        'twitter.com' => 'bi-twitter',
        't.co' => 'bi-twitter',
        'facebook.com' => 'bi-facebook',
        'instagram.com' => 'bi-instagram',
        'youtube.com' => 'bi-youtube',
        'youtu.be' => 'bi-youtube',
        'github.com' => 'bi-github',
        'linkedin.com' => 'bi-linkedin',
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

            $handle = '';
            if ($path !== '') {
                $parts = explode('/', $path);
                $handle = end($parts) ?: '';
            }
            if ($handle === '' && $host !== '') {
                $hp = explode('.', $host);
                if (count($hp) >= 2) {
                    $handle = $hp[count($hp) - 2];
                } else {
                    $handle = $host;
                }
            }

            $label = '@' . preg_replace('/[^A-Za-z0-9_.-]/', '', $handle);
            $icon = $this->icons[$host] ?? 'bi-link-45deg';

            $out .=
                '<a href="' . h($url) . '" target="_blank" rel="noopener noreferrer" '
                . 'class="badge bg-primary text-decoration-none">';
            $out .= '<i class="' . $icon . ' me-1"></i>' . h($label);
            $out .= '</a>';
        }
        $out .= '</div>';

        return $out;
    }
}
