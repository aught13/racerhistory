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
     * @param array<string, mixed> $params
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
     * Automatically injects `v` from `hash` or `modified` when not explicitly provided.
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

        if (!array_key_exists('v', $params) || $params['v'] === null || $params['v'] === '') {
            $v = '';
            if (!empty($image->hash) && is_string($image->hash)) {
                $v = $image->hash;
            } elseif (($image->modified ?? null) instanceof DateTimeInterface) {
                $v = (string)$image->modified->getTimestamp();
            }
            if ($v !== '') {
                $params['v'] = $v;
            }
        }

        return $this->url($id, $params);
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
