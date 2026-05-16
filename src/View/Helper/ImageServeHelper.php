<?php
declare(strict_types=1);

namespace App\View\Helper;

use Cake\View\Helper;
use DateTimeInterface;

/**
 * Helper for building public image serve URLs.
 *
 * Centralizes building `/images/serve/:id` URLs with common query params:
 * - w, h, fit, fm, q
 * - variant
 * - v (cache-busting / immutable caching)
 */
class ImageServeHelper extends Helper
{
    /**
     * Build the base path for an image id.
     *
     * @param string|int $id
     */
    public function path(int|string $id): string
    {
        $idInt = (int)$id;
        if ($idInt <= 0) {
            return '';
        }

        return '/images/serve/' . $idInt;
    }

    /**
     * Build a query string (including leading '?') for supported parameters.
     *
     * @param array<string, mixed> $params
     */
    public function query(array $params): string
    {
        $filtered = $this->filterParams($params);
        if ($filtered === []) {
            return '';
        }

        return '?' . http_build_query($filtered);
    }

    /**
     * Build a full public serve URL for an image id.
     *
     * @param string|int $id
     * @param array $params
     */
    public function url(int|string $id, array $params = []): string
    {
        $path = $this->path($id);
        if ($path === '') {
            return '';
        }

        return $path . $this->query($params);
    }

    /**
     * Build a public serve URL from an Image entity-like object.
     *
     * @param object $image An object with `id` and optionally `hash` and/or `modified`.
     * @param array<string, mixed> $params
     */
    public function urlForImage(object $image, array $params = []): string
    {
        $id = (int)($image->id ?? 0);
        if ($id <= 0) {
            return '';
        }

            return $this->url($id, $params);
    }

    /**
     * Generate a <picture> element with WebP source and fallback <img>.
     *
     * Supports responsive srcset for multiple sizes on different breakpoints.
     *
     * @param object|string|int $image Image ID or Image entity
     * @param array<string, mixed> $params URL parameters (w, h, fit, etc.)
     * @param array<string, mixed> $attrs HTML attributes for the <img> element
     * @return string HTML picture element
     */
    public function picture(int|string|object $image, array $params = [], array $attrs = []): string
    {
        if (is_object($image)) {
            $id = (int)($image->id ?? 0);
            // Auto-inject version from entity
            if (!isset($params['v']) && !empty($image->hash)) {
                $params['v'] = $image->hash;
            } elseif (!isset($params['v']) && ($image->modified ?? null) instanceof DateTimeInterface) {
                $params['v'] = (string)$image->modified->getTimestamp();
            }
        } else {
            $id = (int)$image;
        }

        if ($id <= 0) {
            return '';
        }

        // Build WebP URL
        $webpParams = array_merge($params, ['fm' => 'webp']);
        $webpUrl = $this->url($id, $webpParams);

        // Build fallback URL (without fm override)
        $fallbackUrl = $this->url($id, $params);

        // Default attributes
        $defaultAttrs = [
            'loading' => 'lazy',
            'decoding' => 'async',
            'class' => 'img-fluid',
        ];
        $mergedAttrs = array_merge($defaultAttrs, $attrs);

        // Build alt attribute
        $alt = $mergedAttrs['alt'] ?? '';
        unset($mergedAttrs['alt']);

        // Build attribute string
        $attrStr = $this->buildAttrString($mergedAttrs);

        return sprintf(
            '<picture><source srcset="%s" type="image/webp"><img src="%s" alt="%s"%s></picture>',
            htmlspecialchars($webpUrl, ENT_QUOTES, 'UTF-8'),
            htmlspecialchars($fallbackUrl, ENT_QUOTES, 'UTF-8'),
            htmlspecialchars($alt, ENT_QUOTES, 'UTF-8'),
            $attrStr,
        );
    }

    /**
     * Generate a responsive <picture> element with multiple srcset sizes.
     *
     * Creates WebP and fallback sources at different widths for responsive images.
     *
     * @param object|string|int $image Image ID or Image entity
     * @param array<int, int> $widths Array of widths to generate (e.g., [400, 800, 1200])
     * @param array<string, mixed> $params Additional URL parameters
     * @param array<string, mixed> $attrs HTML attributes for the <img> element
     * @return string HTML picture element with responsive srcset
     */
    public function responsivePicture(
        int|string|object $image,
        array $widths = [400, 800, 1200],
        array $params = [],
        array $attrs = [],
    ): string {
        if (is_object($image)) {
            $id = (int)($image->id ?? 0);
            if (!isset($params['v']) && !empty($image->hash)) {
                $params['v'] = $image->hash;
            } elseif (!isset($params['v']) && ($image->modified ?? null) instanceof DateTimeInterface) {
                $params['v'] = (string)$image->modified->getTimestamp();
            }
        } else {
            $id = (int)$image;
        }

        if ($id <= 0) {
            return '';
        }

        // Sort widths ascending
        sort($widths);

        // Build WebP srcset
        $webpSrcset = [];
        $fallbackSrcset = [];
        foreach ($widths as $w) {
            $wParams = array_merge($params, ['w' => $w]);
            $webpParams = array_merge($wParams, ['fm' => 'webp']);

            $webpSrcset[] = $this->url($id, $webpParams) . ' ' . $w . 'w';
            $fallbackSrcset[] = $this->url($id, $wParams) . ' ' . $w . 'w';
        }

        // Build sizes attribute (default responsive)
        $defaultSizes = '(max-width: 576px) 100vw, (max-width: 992px) 75vw, 50vw';
        $sizes = $attrs['sizes'] ?? $defaultSizes;
        unset($attrs['sizes']);

        // Default fallback src is the middle size
        $middleIndex = (int)floor(count($widths) / 2);
        $fallbackWidth = $widths[$middleIndex] ?? $widths[0];
        $fallbackUrl = $this->url($id, array_merge($params, ['w' => $fallbackWidth]));

        // Default attributes
        $defaultAttrs = [
            'loading' => 'lazy',
            'decoding' => 'async',
            'class' => 'img-fluid',
        ];
        $mergedAttrs = array_merge($defaultAttrs, $attrs);

        $alt = $mergedAttrs['alt'] ?? '';
        unset($mergedAttrs['alt']);

        $attrStr = $this->buildAttrString($mergedAttrs);

        return sprintf(
            '<picture>' .
            '<source srcset="%s" sizes="%s" type="image/webp">' .
            '<source srcset="%s" sizes="%s">' .
            '<img src="%s" alt="%s"%s>' .
            '</picture>',
            htmlspecialchars(implode(', ', $webpSrcset), ENT_QUOTES, 'UTF-8'),
            htmlspecialchars($sizes, ENT_QUOTES, 'UTF-8'),
            htmlspecialchars(implode(', ', $fallbackSrcset), ENT_QUOTES, 'UTF-8'),
            htmlspecialchars($sizes, ENT_QUOTES, 'UTF-8'),
            htmlspecialchars($fallbackUrl, ENT_QUOTES, 'UTF-8'),
            htmlspecialchars($alt, ENT_QUOTES, 'UTF-8'),
            $attrStr,
        );
    }

    /**
     * Build an HTML attribute string from an associative array.
     *
     * @param array<string, mixed> $attrs Attributes array
     * @return string Attribute string with leading space (or empty)
     */
    private function buildAttrString(array $attrs): string
    {
        if (empty($attrs)) {
            return '';
        }

        $parts = [];
        foreach ($attrs as $key => $value) {
            if ($value === null || $value === false) {
                continue;
            }
            if ($value === true) {
                $parts[] = htmlspecialchars($key, ENT_QUOTES, 'UTF-8');
            } else {
                $parts[] = sprintf(
                    '%s="%s"',
                    htmlspecialchars($key, ENT_QUOTES, 'UTF-8'),
                    htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'),
                );
            }
        }

        return $parts ? ' ' . implode(' ', $parts) : '';
    }

    /**
     * @param array<string, mixed> $params
     * @return array<string, string|int>
     */
    private function filterParams(array $params): array
    {
        $allowed = ['w', 'h', 'fit', 'fm', 'q', 'variant', 'v', '_ts'];
        $out = [];

        foreach ($allowed as $key) {
            if (!array_key_exists($key, $params)) {
                continue;
            }

            $value = $params[$key];
            if ($value === null || $value === '') {
                continue;
            }

            if (in_array($key, ['w', 'h', 'q'], true)) {
                if (!is_numeric($value)) {
                    continue;
                }
                $intVal = (int)$value;
                if ($intVal <= 0) {
                    continue;
                }
                $out[$key] = $intVal;
                continue;
            }

            $out[$key] = (string)$value;
        }

        return $out;
    }
}
