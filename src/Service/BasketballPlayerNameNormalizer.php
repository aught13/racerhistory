<?php
declare(strict_types=1);

namespace App\Service;

/**
 * Normalizes supported legacy basketball player-name formats.
 */
class BasketballPlayerNameNormalizer
{
    /**
     * Convert a comma-separated Last, First name to title-cased First Last form.
     *
     * Standalone and trailing Jr. components are removed. Ambiguous names are
     * returned unchanged so the importer can preserve the source value.
     *
     * @param string $name Source player name.
     * @return string Normalized name, or the original when it cannot be parsed.
     */
    public function normalize(string $name): string
    {
        $parts = array_map('trim', explode(',', trim($name)));
        $parts = array_values(array_filter(
            $parts,
            static fn(string $part): bool => $part !== '' && preg_match('/^jr\.?$/i', $part) !== 1,
        ));

        if (count($parts) !== 2) {
            return $name;
        }

        [$last, $first] = $parts;
        $last = preg_replace('/\s+jr\.?$/i', '', $last) ?? $last;
        $first = preg_replace('/\s+jr\.?$/i', '', $first) ?? $first;
        $last = trim($last);
        $first = trim($first);

        if ($last === '' || $first === '') {
            return $name;
        }

        return ucwords(strtolower($first . ' ' . $last));
    }
}
