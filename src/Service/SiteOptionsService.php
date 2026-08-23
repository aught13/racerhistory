<?php
declare(strict_types=1);

namespace App\Service;

use App\Model\Entity\SiteOption;
use App\Model\Table\SiteOptionsTable;
use Cake\Cache\Cache;
use Cake\Core\Configure;
use Cake\ORM\TableRegistry;
use Throwable;

/**
 * Service layer for global key/value site options.
 *
 * Responsibilities:
 * - Read option definitions from `SiteOptionsDefaults` configuration.
 * - Persist form payloads by iterating the registered option keys.
 * - Upsert option rows in a single transaction.
 * - Keep runtime and cache (`global_site_options`) in sync after updates.
 */
class SiteOptionsService
{
    private const CACHE_KEY = 'global_site_options';

    private SiteOptionsTable $siteOptionsTable;

    private SportConfigAdminService $sportConfigAdminService;

    /**
     * @var array<string,array{label:string,type:string,default:mixed}>
     */
    private array $definitions;

    /**
     * @param \App\Model\Table\SiteOptionsTable|null $siteOptionsTable
     * @param array<string,array{label:string,type:string,default:mixed}>|null $definitions
     * @param \App\Service\SportConfigAdminService|null $sportConfigAdminService
     */
    public function __construct(
        ?SiteOptionsTable $siteOptionsTable = null,
        ?array $definitions = null,
        ?SportConfigAdminService $sportConfigAdminService = null,
    ) {
        /** @var \App\Model\Table\SiteOptionsTable $table */
        $table = $siteOptionsTable ?? TableRegistry::getTableLocator()->get('SiteOptions');
        $this->siteOptionsTable = $table;

        $this->definitions = $definitions ?? $this->readDefinitionsFromConfigure();
        $this->sportConfigAdminService = $sportConfigAdminService ?? new SportConfigAdminService();
    }

    /**
     * @return array<string,array{label:string,type:string,default:mixed}>
     */
    public function getDefinitions(): array
    {
        return $this->definitions;
    }

    /**
     * @return array<string,string>
     */
    public function getAvailableSports(): array
    {
        return $this->sportConfigAdminService->getAvailableSports();
    }

    /**
     * @param int $sportId
     * @return string|null
     */
    public function getSportKeyForId(int $sportId): ?string
    {
        return $this->sportConfigAdminService->getSportKeyForId($sportId);
    }

    /**
     * @param string|int $sport
     * @return string
     */
    public function getSportDisplayName(string|int $sport): string
    {
        return $this->sportConfigAdminService->getSportDisplayName($sport);
    }

    /**
     * @param string|int $sport
     * @return array<string,mixed>
     */
    public function getFormattedSportConfigs(string|int $sport): array
    {
        return $this->sportConfigAdminService->getFormattedConfigsForSport($sport);
    }

    /**
     * @param array<string,mixed> $formatted
     * @param string|int|null $sport
     * @return array<string,mixed>
     */
    public function normalizeFormattedSportConfigs(array $formatted, string|int|null $sport = null): array
    {
        return $this->sportConfigAdminService->normalizeFormattedConfigs($formatted, $sport);
    }

    /**
     * @param string|int $sport
     * @param array<string,mixed> $configData
     */
    public function saveSportConfigs(string|int $sport, array $configData): bool
    {
        return $this->sportConfigAdminService->saveBulkConfigs($sport, $configData);
    }

    /**
     * @param string|int $sport
     * @param string $key
     * @param mixed $value
     * @param string|null $description
     */
    public function setSportConfig(string|int $sport, string $key, mixed $value, ?string $description = null): bool
    {
        return $this->sportConfigAdminService->setConfig($sport, $key, $value, $description);
    }

    /**
     * @param string|int $sport
     * @param string $key
     */
    public function deleteSportConfig(string|int $sport, string $key): bool
    {
        return $this->sportConfigAdminService->deleteConfig($sport, $key);
    }

    /**
     * @param string|int $sport
     */
    public function resetSportConfigs(string|int $sport): bool
    {
        return $this->sportConfigAdminService->resetToDefaults($sport);
    }

    /**
     * Read runtime option values (typed) for all registered keys.
     *
     * @return array<string,mixed>
     */
    public function getRuntimeSettings(): array
    {
        $defaults = $this->getDefaultValues();

        if ($this->definitions === []) {
            return $defaults;
        }

        $configured = Configure::read('SiteOptions');
        if (is_array($configured)) {
            $settings = $defaults;
            foreach ($this->definitions as $optionKey => $definition) {
                if (!array_key_exists($optionKey, $configured)) {
                    continue;
                }

                $settings[$optionKey] = $this->normalizeRuntimeValue($configured[$optionKey], $definition);
            }

            return $settings;
        }

        $cached = Cache::read(self::CACHE_KEY);
        if (is_array($cached)) {
            $settings = $defaults;
            foreach ($this->definitions as $optionKey => $definition) {
                if (!array_key_exists($optionKey, $cached)) {
                    continue;
                }

                $settings[$optionKey] = $this->normalizeRuntimeValue($cached[$optionKey], $definition);
            }
            Configure::write('SiteOptions', $settings);

            return $settings;
        }

        $settings = $this->reloadRuntimeSettingsFromDatabase();

        return $settings;
    }

    /**
     * Read one runtime site option by key.
     *
     * @param string $optionKey
     * @param mixed $fallback
     * @return mixed
     */
    public function getRuntimeSetting(string $optionKey, mixed $fallback = null): mixed
    {
        $settings = $this->getRuntimeSettings();

        return $settings[$optionKey] ?? $fallback;
    }

    /**
     * Read one option directly from persisted storage.
     *
     * This bypasses runtime Configure/cache state and is useful for operations
     * that must reflect the latest DB value before writing.
     *
     * @param string $optionKey
     * @param mixed $fallback
     * @return mixed
     */
    public function getPersistedSetting(string $optionKey, mixed $fallback = null): mixed
    {
        if (!isset($this->definitions[$optionKey])) {
            return $fallback;
        }

        $definition = $this->definitions[$optionKey];
        $default = $definition['default'] ?? $fallback;

        $row = $this->siteOptionsTable->find()->where(['option_key' => $optionKey])->first();
        if ($row instanceof SiteOption) {
            return $this->normalizeRuntimeValue($row->value, $definition);
        }

        return $this->normalizeRuntimeValue($default, $definition);
    }

    /**
     * Toggle a checkbox option and persist the result.
     *
     * @param string $optionKey
     * @param bool $default
     * @return bool|null Returns null when key is unknown, not checkbox, or save fails.
     */
    public function toggleBooleanSetting(string $optionKey, bool $default = false): ?bool
    {
        if (!isset($this->definitions[$optionKey])) {
            return null;
        }

        $definition = $this->definitions[$optionKey];
        if ($definition['type'] !== 'checkbox') {
            return null;
        }

        $current = (bool)$this->getPersistedSetting($optionKey, $default);
        $next = !$current;

        if (!$this->saveSettings([$optionKey => $next])) {
            return null;
        }

        return $next;
    }

    /**
     * Persist submitted settings for all registered keys.
     *
     * Missing keys fall back to configured defaults, which ensures deterministic
     * values for omitted checkbox inputs.
     *
     * @param array<string,mixed> $formData
     * @return bool
     */
    public function saveSettings(array $formData): bool
    {
        if ($this->definitions === []) {
            return false;
        }
        $existingRows = $this->loadExistingRows();

        // Create a timestamped JSON backup of current option values before overwriting.
        try {
            $backupDir = TMP . 'backups' . DS;
            if (!is_dir($backupDir)) {
                mkdir($backupDir, 0775, true);
            }

            $backupData = [];
            foreach ($existingRows as $k => $row) {
                $backupData[$k] = $row->value ?? null;
            }

            $ts = date('YmdHis');
            $filePath = $backupDir . "site_options_pre_save_{$ts}.json";
            $encoded = json_encode($backupData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
            if ($encoded !== false) {
                $result = file_put_contents($filePath, $encoded);
                if ($result === false) {
                    // Non-fatal: continue even if backup fails due to permissions.
                }
            }
        } catch (Throwable) {
            // Non-fatal: continue even if backup fails due to permissions.
        }
        $storageValues = [];

        foreach ($this->definitions as $optionKey => $definition) {
            $sourceValue = array_key_exists($optionKey, $formData)
                ? $formData[$optionKey]
                : ($definition['default'] ?? null);

            $storageValues[$optionKey] = $this->normalizeStorageValue($sourceValue, $definition);
        }

        $connection = $this->siteOptionsTable->getConnection();
        $saved = (bool)$connection->transactional(function () use ($existingRows, $storageValues): bool {
            foreach ($storageValues as $optionKey => $storageValue) {
                if (isset($existingRows[$optionKey])) {
                    $entity = $this->siteOptionsTable->patchEntity($existingRows[$optionKey], [
                        'value' => $storageValue,
                    ]);
                } else {
                    $entity = $this->siteOptionsTable->newEntity([
                        'option_key' => $optionKey,
                        'value' => $storageValue,
                    ]);
                }

                if (!$this->siteOptionsTable->save($entity)) {
                    return false;
                }
            }

            return true;
        });

        if (!$saved) {
            return false;
        }

        $runtimeSettings = $this->getDefaultValues();
        foreach ($this->definitions as $optionKey => $definition) {
            $runtimeSettings[$optionKey] = $this->normalizeRuntimeValue($storageValues[$optionKey], $definition);
        }

        Cache::delete(self::CACHE_KEY);
        Cache::write(self::CACHE_KEY, $runtimeSettings);
        Configure::write('SiteOptions', $runtimeSettings);

        return true;
    }

    /**
     * @return array<string,array{label:string,type:string,default:mixed}>
     */
    private function readDefinitionsFromConfigure(): array
    {
        $rawDefinitions = Configure::read('SiteOptionsDefaults');
        if (!is_array($rawDefinitions)) {
            return [];
        }

        $definitions = [];

        foreach ($rawDefinitions as $optionKey => $definition) {
            if (!is_string($optionKey) || !is_array($definition)) {
                continue;
            }

            $definitions[$optionKey] = [
                'label' => isset($definition['label'])
                    ? (string)$definition['label']
                    : ucwords(str_replace('_', ' ', $optionKey)),
                'type' => isset($definition['type'])
                    ? (string)$definition['type']
                    : 'text',
                'default' => $definition['default'] ?? null,
            ];
        }

        return $definitions;
    }

    /**
     * @return array<string,mixed>
     */
    private function getDefaultValues(): array
    {
        $defaults = [];

        foreach ($this->definitions as $optionKey => $definition) {
            $defaults[$optionKey] = $definition['default'] ?? null;
        }

        return $defaults;
    }

    /**
     * @return array<string,\App\Model\Entity\SiteOption>
     */
    private function loadExistingRows(): array
    {
        $existingRows = [];

        if ($this->definitions === []) {
            return $existingRows;
        }

        /** @var \Cake\ORM\Query\SelectQuery<\App\Model\Entity\SiteOption> $query */
        $query = $this->siteOptionsTable->find();
        $rows = $query
            ->where(['option_key IN' => array_keys($this->definitions)])
            ->all();

        foreach ($rows as $row) {
            if (!$row instanceof SiteOption || !is_string($row->option_key)) {
                continue;
            }

            $existingRows[$row->option_key] = $row;
        }

        return $existingRows;
    }

    /**
     * @param mixed $value
     * @param array{label:string,type:string,default:mixed} $definition
     * @return string
     */
    private function normalizeStorageValue(mixed $value, array $definition): string
    {
        $type = $definition['type'];
        $default = $definition['default'];

        if ($type === 'checkbox') {
            return $this->normalizeCheckboxValue($value, (bool)$default) ? 'true' : 'false';
        }

        if ($type === 'number') {
            return (string)$this->normalizeNumberValue($value, (int)$default);
        }

        $normalized = trim((string)$value);
        if ($normalized === '') {
            $normalized = (string)$default;
        }

        return $normalized;
    }

    /**
     * @param mixed $value
     * @param array{label:string,type:string,default:mixed} $definition
     * @return mixed
     */
    private function normalizeRuntimeValue(mixed $value, array $definition): mixed
    {
        $type = $definition['type'];
        $default = $definition['default'];

        if ($type === 'checkbox') {
            return $this->normalizeCheckboxValue($value, (bool)$default);
        }

        if ($type === 'number') {
            return $this->normalizeNumberValue($value, (int)$default);
        }

        if ($value === null || $value === '') {
            return (string)$default;
        }

        return (string)$value;
    }

    /**
     * @param mixed $value
     * @param bool $default
     * @return bool
     */
    private function normalizeCheckboxValue(mixed $value, bool $default): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        if (is_int($value)) {
            return $value === 1;
        }

        if (is_string($value)) {
            $normalized = strtolower(trim($value));
            if (in_array($normalized, ['1', 'true', 'on', 'yes'], true)) {
                return true;
            }
            if (in_array($normalized, ['0', 'false', 'off', 'no'], true)) {
                return false;
            }
        }

        return $default;
    }

    /**
     * @param mixed $value
     * @param int $default
     * @return int
     */
    private function normalizeNumberValue(mixed $value, int $default): int
    {
        if (is_numeric((string)$value)) {
            return (int)$value;
        }

        return $default;
    }

    /**
     * @return array<string,mixed>
     */
    private function reloadRuntimeSettingsFromDatabase(): array
    {
        $settings = $this->getDefaultValues();

        try {
            /** @var \Cake\ORM\Query\SelectQuery<\App\Model\Entity\SiteOption> $query */
            $query = $this->siteOptionsTable->find();
            $rows = $query
                ->where(['option_key IN' => array_keys($this->definitions)])
                ->all();

            foreach ($rows as $row) {
                if (!$row instanceof SiteOption || !is_string($row->option_key)) {
                    continue;
                }
                if (!isset($this->definitions[$row->option_key])) {
                    continue;
                }

                $settings[$row->option_key] = $this->normalizeRuntimeValue(
                    $row->value,
                    $this->definitions[$row->option_key],
                );
            }
        } catch (Throwable) {
            // Keep configured defaults if DB table isn't available yet.
        }

        Cache::write(self::CACHE_KEY, $settings);
        Configure::write('SiteOptions', $settings);

        return $settings;
    }

    /**
     * Retrieve the dynamic RBAC configuration map from Site Options.
     *
     * @return array<string, array<string>>
     */
    public function getRolePrivileges(): array
    {
        $raw = $this->getRuntimeSetting('role_privileges');
        if (is_string($raw)) {
            $decoded = json_decode($raw, true);
            if (is_array($decoded)) {
                return $decoded;
            }
        }

        return [
            'admin' => ['bypass_all'],
            'editor' => ['view_any', 'edit_any', 'create_any'],
            'author' => ['view_own', 'edit_own', 'create_any'],
        ];
    }

    /**
     * Update the dynamic RBAC configuration map.
     *
     * @param array<string, array<string>> $privileges
     * @return bool
     */
    public function updateRolePrivileges(array $privileges): bool
    {
        $json = json_encode($privileges, JSON_PRETTY_PRINT) ?: '';

        return $this->saveSettings(['role_privileges' => $json]);
    }
}
