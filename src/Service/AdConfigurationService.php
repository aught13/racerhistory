<?php
declare(strict_types=1);

namespace App\Service;

/**
 * Service layer for ad slot configuration normalization.
 *
 * Resolves persisted advertising campaigns into typed view and frontend payloads.
 */
class AdConfigurationService
{
    private AdvertisingService $advertisingService;

    /**
     * @param \App\Service\AdvertisingService|null $advertisingService
     */
    public function __construct(?AdvertisingService $advertisingService = null)
    {
        $this->advertisingService = $advertisingService ?? new AdvertisingService();
    }

    /**
     * Build the first normalized campaign for a named placement.
     *
     * @param string $slot
     * @param array<string,mixed> $context
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
    public function getSlotConfiguration(string $slot, array $context = []): array
    {
        $normalizedSlot = trim($slot);
        if ($normalizedSlot === '') {
            return $this->emptySlotConfiguration('');
        }

        return $this->getSlotConfigurations($normalizedSlot, $context)[0]
            ?? $this->emptySlotConfiguration($normalizedSlot);
    }

    /**
     * Build normalized frontend payloads for all matching campaigns in a placement.
     *
     * @param string $slot Placement key.
     * @param array<string,mixed> $context Current page sport/team context.
     * @return array<int,array{
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
     * }>
     */
    public function getSlotConfigurations(string $slot, array $context = []): array
    {
        $normalizedSlot = trim($slot);
        if ($normalizedSlot === '') {
            return [];
        }

        $settings = $this->advertisingService->getSettings();
        $configurations = [];
        $advertisements = $this->advertisingService->getAdvertisementsForPlacement($normalizedSlot, $context);
        foreach ($advertisements as $advertisement) {
            $html = trim((string)$advertisement->html);
            $configuredMode = strtolower(trim((string)$advertisement->mode));
            $hasExplicitMode = in_array($configuredMode, ['custom', 'google', 'gpt'], true);
            $mode = $hasExplicitMode ? $configuredMode : 'custom';
            $sizesDesktop = $this->parseSizes($advertisement->sizes_desktop ?? '');
            $sizesMobile = $this->parseSizes($advertisement->sizes_mobile ?? '');
            $gptUnitPath = trim((string)($advertisement->gpt_unit_path ?? ''));

            if (in_array($mode, ['custom', 'google'], true) && $html === '') {
                continue;
            }

            if ($mode === 'gpt' && $gptUnitPath === '') {
                if ($html === '') {
                    continue;
                }
                $mode = 'custom';
            }

            $googleClient = $this->extractAttribute($html, 'data-ad-client');
            if ($googleClient === '') {
                $googleClient = $this->advertisingService->normalizePublisherId($settings['publisher_id']);
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
                $mode = 'custom';
            }

            $configurations[] = $this->buildConfiguration(
                $normalizedSlot,
                $mode,
                $html,
                $sizesDesktop,
                $sizesMobile,
                $gptUnitPath,
                $mode === 'google' ? $googleSlotId : '',
                $googleClient,
                $googleFormat,
                $googleLayout,
                $googleLayoutKey,
                $googleFullWidthResponsive,
            );
        }

        return $configurations;
    }

    /**
     * Return the site-wide provider script.
     */
    public function getGlobalScript(): string
    {
        return $this->advertisingService->getSettings()['global_script'];
    }

    /**
     * Resolve sport/team context from the current view variables.
     *
     * @param array<string,mixed> $viewVars Template variables.
     * @return array{sportKey:string|null,teamId:int|null}
     */
    public function resolvePageContext(array $viewVars): array
    {
        return $this->advertisingService->resolvePageContext($viewVars);
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
}
