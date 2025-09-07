<?php
declare(strict_types=1);

namespace App\Utility;

use App\Model\Entity\Person;

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
     * @param \App\Model\Entity\Person|object $person Person entity with name fields
     * @param int|null $personId Optional person ID for fallback label
     * @return string Human-readable person label
     */
    public static function buildLabel($person, ?int $personId = null): string
    {
        // Try display field first
        $display = $person->get('display') ?? $person->display ?? null;
        if ($display) {
            return $display;
        }

        // Fall back to first + last name
        $first = property_exists($person, 'first') ? $person->first : ($person->get('first') ?? '');
        $last = property_exists($person, 'last') ? $person->last : ($person->get('last') ?? '');
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
    public static function buildLabelFromId(int $personId, $personsTable): string
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