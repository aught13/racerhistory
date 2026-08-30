<?php
declare(strict_types=1);

namespace App\Service;

class SocialImageService
{
    /**
     * Filter candidate image URLs to remove ad-hosted placeholders and keep real content images.
     *
     * @param array<int,mixed> $candidates
     * @return array<int,string>
     */
    public static function filterCandidates(array $candidates): array
    {
        $clean = [];

        foreach ($candidates as $candidate) {
            $value = trim((string)$candidate);
            if ($value === '' || $value === '0') {
                continue;
            }

            if (self::isAdCandidate($value)) {
                continue;
            }

            $clean[] = $value;
        }

        return $clean;
    }

    /**
     * Determine whether a candidate is ad-hosted or otherwise a non-content slot image.
     *
     * @param string $url
     * @return bool
     */
    public static function isAdCandidate(string $url): bool
    {
        $normalized = strtolower(trim($url));
        if ($normalized === '') {
            return false;
        }

        $adPatterns = [
            'pagead2.googlesyndication.com',
            'googlesyndication.com',
            'googleads.g.doubleclick.net',
            'ad.doubleclick.net',
            'ads.example.com',
            'ads.',
            '/pagead/',
            '/ads?',
            '/ad?',
            'adsbygoogle',
            'adslot',
            'leaderboard',
            'banner-ad',
            'promo-banner',
            'ad-creative',
        ];

        foreach ($adPatterns as $pattern) {
            if (str_contains($normalized, $pattern)) {
                return true;
            }
        }

        return false;
    }
}
