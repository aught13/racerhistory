<?php
declare(strict_types=1);

use Migrations\BaseMigration;

class AddUserProfileAndOwnership extends BaseMigration
{
    /**
     * Change Method.
     *
     * More information on this method is available here:
     * https://book.cakephp.org/migrations/5/guides/writing-migrations/migration-methods.html#the-change-method
     *
     * @return void
     */
    public function change(): void
    {
        $users = $this->table('users');
        $users->changeColumn('role', 'string', ['default' => 'author', 'limit' => 50, 'null' => false])
              ->addColumn('display_name', 'string', ['limit' => 255, 'null' => true])
              ->addColumn('bio', 'text', ['null' => true])
              ->addColumn('profile_image_id', 'integer', ['signed' => false, 'null' => true])
              ->addColumn('website_url', 'string', ['limit' => 255, 'null' => true])
              ->addColumn('social_links', 'json', ['null' => true])
              ->addIndex(['profile_image_id'])
              ->addForeignKey('profile_image_id', 'images', 'id', ['delete' => 'SET_NULL', 'update' => 'CASCADE'])
              ->update();

        $blogPosts = $this->table('blog_posts');
        $blogPosts->addColumn('user_id', 'integer', ['null' => true])
                  ->addIndex(['user_id'])
                  ->addForeignKey('user_id', 'users', 'id', ['delete' => 'SET_NULL', 'update' => 'CASCADE'])
                  ->update();

        $images = $this->table('images');
        $images->addColumn('user_id', 'integer', ['null' => true])
               ->addColumn('photo_credit', 'string', ['limit' => 255, 'null' => true])
               ->addIndex(['user_id'])
               ->addForeignKey('user_id', 'users', 'id', ['delete' => 'SET_NULL', 'update' => 'CASCADE'])
               ->update();
    }
}
