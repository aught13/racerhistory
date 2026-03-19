<?php
declare(strict_types=1);

use Migrations\BaseMigration;

class AddPinnedFieldsToBlogPosts extends BaseMigration
{
    public bool $autoId = false;

    public function up(): void
    {
        $this->table('blog_posts')
            ->addColumn('is_pinned', 'boolean', ['null' => false, 'default' => false])
            ->addColumn('pinned_rank', 'integer', ['null' => true, 'default' => null])
            ->addColumn('pinned_until', 'datetime', ['null' => true, 'default' => null])
            ->addIndex(['is_pinned'])
            ->addIndex(['pinned_rank'])
            ->addIndex(['pinned_until'])
            ->update();
    }

    public function down(): void
    {
        $this->table('blog_posts')
            ->removeColumn('is_pinned')
            ->removeColumn('pinned_rank')
            ->removeColumn('pinned_until')
            ->update();
    }
}
