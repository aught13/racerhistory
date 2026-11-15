<?php
declare(strict_types=1);

use Migrations\BaseMigration;

class CreateAdvertisingConfiguration extends BaseMigration
{
    public bool $autoId = false;

    /**
     * Create ad campaigns and move existing ad settings out of Site Options.
     */
    public function up(): void
    {
        $this->table('advertising_settings')
            ->addColumn('id', 'integer', ['autoIncrement' => true, 'signed' => false])
            ->addPrimaryKey(['id'])
            ->addColumn('global_script', 'text', ['null' => false])
            ->addColumn('publisher_id', 'string', ['limit' => 100, 'null' => false, 'default' => ''])
            ->addColumn('created', 'datetime', ['null' => false])
            ->addColumn('modified', 'datetime', ['null' => false])
            ->create();

        $this->table('advertisements')
            ->addColumn('id', 'integer', ['autoIncrement' => true, 'signed' => false])
            ->addPrimaryKey(['id'])
            ->addColumn('title', 'string', ['limit' => 190, 'null' => false])
            ->addColumn('placement', 'string', ['limit' => 64, 'null' => false])
            ->addColumn('target_type', 'string', ['limit' => 20, 'null' => false, 'default' => 'site'])
            ->addColumn('sport_key', 'string', ['limit' => 64, 'null' => true, 'default' => null])
            ->addColumn('team_id', 'integer', ['null' => true, 'default' => null])
            ->addColumn('is_active', 'boolean', ['null' => false, 'default' => false])
            ->addColumn('html', 'text', ['null' => false])
            ->addColumn('mode', 'string', ['limit' => 12, 'null' => false, 'default' => 'custom'])
            ->addColumn('sizes_desktop', 'text', ['null' => true, 'default' => null])
            ->addColumn('sizes_mobile', 'text', ['null' => true, 'default' => null])
            ->addColumn('gpt_unit_path', 'string', ['limit' => 255, 'null' => true, 'default' => null])
            ->addColumn('display_order', 'integer', ['null' => false, 'default' => 0])
            ->addColumn('created', 'datetime', ['null' => false])
            ->addColumn('modified', 'datetime', ['null' => false])
            ->addIndex(['placement', 'is_active', 'display_order'])
            ->addIndex(['sport_key'])
            ->addIndex(['team_id'])
            ->create();

        $legacyOptions = [];
        if ($this->hasTable('site_options')) {
            foreach ($this->fetchAll('SELECT option_key, value FROM site_options ORDER BY id') as $row) {
                $key = (string)($row['option_key'] ?? '');
                if ($key !== '') {
                    $value = $row['value'] ?? '';
                    $legacyOptions[$key] = is_scalar($value) ? (string)$value : '';
                }
            }
        }

        $now = date('Y-m-d H:i:s');
        $this->table('advertising_settings')->insert([
            [
                'id' => 1,
                'global_script' => $legacyOptions['ad_script'] ?? '',
                'publisher_id' => $legacyOptions['ad_publisher_id'] ?? '',
                'created' => $now,
                'modified' => $now,
            ],
        ])->saveData();

        $placements = [
            'below_nav' => 'Below Navigation',
            'below_content' => 'Below Content',
            'footer' => 'Footer',
            'homepage_mid' => 'Homepage Mid-Page',
            'news_after_first' => 'News After First Post',
            'news_every_fifth' => 'News Every Fifth Post',
            'news_sidebar_1' => 'News Sidebar 1',
            'news_sidebar_2' => 'News Sidebar 2',
        ];
        $legacyAdvertisements = [];
        foreach ($placements as $placement => $title) {
            $prefix = 'ad_' . $placement;
            $html = trim($legacyOptions[$prefix . '_html'] ?? '');
            $sizesDesktop = trim($legacyOptions[$prefix . '_sizes_desktop'] ?? '');
            $sizesMobile = trim($legacyOptions[$prefix . '_sizes_mobile'] ?? '');
            $gptUnitPath = trim($legacyOptions[$prefix . '_gpt_unit_path'] ?? '');
            $activeValue = strtolower(trim($legacyOptions[$prefix . '_active'] ?? ''));
            $isActive = in_array($activeValue, ['1', 'true', 'yes', 'on'], true);

            if (!$isActive && $html === '' && $sizesDesktop === '' && $sizesMobile === '' && $gptUnitPath === '') {
                continue;
            }

            $mode = strtolower(trim($legacyOptions[$prefix . '_mode'] ?? ''));
            if (!in_array($mode, ['custom', 'google', 'gpt'], true)) {
                $legacyGoogleValue = strtolower(trim($legacyOptions[$prefix . '_google_mode'] ?? ''));
                $legacyGoogleMode = in_array($legacyGoogleValue, ['1', 'true', 'yes', 'on'], true);
                $hasGoogleMarkup = str_contains(strtolower($html), 'adsbygoogle')
                    && preg_match('/\bdata-ad-slot\s*=\s*(["\'])\d+\1/i', $html) === 1;
                $mode = $legacyGoogleMode || $hasGoogleMarkup ? 'google' : 'custom';
            }

            $legacyAdvertisements[] = [
                'title' => $title,
                'placement' => $placement,
                'target_type' => 'site',
                'sport_key' => null,
                'team_id' => null,
                'is_active' => $isActive,
                'html' => $html,
                'mode' => $mode,
                'sizes_desktop' => $sizesDesktop !== '' ? $sizesDesktop : null,
                'sizes_mobile' => $sizesMobile !== '' ? $sizesMobile : null,
                'gpt_unit_path' => $gptUnitPath !== '' ? $gptUnitPath : null,
                'display_order' => count($legacyAdvertisements),
                'created' => $now,
                'modified' => $now,
            ];
        }

        if ($legacyAdvertisements !== []) {
            $this->table('advertisements')->insert($legacyAdvertisements)->saveData();
        }
    }

    /**
     * Drop the new advertising tables without altering the preserved legacy options.
     */
    public function down(): void
    {
        $this->table('advertisements')->drop()->save();
        $this->table('advertising_settings')->drop()->save();
    }
}
