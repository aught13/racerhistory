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
 * @property string $team_name Short display name (max 162 chars)
 * @property string|null $team_description Full official name including institution and sport (max 240 chars)
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
     * Get display name for this team
     *
     * @return string
     */
    public function getDisplayName(): string
    {
        return $this->team_name ?? 'Unknown Team';
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
        $sportName = $this->sport?->sport_name ?? 'Unknown Sport';

        return sprintf('%s (%s)', $this->team_name ?? 'Unknown Team', $sportName);
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
     * Check if team has a description
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
