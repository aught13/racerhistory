<?php
declare(strict_types=1);

namespace App\View\Helper;

use Cake\Core\Configure;
use Cake\View\Helper;

/**
 * Helper for building public image serve URLs.
 *
 * Centralizes building `/images/serve/:id` URLs with common query params:
 * - w, h, fit, fm, q
 * - variant
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
     * @param object $image An object with `id`.
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
        } else {
            $id = (int)$image;
        }

        if ($id <= 0) {
            return '';
        }

        // Build WebP URL and fallback URL params based on variant config.
        [$webpParams, $fallbackParams] = $this->buildPictureParams($params);
        $webpUrl = $this->url($id, $webpParams);

        // Build fallback URL
        $fallbackUrl = $this->url($id, $fallbackParams);

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
        } else {
            $id = (int)$image;
        }

        if ($id <= 0) {
            return '';
        }

        // Sort widths ascending
        sort($widths);

        $variantName = isset($params['variant']) ? (string)$params['variant'] : '';
        $variantConfig = $this->getVariantConfig($variantName);
        $fallbackBaseParams = $this->buildVariantFallbackParams($params, $variantConfig);

        // Build WebP srcset
        $webpSrcset = [];
        $fallbackSrcset = [];
        foreach ($widths as $w) {
            $wParams = array_merge($params, ['w' => $w]);
            $webpParams = array_merge($wParams, ['fm' => 'webp']);

            // Avoid forcing a transform when variant output is already WebP.
            if ($variantName !== '' && ($variantConfig['format'] ?? null) === 'webp') {
                unset($webpParams['fm']);
            }

            $fallbackWParams = array_merge($fallbackBaseParams, ['w' => $w]);

            $webpSrcset[] = $this->url($id, $webpParams) . ' ' . $w . 'w';
            $fallbackSrcset[] = $this->url($id, $fallbackWParams) . ' ' . $w . 'w';
        }

        // Build sizes attribute (default responsive)
        $defaultSizes = '(max-width: 576px) 100vw, (max-width: 992px) 75vw, 50vw';
        $sizes = $attrs['sizes'] ?? $defaultSizes;
        unset($attrs['sizes']);

        // Default fallback src is the middle size
        $middleIndex = (int)floor(count($widths) / 2);
        $fallbackWidth = $widths[$middleIndex] ?? $widths[0];
        $fallbackUrl = $this->url($id, array_merge($fallbackBaseParams, ['w' => $fallbackWidth]));

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
     * Build WebP and fallback params for picture rendering.
     *
     * When a selected variant is configured as WebP, keep that variant URL as the WebP source
     * and derive a fallback from variant dimensions so non-WebP browsers still get an image.
     *
     * @param array<string, mixed> $params
     * @return array{0:array<string,mixed>,1:array<string,mixed>}
     */
    private function buildPictureParams(array $params): array
    {
        $webpParams = $params;
        $fallbackParams = $params;

        $variantName = isset($params['variant']) ? (string)$params['variant'] : '';
        $variantConfig = $this->getVariantConfig($variantName);
        $variantFormat = isset($variantConfig['format']) ? (string)$variantConfig['format'] : '';

        if ($variantName !== '' && $variantFormat === 'webp') {
            unset($webpParams['fm']);
            $fallbackParams = $this->buildVariantFallbackParams($params, $variantConfig);

            return [$webpParams, $fallbackParams];
        }

        $webpParams['fm'] = 'webp';

        return [$webpParams, $fallbackParams];
    }

    /**
     * Resolve variant config from app config.
     *
     * @param string $variantName
     * @return array<string, mixed>
     */
    private function getVariantConfig(string $variantName): array
    {
        if ($variantName === '') {
            return [];
        }

        $variants = (array)Configure::read('Images.variants', []);
        $variantConfig = $variants[$variantName] ?? null;

        return is_array($variantConfig) ? $variantConfig : [];
    }

    /**
     * Convert a variant-backed request into transform params suitable for non-WebP fallback.
     *
     * @param array<string, mixed> $params
     * @param array<string, mixed> $variantConfig
     * @return array<string, mixed>
     */
    private function buildVariantFallbackParams(array $params, array $variantConfig): array
    {
        $fallbackParams = $params;

        // Fallback should target the original with equivalent transforms, not a WebP-only variant.
        unset($fallbackParams['variant']);

        $fit = $variantConfig['fit'] ?? null;
        if (is_array($fit) && count($fit) >= 2) {
            $fitW = is_numeric($fit[0]) ? (int)$fit[0] : null;
            $fitH = is_numeric($fit[1]) ? (int)$fit[1] : null;

            if ($fitW !== null && $fitW > 0 && !isset($fallbackParams['w'])) {
                $fallbackParams['w'] = $fitW;
            }
            if ($fitH !== null && $fitH > 0 && !isset($fallbackParams['h'])) {
                $fallbackParams['h'] = $fitH;
            }
            if (!isset($fallbackParams['fit'])) {
                $fallbackParams['fit'] = 'cover';
            }
        }

        $maxWidth = $variantConfig['maxWidth'] ?? null;
        if (is_numeric($maxWidth) && (int)$maxWidth > 0 && !isset($fallbackParams['w'])) {
            $fallbackParams['w'] = (int)$maxWidth;
        }

        $maxHeight = $variantConfig['maxHeight'] ?? null;
        if (is_numeric($maxHeight) && (int)$maxHeight > 0 && !isset($fallbackParams['h'])) {
            $fallbackParams['h'] = (int)$maxHeight;
        }

        return $fallbackParams;
    }

    /**
     * @param array<string, mixed> $params
     * @return array<string, string|int>
     */
    private function filterParams(array $params): array
    {
        $allowed = ['w', 'h', 'fit', 'fm', 'q', 'variant', '_ts'];
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
