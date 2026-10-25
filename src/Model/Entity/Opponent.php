<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * Opponent Entity
 *
 * @property int $id
 * @property string|null $opponent_name Official opponent name
 * @property string|null $opponent_mascot Team mascot or nickname
 * @property int|null $opponent_current Reference to current opponent (self-reference)
 * @property string|null $opponent_short Short opponent name (max 30 chars)
 * @property string|null $opponent_abbr Scorebug abbreviation (max 6 chars)
 * @property int|null $place_id
 * @property \App\Model\Entity\Place|null $place
 * @property \App\Model\Entity\Opponent|null $current_opponent
 * @property \Cake\I18n\DateTime|null $created_at
 * @property \Cake\I18n\DateTime|null $updated_at
 */
class Opponent extends Entity
{
    /**
     * Get the default opponent name for display.
     *
     * If a mascot is not available, retain the existing official opponent name.
     *
     * @return string
     */
    public function getDisplayName(): string
    {
        $short = trim((string)($this->opponent_short ?? ''));
        $mascot = trim((string)($this->opponent_mascot ?? ''));
        if ($short !== '' && $mascot !== '') {
            return $this->composeDisplayName([$short, $mascot]);
        }

        return $this->getFullDisplayName();
    }

    /**
     * Get the full opponent name for display.
     *
     * @return string
     */
    public function getFullDisplayName(): string
    {
        $displayName = $this->composeDisplayName([
            $this->opponent_name ?? null,
            $this->opponent_mascot ?? null,
        ]);
        if ($displayName !== 'Unknown Opponent') {
            return $displayName;
        }

        return $this->composeDisplayName([$this->opponent_short ?? null]);
    }

    /**
     * Get the opponent mascot for compact display.
     *
     * @return string
     */
    public function getMascotDisplayName(): string
    {
        $mascot = trim((string)($this->opponent_mascot ?? ''));

        return $mascot !== '' ? $mascot : $this->getDisplayName();
    }

    /**
     * Join populated display components and remove exact duplicates.
     *
     * @param array<int,mixed> $parts Display components
     * @return string
     */
    private function composeDisplayName(array $parts): string
    {
        $components = [];
        foreach ($parts as $part) {
            if (!is_string($part) && !is_numeric($part)) {
                continue;
            }

            $component = trim((string)$part);
            if ($component !== '' && !in_array($component, $components, true)) {
                $components[] = $component;
            }
        }

        return $components === [] ? 'Unknown Opponent' : implode(' ', $components);
    }
}
