<?php
declare(strict_types=1);

namespace App\Service;

/**
 * Admin service for sport configuration management.
 *
 * Reads static sport defaults from Configure via SportConfigService and persists
 * only per-sport runtime overrides into SiteOptions.
 */
class SportConfigAdminService
{
    private SportConfigService $sportConfigService;

    /**
     * @param \App\Service\SportConfigService|null $sportConfigService
     */
    public function __construct(?SportConfigService $sportConfigService = null)
    {
        $this->sportConfigService = $sportConfigService ?? new SportConfigService();
    }

    /**
     * @return array<string,string>
     */
    public function getAvailableSports(): array
    {
        return $this->sportConfigService->getAvailableSports();
    }

    /**
     * @param int $sportId
     * @return string|null
     */
    public function getSportKeyForId(int $sportId): ?string
    {
        return $this->sportConfigService->getKeyById($sportId);
    }

    /**
     * @param string|int $sport
     * @return string
     */
    public function getSportDisplayName(string|int $sport): string
    {
        $sportKey = $this->resolveSportKey($sport);

        return $sportKey !== null
            ? $this->sportConfigService->getSportDisplayName($sportKey)
            : '';
    }

    /**
     * @param string|int $sport
     * @return array<string,mixed>
     */
    public function getFormattedConfigsForSport(string|int $sport): array
    {
        $sportKey = $this->resolveSportKey($sport);
        if ($sportKey === null) {
            return [
                'period_names' => [],
                'officials' => ['value' => [], 'description' => ''],
                'settings' => [],
            ];
        }

        return $this->sportConfigService->getFormattedConfigsForSport($sportKey);
    }

    /**
     * Persist config payload from edit form as SiteOptions override.
     *
     * @param string|int $sport
     * @param array<string,mixed> $configData
     */
    public function saveBulkConfigs(string|int $sport, array $configData): bool
    {
        $sportKey = $this->resolveSportKey($sport);
        if ($sportKey === null) {
            return false;
        }

        $merged = $this->sportConfigService->getMergedConfig($sportKey);
        $updated = $this->buildEditableConfig($merged, $sportKey);
        $descriptions = isset($updated['_descriptions']) && is_array($updated['_descriptions'])
            ? $updated['_descriptions']
            : [];

        $periodNames = [];
        $periodDescriptions = [];
        $periodRowsProvided = false;

        foreach ($configData as $key => $data) {
            if (!is_string($key)) {
                continue;
            }

            if (!str_starts_with($key, 'period_name_')) {
                continue;
            }

            $periodRowsProvided = true;

            $periods = null;
            if (preg_match('/^period_name_(\d+)$/', $key, $matches) === 1) {
                $periods = (int)$matches[1];
            }

            if (
                str_starts_with($key, 'period_name_new_')
                && is_array($data)
                && isset($data['periods'])
                && is_numeric((string)$data['periods'])
            ) {
                $periods = (int)$data['periods'];
            }

            if ($periods === null || $periods <= 0) {
                continue;
            }

            $rawValue = is_array($data) ? ($data['value'] ?? null) : $data;
            $periodName = trim((string)$rawValue);
            if ($periodName === '') {
                continue;
            }

            $periodNames[$periods] = $periodName;

            if (is_array($data)) {
                $periodDescription = trim((string)($data['description'] ?? ''));
                if ($periodDescription !== '') {
                    $periodDescriptions['period_name_' . $periods] = $periodDescription;
                }
            }
        }

        if ($periodRowsProvided) {
            ksort($periodNames);
            $updated['period_names'] = $periodNames;

            foreach (array_keys($descriptions) as $descriptionKey) {
                if (!is_string($descriptionKey)) {
                    continue;
                }
                if (str_starts_with($descriptionKey, 'period_name_')) {
                    unset($descriptions[$descriptionKey]);
                }
            }

            foreach ($periodDescriptions as $descriptionKey => $descriptionValue) {
                $descriptions[$descriptionKey] = $descriptionValue;
            }
        }

        if (isset($configData['officials']) && is_array($configData['officials'])) {
            $rawOfficials = $configData['officials']['value'] ?? [];
            $updated['officials'] = $this->normalizeStringList($rawOfficials);

            $officialsDescription = trim((string)($configData['officials']['description'] ?? ''));
            if ($officialsDescription === '') {
                unset($descriptions['officials']);
            } else {
                $descriptions['officials'] = $officialsDescription;
            }
        }

        foreach ($configData as $key => $data) {
            if (!is_string($key)) {
                continue;
            }
            if (str_starts_with($key, 'period_name_') || $key === 'officials') {
                continue;
            }

            $actualKey = $key;
            $value = $data;

            if (is_array($data) && isset($data['value'])) {
                $value = $data['value'];
                if (isset($data['key']) && is_string($data['key']) && trim($data['key']) !== '') {
                    $actualKey = trim($data['key']);
                }
            }

            if (str_starts_with($actualKey, 'new_setting_')) {
                continue;
            }

            $updated[$actualKey] = $this->normalizeSettingValue($actualKey, $value);

            if (is_array($data)) {
                $settingDescription = trim((string)($data['description'] ?? ''));
                if ($settingDescription === '') {
                    unset($descriptions[$actualKey]);
                } else {
                    $descriptions[$actualKey] = $settingDescription;
                }
            }
        }

        if ($periodRowsProvided && !isset($updated['supports_periods'])) {
            $updated['supports_periods'] = array_map('intval', array_keys((array)$updated['period_names']));
        }

        $updated['supports_periods'] = $this->normalizeSupportsPeriodsValue(
            $updated['supports_periods'] ?? [],
            (array)$updated['period_names'],
        );

        $defaultPeriods = (int)($updated['default_periods'] ?? 0);
        if ($defaultPeriods <= 0) {
            $defaultPeriods = 4;
        }
        if (
            is_array($updated['supports_periods'])
            && $updated['supports_periods'] !== []
            && !in_array($defaultPeriods, $updated['supports_periods'], true)
        ) {
            $defaultPeriods = (int)$updated['supports_periods'][0];
        }
        $updated['default_periods'] = $defaultPeriods;

        if (!isset($updated['overtime_name']) || trim((string)$updated['overtime_name']) === '') {
            $updated['overtime_name'] = 'OT';
        }

        if ($descriptions === []) {
            unset($updated['_descriptions']);
        } else {
            $updated['_descriptions'] = $descriptions;
        }

        return $this->sportConfigService->saveMergedConfig($sportKey, $updated);
    }

    /**
     * @param string|int $sport
     * @return array<string,mixed>
     */
    public function getDefaultConfigTemplate(string|int $sport): array
    {
        $sportKey = $this->resolveSportKey($sport) ?? $this->sportConfigService->getDefaultSportKey();
        $defaults = $this->sportConfigService->getDefaultConfig($sportKey);

        $template = [];

        foreach ((array)($defaults['period_names'] ?? []) as $periods => $periodName) {
            $template['period_name_' . $periods] = [
                'value' => (string)$periodName,
                'description' => '',
            ];
        }

        $template['officials'] = [
            'value' => $this->normalizeStringList($defaults['officials'] ?? []),
            'description' => '',
        ];

        $settings = [
            'sport_active',
            'has_stats',
            'stats_tracked',
            'overtime_name',
            'default_periods',
            'supports_periods',
            'scoring_type',
        ];

        foreach ($settings as $settingKey) {
            if (!array_key_exists($settingKey, $defaults)) {
                continue;
            }

            $template[$settingKey] = [
                'value' => $defaults[$settingKey],
                'description' => '',
            ];
        }

        return $template;
    }

    /**
     * @param array<string,mixed> $formatted
     * @param string|int|null $sport
     * @return array<string,mixed>
     */
    public function normalizeFormattedConfigs(array $formatted, string|int|null $sport = null): array
    {
        $formatted['period_names'] = isset($formatted['period_names']) && is_array($formatted['period_names'])
            ? $formatted['period_names']
            : [];

        if (!isset($formatted['officials']) || !is_array($formatted['officials'])) {
            $formatted['officials'] = ['value' => [], 'description' => ''];
        }
        if (!array_key_exists('value', $formatted['officials'])) {
            $formatted['officials']['value'] = [];
        }

        $formatted['settings'] = isset($formatted['settings']) && is_array($formatted['settings'])
            ? $formatted['settings']
            : [];

        if (
            $formatted['period_names'] === []
            && $formatted['settings'] === []
            && $this->normalizeStringList($formatted['officials']['value'] ?? []) === []
        ) {
            $seed = $this->getDefaultConfigTemplate($sport ?? $this->sportConfigService->getDefaultSportKey());
            foreach ($seed as $key => $data) {
                if (str_starts_with($key, 'period_name_')) {
                    $periods = str_replace('period_name_', '', $key);
                    $formatted['period_names'][(string)$periods] = [
                        'value' => $data['value'] ?? '',
                        'description' => $data['description'] ?? '',
                    ];
                } elseif ($key === 'officials') {
                    $formatted['officials'] = [
                        'value' => $data['value'] ?? [],
                        'description' => $data['description'] ?? '',
                    ];
                } else {
                    $formatted['settings'][$key] = [
                        'value' => $data['value'] ?? null,
                        'description' => $data['description'] ?? '',
                    ];
                }
            }
        }

        return $formatted;
    }

    /**
     * Set a single configuration key and optional description.
     *
     * @param string|int $sport
     * @param string $key
     * @param mixed $value
     * @param string|null $description
     * @return bool
     */
    public function setConfig(string|int $sport, string $key, mixed $value, ?string $description = null): bool
    {
        $sportKey = $this->resolveSportKey($sport);
        if ($sportKey === null || trim($key) === '') {
            return false;
        }

        $updated = $this->buildEditableConfig($this->sportConfigService->getMergedConfig($sportKey), $sportKey);
        $descriptions = isset($updated['_descriptions']) && is_array($updated['_descriptions'])
            ? $updated['_descriptions']
            : [];

        if (preg_match('/^period_name_(\d+)$/', $key, $matches) === 1) {
            $periodCount = (int)$matches[1];
            if ($periodCount > 0) {
                $updated['period_names'][$periodCount] = trim((string)$value);
            }
        } elseif ($key === 'officials') {
            $updated['officials'] = $this->normalizeStringList($value);
        } else {
            $updated[$key] = $this->normalizeSettingValue($key, $value);
        }

        if ($description !== null) {
            $descriptionValue = trim($description);
            if ($descriptionValue === '') {
                unset($descriptions[$key]);
            } else {
                $descriptions[$key] = $descriptionValue;
            }
        }

        if ($descriptions === []) {
            unset($updated['_descriptions']);
        } else {
            $updated['_descriptions'] = $descriptions;
        }

        $updated['supports_periods'] = $this->normalizeSupportsPeriodsValue(
            $updated['supports_periods'] ?? [],
            (array)$updated['period_names'],
        );

        if (is_array($updated['supports_periods']) && $updated['supports_periods'] !== []) {
            $default = (int)($updated['default_periods'] ?? 0);
            if (!in_array($default, $updated['supports_periods'], true)) {
                $updated['default_periods'] = (int)$updated['supports_periods'][0];
            }
        }

        return $this->sportConfigService->saveMergedConfig($sportKey, $updated);
    }

    /**
     * Delete a single configuration key.
     *
     * @param string|int $sport
     * @param string $configKey
     * @return bool
     */
    public function deleteConfig(string|int $sport, string $configKey): bool
    {
        $sportKey = $this->resolveSportKey($sport);
        if ($sportKey === null || trim($configKey) === '') {
            return false;
        }

        $updated = $this->buildEditableConfig($this->sportConfigService->getMergedConfig($sportKey), $sportKey);
        $descriptions = isset($updated['_descriptions']) && is_array($updated['_descriptions'])
            ? $updated['_descriptions']
            : [];

        if (preg_match('/^period_name_(\d+)$/', $configKey, $matches) === 1) {
            unset($updated['period_names'][(int)$matches[1]]);
            if (!is_string($updated['supports_periods'] ?? null)) {
                $updated['supports_periods'] = array_map('intval', array_keys((array)$updated['period_names']));
            }
        } elseif ($configKey === 'officials') {
            $updated['officials'] = [];
        } else {
            unset($updated[$configKey]);
        }

        unset($descriptions[$configKey]);

        if ($descriptions === []) {
            unset($updated['_descriptions']);
        } else {
            $updated['_descriptions'] = $descriptions;
        }

        $supports = $this->normalizeSupportsPeriodsValue(
            $updated['supports_periods'] ?? [],
            (array)$updated['period_names'],
        );
        $updated['supports_periods'] = $supports;

        if (is_array($supports) && $supports !== []) {
            $default = (int)($updated['default_periods'] ?? 0);
            if (!in_array($default, $supports, true)) {
                $updated['default_periods'] = (int)$supports[0];
            }
        }

        return $this->sportConfigService->saveMergedConfig($sportKey, $updated);
    }

    /**
     * Remove all runtime overrides for a sport.
     *
     * @param string|int $sport
     * @return bool
     */
    public function resetToDefaults(string|int $sport): bool
    {
        $sportKey = $this->resolveSportKey($sport);
        if ($sportKey === null) {
            return false;
        }

        return $this->sportConfigService->resetToDefaults($sportKey);
    }

    /**
     * Resolve a sport key from numeric legacy ID or canonical key.
     *
     * @param string|int $sport
     * @return string|null
     */
    private function resolveSportKey(string|int $sport): ?string
    {
        if (is_int($sport) || ctype_digit((string)$sport)) {
            return $this->sportConfigService->getKeyById((int)$sport);
        }

        $candidate = strtolower(trim((string)$sport));
        if ($candidate === '') {
            return null;
        }

        $available = $this->sportConfigService->getAvailableSports();

        return array_key_exists($candidate, $available) ? $candidate : null;
    }

    /**
     * @param array<string,mixed> $merged
     * @param string $sportKey
     * @return array<string,mixed>
     */
    private function buildEditableConfig(array $merged, string $sportKey): array
    {
        $periodNames = [];
        foreach ((array)($merged['period_names'] ?? []) as $periods => $periodName) {
            if (!is_numeric((string)$periods)) {
                continue;
            }
            $periodNames[(int)$periods] = trim((string)$periodName);
        }

        $editable = [
            'key' => $sportKey,
            'name' => (string)($merged['name'] ?? $this->sportConfigService->getSportDisplayName($sportKey)),
            'sport_active' => $this->normalizeBoolean($merged['sport_active'] ?? $merged['sportActive'] ?? true, true),
            'has_stats' => $this->normalizeBoolean($merged['has_stats'] ?? $merged['hasStats'] ?? false, false),
            'stats_tracked' => $this->normalizeStringList($merged['stats_tracked'] ?? $merged['statsTracked'] ?? []),
            'period_names' => $periodNames,
            'officials' => $this->normalizeStringList($merged['officials'] ?? []),
            '_descriptions' => is_array($merged['_descriptions'] ?? null) ? $merged['_descriptions'] : [],
            'supports_periods' => $this->normalizeSupportsPeriodsValue(
                $merged['supports_periods'] ?? [],
                $periodNames,
            ),
            'default_periods' => (int)($merged['default_periods'] ?? 0),
            'overtime_name' => (string)($merged['overtime_name'] ?? 'OT'),
            'scoring_type' => strtolower((string)($merged['scoring_type'] ?? 'cumulative')),
        ];

        $known = [
            'id',
            'key', 'name', 'sport_key',
            'sport_active', 'sportActive',
            'has_stats', 'hasStats',
            'stats_tracked', 'statsTracked',
            'period_names', 'periodNames',
            'officials',
            '_descriptions',
            'supports_periods', 'periods',
            'default_periods', 'defaultPeriods',
            'overtime_name', 'overtimeName',
            'scoring_type', 'scoringType',
        ];

        foreach ($merged as $key => $value) {
            if (!is_string($key)) {
                continue;
            }
            if (in_array($key, $known, true)) {
                continue;
            }
            if (preg_match('/^period_name_(\d+)$/', $key) === 1) {
                continue;
            }
            $editable[$key] = $value;
        }

        if (
            is_array($editable['supports_periods'])
            && $editable['supports_periods'] === []
            && $editable['period_names'] !== []
        ) {
            $editable['supports_periods'] = array_map('intval', array_keys($editable['period_names']));
        }

        if ($editable['default_periods'] <= 0) {
            $editable['default_periods'] = 4;
        }

        if (is_array($editable['supports_periods']) && $editable['supports_periods'] !== []) {
            if (!in_array($editable['default_periods'], $editable['supports_periods'], true)) {
                $editable['default_periods'] = (int)$editable['supports_periods'][0];
            }
        }

        return $editable;
    }

    /**
     * Normalize editable setting values from form payload strings.
     *
     * @param string $key
     * @param mixed $value
     * @return mixed
     */
    private function normalizeSettingValue(string $key, mixed $value): mixed
    {
        if ($key === 'supports_periods') {
            return $this->normalizeSupportsPeriodsValue($value, []);
        }

        if ($key === 'default_periods') {
            return is_numeric((string)$value) ? (int)$value : 0;
        }

        if ($key === 'sport_active' || $key === 'has_stats') {
            return $this->normalizeBoolean($value, false);
        }

        if ($key === 'stats_tracked') {
            return $this->normalizeStringList($value);
        }

        if ($key === 'officials') {
            return $this->normalizeStringList($value);
        }

        if ($key === 'scoring_type') {
            return strtolower(trim((string)$value));
        }

        if (is_string($value)) {
            $trimmed = trim($value);
            $decoded = json_decode($trimmed, true);
            if (is_array($decoded)) {
                return $decoded;
            }

            return $trimmed;
        }

        return $value;
    }

    /**
     * @param mixed $value
     * @return array<int,string>
     */
    private function normalizeStringList(mixed $value): array
    {
        if (is_string($value)) {
            $decoded = json_decode($value, true);
            if (is_array($decoded)) {
                $value = $decoded;
            } else {
                $value = array_map('trim', explode(',', $value));
            }
        }

        if (!is_array($value)) {
            return [];
        }

        $normalized = [];
        foreach ($value as $item) {
            $label = trim((string)$item);
            if ($label === '') {
                continue;
            }
            $normalized[] = $label;
        }

        return array_values(array_unique($normalized));
    }

    /**
     * @param mixed $value
     * @return array<int>
     */
    private function normalizeIntList(mixed $value): array
    {
        if (is_string($value)) {
            $decoded = json_decode($value, true);
            if (is_array($decoded)) {
                $value = $decoded;
            } else {
                $value = array_map('trim', explode(',', $value));
            }
        }

        if (!is_array($value)) {
            return [];
        }

        $normalized = [];
        foreach ($value as $item) {
            if (!is_numeric((string)$item)) {
                continue;
            }
            $normalized[] = (int)$item;
        }

        $normalized = array_values(array_unique($normalized));
        sort($normalized);

        return $normalized;
    }

    /**
     * @param mixed $value
     * @param array<int,string> $periodNames
     * @return array<int>|string
     */
    private function normalizeSupportsPeriodsValue(mixed $value, array $periodNames): array|string
    {
        if (is_string($value)) {
            $normalizedString = strtolower(trim($value));
            if (in_array($normalizedString, ['any', '*', 'all'], true)) {
                return 'any';
            }
        }

        $periods = $this->normalizeIntList($value);
        if ($periods === [] && $periodNames !== []) {
            $periods = array_map('intval', array_keys($periodNames));
            sort($periods);
        }

        return $periods;
    }

    /**
     * @param mixed $value
     * @param bool $default
     * @return bool
     */
    private function normalizeBoolean(mixed $value, bool $default): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        if (is_int($value) || is_float($value)) {
            return ((int)$value) !== 0;
        }

        if (is_string($value)) {
            $normalized = strtolower(trim($value));
            if (in_array($normalized, ['1', 'true', 'yes', 'on'], true)) {
                return true;
            }
            if (in_array($normalized, ['0', 'false', 'no', 'off'], true)) {
                return false;
            }
        }

        return $default;
    }
}
