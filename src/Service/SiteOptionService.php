<?php
declare(strict_types=1);

namespace App\Service;

use App\Model\Entity\SiteOption;
use Cake\ORM\TableRegistry;

/**
 * SiteOptionService
 *
 * Service layer for key/value site options (SiteOptions table).
 */
class SiteOptionService
{
    /**
     * Get an option value by key.
     *
     * @param string $key
     * @return string|null
     */
    public function getOptionValue(string $key): ?string
    {
        $table = TableRegistry::getTableLocator()->get('SiteOptions');
        $row = $table->find()->where(['option_key' => $key])->first();

        return $row instanceof SiteOption ? (string)$row->value : null;
    }

    /**
     * Set an option value (creates the record if missing).
     *
     * @param string $key
     * @param string $value
     * @return bool
     */
    public function setOptionValue(string $key, string $value): bool
    {
        $table = TableRegistry::getTableLocator()->get('SiteOptions');
        $row = $table->find()->where(['option_key' => $key])->first();

        if ($row instanceof SiteOption) {
            $row->value = $value;
        } else {
            $row = $table->newEntity([
                'option_key' => $key,
                'value' => $value,
            ]);
        }

        return (bool)$table->save($row);
    }

    /**
     * Toggle a boolean option stored as the string 'true'/'false'.
     *
     * @param string $key
     * @param bool $default Default used when the option does not exist.
     * @return bool The new boolean value.
     */
    public function toggleBooleanOption(string $key, bool $default = true): bool
    {
        $current = $this->getBooleanOption($key, $default);
        $next = !$current;
        $this->setOptionValue($key, $next ? 'true' : 'false');

        return $next;
    }

    /**
     * Get a boolean option stored as the string 'true'/'false'.
     *
     * @param string $key
     * @param bool $default
     * @return bool
     */
    public function getBooleanOption(string $key, bool $default = true): bool
    {
        $value = $this->getOptionValue($key);
        if ($value === null) {
            return $default;
        }

        return $value === 'true';
    }
}
