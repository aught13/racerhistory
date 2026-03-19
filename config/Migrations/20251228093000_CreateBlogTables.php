<?php
declare(strict_types=1);

use Migrations\BaseMigration;

class CreateBlogTables extends BaseMigration
{
    public bool $autoId = false;

    public function up(): void
    {
        $this->table('blog_posts')
            ->addColumn('id', 'integer', ['autoIncrement' => true, 'signed' => false])
            ->addPrimaryKey(['id'])
            ->addColumn('title', 'string', ['limit' => 190, 'null' => false])
            ->addColumn('slug', 'string', ['limit' => 190, 'null' => false])
            ->addColumn('excerpt', 'text', ['null' => true, 'default' => null])
            ->addColumn('body', 'text', ['null' => false])
            ->addColumn('status', 'string', ['limit' => 20, 'null' => false, 'default' => 'draft'])
            ->addColumn('is_published', 'boolean', ['null' => false, 'default' => false])
            ->addColumn('published_at', 'datetime', ['null' => true, 'default' => null])
            ->addColumn('hero_image_id', 'integer', ['null' => true, 'signed' => false, 'default' => null])
            ->addColumn('created', 'datetime', ['null' => true, 'default' => null])
            ->addColumn('modified', 'datetime', ['null' => true, 'default' => null])
            ->addIndex(['slug'], ['unique' => true])
            ->addIndex(['published_at'])
            ->addIndex(['hero_image_id'])
            ->create();

        $this->table('blog_tags')
            ->addColumn('id', 'integer', ['autoIncrement' => true, 'signed' => false])
            ->addPrimaryKey(['id'])
            ->addColumn('name', 'string', ['limit' => 150, 'null' => false])
            ->addColumn('slug', 'string', ['limit' => 150, 'null' => false])
            ->addColumn('created', 'datetime', ['null' => true, 'default' => null])
            ->addColumn('modified', 'datetime', ['null' => true, 'default' => null])
            ->addIndex(['slug'], ['unique' => true])
            ->create();

        $this->table('blog_posts_blog_tags')
            ->addColumn('id', 'integer', ['autoIncrement' => true, 'signed' => false])
            ->addPrimaryKey(['id'])
            ->addColumn('blog_post_id', 'integer', ['null' => false, 'signed' => false])
            ->addColumn('blog_tag_id', 'integer', ['null' => false, 'signed' => false])
            ->addColumn('created', 'datetime', ['null' => true, 'default' => null])
            ->addColumn('modified', 'datetime', ['null' => true, 'default' => null])
            ->addIndex(['blog_post_id'])
            ->addIndex(['blog_tag_id'])
            ->addIndex(['blog_post_id', 'blog_tag_id'], ['unique' => true])
            ->create();
    }

    public function down(): void
    {
        $this->table('blog_posts_blog_tags')->drop()->save();
        $this->table('blog_posts')->drop()->save();
        $this->table('blog_tags')->drop()->save();
    }
}
