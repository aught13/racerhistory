<?php
declare(strict_types=1);

namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

class AdvertisementsFixture extends TestFixture
{
    public string $table = 'advertisements';
    public $import = ['table' => 'advertisements'];

    /**
     * Initialize representative global and targeted campaigns.
     */
    public function init(): void
    {
        $now = '2026-11-12 00:00:00';
        $this->records = [
            [
                'id' => 1,
                'title' => 'Global Sponsor',
                'placement' => 'below_nav',
                'target_type' => 'site',
                'sport_key' => null,
                'team_id' => null,
                'is_active' => true,
                'html' => '<div class="global-sponsor">Global sponsor</div>',
                'mode' => 'custom',
                'sizes_desktop' => null,
                'sizes_mobile' => null,
                'gpt_unit_path' => null,
                'display_order' => 0,
                'created' => $now,
                'modified' => $now,
            ],
            [
                'id' => 2,
                'title' => 'Basketball Sponsor',
                'placement' => 'below_nav',
                'target_type' => 'content',
                'sport_key' => 'basketball',
                'team_id' => null,
                'is_active' => true,
                'html' => '<div class="basketball-sponsor">Basketball sponsor</div>',
                'mode' => 'custom',
                'sizes_desktop' => null,
                'sizes_mobile' => null,
                'gpt_unit_path' => null,
                'display_order' => 1,
                'created' => $now,
                'modified' => $now,
            ],
            [
                'id' => 3,
                'title' => 'Basketball Team Sponsor',
                'placement' => 'below_nav',
                'target_type' => 'content',
                'sport_key' => 'basketball',
                'team_id' => 1,
                'is_active' => true,
                'html' => '<div class="team-sponsor">Team sponsor</div>',
                'mode' => 'custom',
                'sizes_desktop' => null,
                'sizes_mobile' => null,
                'gpt_unit_path' => null,
                'display_order' => 2,
                'created' => $now,
                'modified' => $now,
            ],
            [
                'id' => 4,
                'title' => 'Disabled Sponsor',
                'placement' => 'below_nav',
                'target_type' => 'site',
                'sport_key' => null,
                'team_id' => null,
                'is_active' => false,
                'html' => '<div class="disabled-sponsor">Disabled sponsor</div>',
                'mode' => 'custom',
                'sizes_desktop' => null,
                'sizes_mobile' => null,
                'gpt_unit_path' => null,
                'display_order' => 3,
                'created' => $now,
                'modified' => $now,
            ],
        ];
        parent::init();
    }
}
