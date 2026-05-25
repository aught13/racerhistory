<?php
declare(strict_types=1);

namespace App\View\Helper;

use App\Model\Entity\Image;
use App\Service\ImageUrlService;
use Cake\View\Helper;
use Cake\View\View;

/**
 * Helper for building direct public URLs for stored images.
 *
 * Variant and profile arguments are reduced to stored variants only.
 */
class ImageServeHelper extends Helper
{
    /**
     * @var \App\Service\ImageUrlService
     */
    private ImageUrlService $imageUrlService;

    /**
     * @param \Cake\View\View $View
     * @param array<string,mixed> $config
     */
    public function __construct(View $View, array $config = [])
    {
        parent::__construct($View, $config);
        $this->imageUrlService = new ImageUrlService();
    }

    /**
     * Build the base path for an image id.
     *
     * @param string|int $id
     */
    public function path(int|string $id): string
    {
        return $this->imageUrlService->urlForId($id);
    }

    /**
     * Build a query string (including leading '?') for supported parameters.
     *
     * @param array<string, mixed> $params
     */
    public function query(array $params): string
    {
        return $this->imageUrlService->cacheBustQuery($params);
    }

    /**
     * Build a full public serve URL for an image id.
     *
     * @param string|int $id
     * @param array $params
     */
    public function url(int|string $id, array $params = []): string
    {
        return $this->imageUrlService->urlForId($id, $params);
    }

    /**
     * Build a public serve URL from an Image entity-like object.
     *
     * @param object $image An object with `id`.
     * @param array<string, mixed> $params
     */
    public function urlForImage(object $image, array $params = []): string
    {
        if ($image instanceof Image) {
            return $this->imageUrlService->urlForImage($image, $params);
        }

        $id = (int)($image->id ?? 0);

        return $this->url($id, $params);
    }

    /**
     * Generate a <picture> element around a stored image URL.
     *
     * @param object|string|int $image Image ID or Image entity
     * @param array<string, mixed> $params URL parameters (w, h, fit, variant, profile, etc.)
     * @param array<string, mixed> $attrs HTML attributes for the <img> element
     * @return string HTML picture element
     */
    public function picture(int|string|object $image, array $params = [], array $attrs = []): string
    {
        $url = $this->resolveUrl($image, $params);
        if ($url === '') {
            return '';
        }

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
            '<picture><img src="%s" alt="%s"%s></picture>',
            htmlspecialchars($url, ENT_QUOTES, 'UTF-8'),
            htmlspecialchars($alt, ENT_QUOTES, 'UTF-8'),
            $attrStr,
        );
    }

    /**
     * Generate a stored-image <picture> wrapper.
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
        unset($widths);
        $defaultAttrs = [
            'loading' => 'lazy',
            'decoding' => 'async',
            'class' => 'img-fluid',
        ];
        $mergedAttrs = array_merge($defaultAttrs, $attrs);

        $alt = $mergedAttrs['alt'] ?? '';
        unset($mergedAttrs['alt']);

        $attrStr = $this->buildAttrString($mergedAttrs);

        $url = $this->resolveUrl($image, $params);
        if ($url === '') {
            return '';
        }

        return sprintf(
            '<picture><img src="%s" alt="%s"%s></picture>',
            htmlspecialchars($url, ENT_QUOTES, 'UTF-8'),
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
     * @param object|string|int $image
     * @param array<string,mixed> $params
     * @return string
     */
    private function resolveUrl(int|string|object $image, array $params): string
    {
        if ($image instanceof Image) {
            return $this->imageUrlService->urlForImage($image, $params);
        }

        if (is_object($image)) {
            $id = (int)($image->id ?? 0);

            return $this->imageUrlService->urlForId($id, $params);
        }

        return $this->imageUrlService->urlForId($image, $params);
    }
}
