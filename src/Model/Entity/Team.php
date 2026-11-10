<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * Team Entity
 *
 * Represents a competitive team within a specific sport.
 * Teams have detailed information including sport association,
 * gender classification, and display information.
 *
 * @property int $id Unique identifier
 * @property int $sport_id Foreign key to sports table
 * @property string|null $sport_key Canonical sport key (basketball, football, etc.)
 * @property string $team_name Base team name or category (max 162 chars)
 * @property string|null $team_description Organization or name prefix for the full team name (max 240 chars)
 * @property string $abbr Team abbreviation for compact display (max 5 chars)
 * @property string $team_nickname Team mascot or nickname (max 30 chars)
 * @property string $team_scorebug Shortened name for score display (max 6 chars)
 * @property string $gender Gender classification: M (Male), F (Female), C (Co-ed)
 * @property \Cake\I18n\DateTime|null $created_at Creation timestamp
 * @property \Cake\I18n\DateTime|null $updated_at Last modification timestamp
 *
 * @property \App\Model\Entity\Sport $sport Associated sport entity
 */
class Team extends Entity
{
    /**
     * Fields that can be mass assigned using newEntity() or patchEntity().
     *
     * Note that when '*' is set to true, this allows all unspecified fields to
     * be mass assigned. For security purposes, it is advised to set '*' to false
     * (or remove it), and explicitly make individual fields accessible as needed.
     *
     * @var array<string, bool>
     */
    protected array $_accessible = [
        'sport_id' => true,
        'sport_key' => true,
        'team_name' => true,
        'team_description' => true,
        'abbr' => true,
        'team_nickname' => true,
        'team_scorebug' => true,
        'gender' => true,
        'created_at' => true,
        'updated_at' => true,
        'sport' => true,
    ];

    /**
     * Gender classification constants
     */
    public const GENDER_MALE = 'M';
    public const GENDER_FEMALE = 'F';
    public const GENDER_COED = 'C';

    /**
     * Get the default team name for display.
     *
     * @return string
     */
    public function getDisplayName(): string
    {
        $nameParts = [$this->team_nickname ?? null, $this->team_name ?? null];

        return $this->composeDisplayName([
            $nameParts[0],
            $this->getDisplayGenderPrefix($nameParts),
            $nameParts[1],
        ]);
    }

    /**
     * Get the full team name for display.
     *
     * @return string
     */
    public function getFullTeamDisplayName(): string
    {
        $nameParts = [
            $this->team_description ?? null,
            $this->team_nickname ?? null,
            $this->team_name ?? null,
        ];

        return $this->composeDisplayName([
            $nameParts[0],
            $nameParts[1],
            $this->getDisplayGenderPrefix($nameParts),
            $nameParts[2],
        ]);
    }

    /**
     * Get the short team label for display.
     *
     * @return string
     */
    public function getTeamDisplayName(): string
    {
        $nameParts = [$this->team_name ?? null];

        return $this->composeDisplayName([
            $this->getDisplayGenderPrefix($nameParts),
            $nameParts[0],
        ]);
    }

    /**
     * Get human-readable gender label
     *
     * @return string
     */
    public function getGenderLabel(): string
    {
        $genderLabels = [
            self::GENDER_MALE => 'Male',
            self::GENDER_FEMALE => 'Female',
            self::GENDER_COED => 'Co-ed',
        ];

        return $genderLabels[$this->gender] ?? 'Unknown';
    }

    /**
     * Get full team display name with sport context
     *
     * @return string
     */
    public function getFullDisplayName(): string
    {
        $sportName = $this->sport->sport_name ?? null;
        if ($sportName === null || $sportName === '') {
            $sportKey = trim((string)($this->sport_key ?? ''));
            if ($sportKey !== '') {
                $sportName = ucwords(str_replace('_', ' ', $sportKey));
            }
        }

        if ($sportName === null || $sportName === '') {
            $sportName = 'Unknown Sport';
        }

        return sprintf('%s (%s)', $this->team_name ?? 'Unknown Team', $sportName);
    }

    /**
     * Join populated display components and avoid repeating exact components.
     *
     * @param array<int,mixed> $parts Display components
     * @return string
     */
    private function composeDisplayName(array $parts): string
    {
        $components = $this->normalizeDisplayComponents($parts);

        return $components === [] ? 'Unknown Team' : implode(' ', $components);
    }

    /**
     * Add a gender prefix unless the base team name is missing or already gendered.
     *
     * @param array<int,mixed> $nameParts Name components included in this display
     * @return string
     */
    private function getDisplayGenderPrefix(array $nameParts): string
    {
        if ($this->normalizeDisplayComponents([$this->team_name ?? null]) === []) {
            return '';
        }

        $prefix = match ($this->gender ?? null) {
            self::GENDER_MALE => "Men's",
            self::GENDER_FEMALE => "Women's",
            default => '',
        };
        if ($prefix === '') {
            return '';
        }

        $includedName = implode(' ', $this->normalizeDisplayComponents($nameParts));
        $pattern = "/\\b(?:men(?:['’]s)?|male|boys?|women(?:['’]s)?|female|girls?)\\b/ui";

        return preg_match($pattern, $includedName) === 1 ? '' : $prefix;
    }

    /**
     * Trim empty display components and remove exact duplicates.
     *
     * @param array<int,mixed> $parts Display components
     * @return list<string>
     */
    private function normalizeDisplayComponents(array $parts): array
    {
        $components = [];
        foreach ($parts as $part) {
            if (!is_string($part) && !is_numeric($part)) {
                continue;
            }

            $component = trim((string)$part);
            if ($component !== '') {
                $existingIndex = array_search($component, $components, true);
                if ($existingIndex !== false) {
                    array_splice($components, $existingIndex, 1);
                }
                $components[] = $component;
            }
        }

        return $components;
    }

    /**
     * Get compact display format using abbreviation
     *
     * @return string
     */
    public function getCompactName(): string
    {
        return $this->abbr ?? 'UNK';
    }

    /**
     * Check if the team has an organization or name prefix.
     *
     * @return bool
     */
    public function hasDescription(): bool
    {
        return !empty($this->team_description);
    }

    /**
     * Get array of all gender options for forms
     *
     * @return array<string, string>
     */
    public static function getGenderOptions(): array
    {
        return [
            self::GENDER_MALE => 'Male',
            self::GENDER_FEMALE => 'Female',
            self::GENDER_COED => 'Co-ed',
        ];
    }
}
