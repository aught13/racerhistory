<?php
declare(strict_types=1);

namespace App\Service;

/**
 * Service layer for ad slot configuration normalization.
 *
 * Reads ad slot configuration from runtime SiteOptions and exposes a typed
 * payload used by the view helper and frontend lifecycle controller.
 */
class AdConfigurationService
{
    private SiteOptionsService $siteOptionsService;

    /**
     * @param \App\Service\SiteOptionsService|null $siteOptionsService
     */
    public function __construct(?SiteOptionsService $siteOptionsService = null)
    {
        $this->siteOptionsService = $siteOptionsService ?? new SiteOptionsService();
    }

    /**
     * Build normalized slot configuration for a named ad placement.
     *
     * @param string $slot
     * @return array{
     *   slot:string,
     *   active:bool,
     *   mode:string,
     *   html:string,
     *   sizes_desktop:array<int,array{0:int,1:int}>,
     *   sizes_mobile:array<int,array{0:int,1:int}>,
     *   gpt_unit_path:string,
     *   google_slot_id:string,
     *   google_client:string,
     *   google_format:string,
     *   google_layout:string,
     *   google_layout_key:string,
     *   google_full_width_responsive:string
     * }
     */
    public function getSlotConfiguration(string $slot): array
    {
        $normalizedSlot = trim($slot);
        if ($normalizedSlot === '') {
            return $this->emptySlotConfiguration('');
        }

        $settings = $this->siteOptionsService->getRuntimeSettings();
        $prefix = 'ad_' . $normalizedSlot;
        $active = $this->toBool($settings[$prefix . '_active'] ?? false);
        $html = trim((string)($settings[$prefix . '_html'] ?? ''));
        $configuredMode = strtolower(trim((string)($settings[$prefix . '_mode'] ?? '')));
        $legacyGoogleMode = $this->toBool($settings[$prefix . '_google_mode'] ?? false);
        $sizesDesktop = $this->parseSizes($settings[$prefix . '_sizes_desktop'] ?? '');
        $sizesMobile = $this->parseSizes($settings[$prefix . '_sizes_mobile'] ?? '');
        $gptUnitPath = trim((string)($settings[$prefix . '_gpt_unit_path'] ?? ''));

        if (!$active) {
            return $this->emptySlotConfiguration($normalizedSlot);
        }

        $hasExplicitMode = in_array($configuredMode, ['custom', 'google', 'gpt'], true);
        $mode = $hasExplicitMode
            ? $configuredMode
            : ($legacyGoogleMode ? 'google' : 'custom');

        if (in_array($mode, ['custom', 'google'], true) && $html === '') {
            return $this->emptySlotConfiguration($normalizedSlot);
        }

        if ($mode === 'gpt' && $gptUnitPath === '') {
            return $html === ''
                ? $this->emptySlotConfiguration($normalizedSlot)
                : $this->buildConfiguration(
                    $normalizedSlot,
                    'custom',
                    $html,
                    $sizesDesktop,
                    $sizesMobile,
                    $gptUnitPath,
                    '',
                    '',
                    '',
                    '',
                    '',
                    '',
                );
        }

        $publisherId = trim((string)($settings['ad_publisher_id'] ?? ''));
        $googleClient = $this->extractAttribute($html, 'data-ad-client');
        if ($googleClient === '') {
            $googleClient = $this->normalizePublisherId($publisherId);
        }

        $googleSlotId = $this->extractNumericAttribute($html, 'data-ad-slot');
        $googleFormat = $this->extractAttribute($html, 'data-ad-format');
        $googleLayout = $this->extractAttribute($html, 'data-ad-layout');
        $googleLayoutKey = $this->extractAttribute($html, 'data-ad-layout-key');
        $googleFullWidthResponsive = $this->extractAttribute($html, 'data-full-width-responsive');

        $hasGoogleMarkup = preg_match(
            '/<ins\b[^>]*\bclass\s*=\s*(["\'])[^"\']*\badsbygoogle\b[^"\']*\1/i',
            $html,
        ) === 1;
        if (!$hasExplicitMode && $hasGoogleMarkup) {
            $mode = 'google';
        }

        if ($mode === 'google' && $googleSlotId === '') {
            return $this->buildConfiguration(
                $normalizedSlot,
                'custom',
                $html,
                $sizesDesktop,
                $sizesMobile,
                $gptUnitPath,
                '',
                $googleClient,
                $googleFormat,
                $googleLayout,
                $googleLayoutKey,
                $googleFullWidthResponsive,
            );
        }

        return $this->buildConfiguration(
            $normalizedSlot,
            $mode,
            $html,
            $sizesDesktop,
            $sizesMobile,
            $gptUnitPath,
            $googleSlotId,
            $googleClient,
            $googleFormat,
            $googleLayout,
            $googleLayoutKey,
            $googleFullWidthResponsive,
        );
    }

    /**
     * @param string $slot
     * @return array{
     *   slot:string,
     *   active:bool,
     *   mode:string,
     *   html:string,
     *   sizes_desktop:array<int,array{0:int,1:int}>,
     *   sizes_mobile:array<int,array{0:int,1:int}>,
     *   gpt_unit_path:string,
     *   google_slot_id:string,
     *   google_client:string,
     *   google_format:string,
     *   google_layout:string,
     *   google_layout_key:string,
     *   google_full_width_responsive:string
     * }
     */
    private function emptySlotConfiguration(string $slot): array
    {
        return [
            'slot' => $slot,
            'active' => false,
            'mode' => 'custom',
            'html' => '',
            'sizes_desktop' => [],
            'sizes_mobile' => [],
            'gpt_unit_path' => '',
            'google_slot_id' => '',
            'google_client' => '',
            'google_format' => '',
            'google_layout' => '',
            'google_layout_key' => '',
            'google_full_width_responsive' => '',
        ];
    }

    /**
     * @param string $slot
     * @param string $mode
     * @param string $html
     * @param array<int,array{0:int,1:int}> $sizesDesktop
     * @param array<int,array{0:int,1:int}> $sizesMobile
     * @param string $gptUnitPath
     * @param string $googleSlotId
     * @param string $googleClient
     * @param string $googleFormat
     * @param string $googleLayout
     * @param string $googleLayoutKey
     * @param string $googleFullWidthResponsive
     * @return array<string,mixed>
     */
    private function buildConfiguration(
        string $slot,
        string $mode,
        string $html,
        array $sizesDesktop,
        array $sizesMobile,
        string $gptUnitPath,
        string $googleSlotId,
        string $googleClient,
        string $googleFormat,
        string $googleLayout,
        string $googleLayoutKey,
        string $googleFullWidthResponsive,
    ): array {
        return [
            'slot' => $slot,
            'active' => true,
            'mode' => $mode,
            'html' => $html,
            'sizes_desktop' => $sizesDesktop,
            'sizes_mobile' => $sizesMobile,
            'gpt_unit_path' => $gptUnitPath,
            'google_slot_id' => $googleSlotId,
            'google_client' => $googleClient,
            'google_format' => $googleFormat,
            'google_layout' => $googleLayout,
            'google_layout_key' => $googleLayoutKey,
            'google_full_width_responsive' => $googleFullWidthResponsive,
        ];
    }

    /**
     * @param mixed $value
     * @return array<int,array{0:int,1:int}>
     */
    private function parseSizes(mixed $value): array
    {
        if (is_string($value)) {
            $value = trim($value);
            if ($value === '') {
                return [];
            }

            if (str_starts_with($value, '[')) {
                $decoded = json_decode($value, true);
                if (!is_array($decoded)) {
                    return [];
                }
                $value = $decoded;
            } else {
                $value = preg_split('/\s*,\s*/', $value, -1, PREG_SPLIT_NO_EMPTY) ?: [];
            }
        }

        if (!is_array($value)) {
            return [];
        }

        $sizes = [];
        foreach ($value as $size) {
            if (is_string($size) && preg_match('/^(\d+)\s*x\s*(\d+)$/i', trim($size), $matches) === 1) {
                $size = [$matches[1], $matches[2]];
            }

            if (!is_array($size) || count($size) < 2) {
                continue;
            }

            $dimensions = array_values($size);
            if (!is_numeric($dimensions[0]) || !is_numeric($dimensions[1])) {
                continue;
            }

            $width = (int)$dimensions[0];
            $height = (int)$dimensions[1];
            if ($width < 1 || $height < 1) {
                continue;
            }

            $sizes[] = [$width, $height];
        }

        return $sizes;
    }

    /**
     * @param mixed $value
     */
    private function toBool(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        if (is_numeric($value)) {
            return (int)$value === 1;
        }

        if (is_string($value)) {
            $normalized = strtolower(trim($value));

            return in_array($normalized, ['1', 'true', 'yes', 'on'], true);
        }

        return false;
    }

    /**
     * @param string $html
     * @param string $attribute
     */
    private function extractAttribute(string $html, string $attribute): string
    {
        $pattern = '/\b' . preg_quote($attribute, '/') . '\s*=\s*(["\'])([^"\']+)\1/i';
        if (preg_match($pattern, $html, $matches) !== 1) {
            return '';
        }

        return trim((string)($matches[2] ?? ''));
    }

    /**
     * @param string $html
     * @param string $attribute
     */
    private function extractNumericAttribute(string $html, string $attribute): string
    {
        $value = $this->extractAttribute($html, $attribute);
        if ($value !== '' && ctype_digit($value)) {
            return $value;
        }

        return '';
    }

    /**
     * @param string $publisherId
     */
    private function normalizePublisherId(string $publisherId): string
    {
        $candidate = trim($publisherId);
        if ($candidate === '') {
            return '';
        }

        if (ctype_digit($candidate)) {
            return 'ca-pub-' . $candidate;
        }

        if (preg_match('/^ca-pub-\d+$/i', $candidate) === 1) {
            return strtolower($candidate);
        }

        if (preg_match('/^pub-\d+$/i', $candidate) === 1) {
            return 'ca-' . strtolower($candidate);
        }

        if (preg_match('/ca-pub-\d+/i', $candidate, $matches) === 1) {
            return strtolower((string)$matches[0]);
        }

        return $candidate;
    }
}
