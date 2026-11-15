<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * @property int $id
 * @property string $title
 * @property string $placement
 * @property string $target_type
 * @property string|null $sport_key
 * @property int|null $team_id
 * @property bool $is_active
 * @property string $html
 * @property string $mode
 * @property string|null $sizes_desktop
 * @property string|null $sizes_mobile
 * @property string|null $gpt_unit_path
 * @property int $display_order
 * @property \Cake\I18n\DateTime|null $created
 * @property \Cake\I18n\DateTime|null $modified
 */
class Advertisement extends Entity
{
    /**
     * Mass assignment rules.
     *
     * @var array<string,bool>
     */
    protected array $_accessible = [
        'title' => true,
        'placement' => true,
        'target_type' => true,
        'sport_key' => true,
        'team_id' => true,
        'is_active' => true,
        'html' => true,
        'mode' => true,
        'sizes_desktop' => true,
        'sizes_mobile' => true,
        'gpt_unit_path' => true,
        'display_order' => true,
        'created' => true,
        'modified' => true,
    ];
}
