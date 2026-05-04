<?php
declare(strict_types=1);

namespace App\Service;

use Cake\ORM\TableRegistry;

/**
 * SportConfigAdminService
 *
 * Thin service wrapper around SportConfigs table operations that are used by
 * admin controllers. Keeps controllers focused on request/response only.
 */
class SportConfigAdminService
{
    /**
     * Get configs formatted for the sport edit/view pages.
     *
     * @param int $sportId
     */
    public function getFormattedConfigsForSport(int $sportId): array
    {
        /** @var \App\Model\Table\SportConfigsTable $table */
        $table = TableRegistry::getTableLocator()->get('SportConfigs');

        return $table->getFormattedConfigsForSport($sportId);
    }

    /**
     * Persist config payload from the editConfigs form.
     *
     * @param int $sportId
     * @param array $configData
     */
    public function saveBulkConfigs(int $sportId, array $configData): bool
    {
        /** @var \App\Model\Table\SportConfigsTable $table */
        $table = TableRegistry::getTableLocator()->get('SportConfigs');

        return (bool)$table->saveBulkConfigs($sportId, $configData);
    }

    /**
     * Return the default config template.
     */
    public function getDefaultConfigTemplate(): array
    {
        /** @var \App\Model\Table\SportConfigsTable $table */
        $table = TableRegistry::getTableLocator()->get('SportConfigs');

        return (array)$table->getDefaultConfigTemplate();
    }

    /**
     * Ensure the formatted config structure contains the expected keys and
     * optionally seeds defaults when no configs exist.
     *
     * @param array $formatted
     */
    public function normalizeFormattedConfigs(array $formatted): array
    {
        if (empty($formatted['period_names']) && empty($formatted['officials']) && empty($formatted['settings'])) {
            $defaultTemplate = $this->getDefaultConfigTemplate();
            foreach ($defaultTemplate as $key => $data) {
                if (str_starts_with($key, 'period_name_')) {
                    $periods = str_replace('period_name_', '', $key);
                    $formatted['period_names'][$periods] = $data;
                } elseif ($key === 'officials') {
                    $formatted['officials'] = $data;
                } else {
                    $formatted['settings'][$key] = $data;
                }
            }
        } else {
            if (empty($formatted['officials'])) {
                $formatted['officials'] = ['value' => '', 'description' => ''];
            }
            if (!isset($formatted['period_names'])) {
                $formatted['period_names'] = [];
            }
            if (!isset($formatted['settings'])) {
                $formatted['settings'] = [];
            }
        }

        return $formatted;
    }

    /**
     * Set a single config key/value.
     *
     * @param int $sportId
     * @param string $key
     * @param mixed $value
     * @param string|null $description
     */
    public function setConfig(int $sportId, string $key, mixed $value, ?string $description = null): bool
    {
        /** @var \App\Model\Table\SportConfigsTable $table */
        $table = TableRegistry::getTableLocator()->get('SportConfigs');

        return (bool)$table->setConfig($sportId, $key, $value, $description);
    }

    /**
     * Delete a single config record.
     *
     * @param int $sportId
     * @param string $configKey
     */
    public function deleteConfig(int $sportId, string $configKey): bool
    {
        /** @var \App\Model\Table\SportConfigsTable $table */
        $table = TableRegistry::getTableLocator()->get('SportConfigs');

        $config = $table->find()
            ->where(['sport_id' => $sportId, 'config_key' => $configKey])
            ->first();

        return $config ? (bool)$table->delete($config) : false;
    }

    /**
     * Reset configs by deleting current and seeding defaults.
     *
     * @param int $sportId
     */
    public function resetToDefaults(int $sportId): bool
    {
        /** @var \App\Model\Table\SportConfigsTable $table */
        $table = TableRegistry::getTableLocator()->get('SportConfigs');
        $table->deleteAll(['sport_id' => $sportId]);

        return $this->saveBulkConfigs($sportId, $this->getDefaultConfigTemplate());
    }
}
