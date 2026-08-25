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
        $active = $this->toBool($settings['ad_' . $normalizedSlot . '_active'] ?? false);
        $html = trim((string)($settings['ad_' . $normalizedSlot . '_html'] ?? ''));
        $googleModeEnabled = $this->toBool($settings['ad_' . $normalizedSlot . '_google_mode'] ?? false);

        if (!$active || $html === '') {
            return $this->emptySlotConfiguration($normalizedSlot);
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

        $googleRenderable = $googleModeEnabled && $googleSlotId !== '';

        return [
            'slot' => $normalizedSlot,
            'active' => true,
            'mode' => $googleRenderable ? 'google' : 'custom',
            'html' => $html,
            'google_slot_id' => $googleSlotId,
            'google_client' => $googleClient,
            'google_format' => $googleFormat,
            'google_layout' => $googleLayout,
            'google_layout_key' => $googleLayoutKey,
            'google_full_width_responsive' => $googleFullWidthResponsive,
        ];
    }

    /**
     * @param string $slot
     * @return array{
     *   slot:string,
     *   active:bool,
     *   mode:string,
     *   html:string,
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
            'google_slot_id' => '',
            'google_client' => '',
            'google_format' => '',
            'google_layout' => '',
            'google_layout_key' => '',
            'google_full_width_responsive' => '',
        ];
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
