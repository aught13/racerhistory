<?php
declare(strict_types=1);

namespace App\Service;

/**
 * GameEavUiService
 *
 * Small helper service for preparing game EAV values for admin UI rendering.
 */
class GameEavUiService
{
    /**
     * Map legacy stored keys to current field names used by the UI.
     *
     * Legacy keys: period_{n}_mur, period_{n}_opp
     * Current UI keys: period_{n}_team, period_{n}_opponent
     *
     * @param array<string,mixed> $values
     * @return array<string,mixed>
     */
    public function mapLegacyKeys(array $values): array
    {
        $mapped = $values;

        foreach ($values as $key => $value) {
            if (preg_match('/^period_(\d+)_mur$/', (string)$key, $m)) {
                $newKey = 'period_' . $m[1] . '_team';
                if (!array_key_exists($newKey, $mapped)) {
                    $mapped[$newKey] = $value;
                }
            } elseif (preg_match('/^period_(\d+)_opp$/', (string)$key, $m)) {
                $newKey = 'period_' . $m[1] . '_opponent';
                if (!array_key_exists($newKey, $mapped)) {
                    $mapped[$newKey] = $value;
                }
            }
        }

        return $mapped;
    }

    /**
     * Merge posted EAV-ish fields into an existing EAV array so the form can re-render user input.
     *
     * Only merges keys that belong to the EAV UI element (period_* and overtime_*).
     *
     * @param array<string,mixed> $existing
     * @param array<string,mixed> $posted
     * @return array<string,mixed>
     */
    public function mergePostedPeriodAndOvertimeFields(array $existing, array $posted): array
    {
        foreach ($posted as $key => $value) {
            if (strpos((string)$key, 'period_') === 0 || strpos((string)$key, 'overtime_') === 0) {
                $existing[(string)$key] = $value;
            }
        }

        return $existing;
    }
}
