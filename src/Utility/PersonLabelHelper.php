<?php
declare(strict_types=1);

namespace App\Utility;

/**
 * Helper utility for building person labels consistently across the application.
 */
class PersonLabelHelper
{
    /**
     * Build a display label for a person based on available name fields.
     *
     * Prioritizes display field, falls back to first + last name combination,
     * and provides a fallback for cases where neither is available.
     *
     * @param object $person Person entity with name fields
     * @param int|null $personId Optional person ID for fallback label
     * @return string Human-readable person label
     */
    public static function buildLabel(object $person, ?int $personId = null): string
    {
        // Safe accessor for both Entities (with get()) and plain objects
        $getField = function (string $field) use ($person) {
            if (is_object($person) && method_exists($person, 'get')) {
                return $person->get($field);
            }

            return $person->{$field} ?? null;
        };

        // Try display field first
        $display = $getField('display');
        if ($display) {
            return $display;
        }

        // Fall back to first + last name
        $first = $getField('first') ?? '';
        $last = $getField('last') ?? '';
        $fullName = trim((string)$first . ' ' . (string)$last);
        if ($fullName) {
            return $fullName;
        }

        // Final fallback with person ID
        if ($personId !== null) {
            return 'Person #' . $personId;
        }

        return 'Unknown Person';
    }

    /**
     * Build a label for a person by fetching from database if needed.
     *
     * @param int $personId Person ID to fetch and build label for
     * @param \Cake\ORM\Table $personsTable Table instance to query
     * @return string Person label
     */
    public static function buildLabelFromId(int $personId, \Cake\ORM\Table $personsTable): string
    {
        try {
            /** @var \App\Model\Entity\Person $person */
            $person = $personsTable->get($personId);

            return self::buildLabel($person, $personId);
        } catch (\Throwable $e) {
            return 'Person #' . $personId;
        }
    }
}
