<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\Table;

class BlogPostsBlogTagsTable extends Table
{
    /**
     * Initialize table configuration.
     *
     * @param array<string,mixed> $config Configuration.
     */
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->setTable('blog_posts_blog_tags');
        $this->setPrimaryKey('id');
        $this->addBehavior('Timestamp');
    }
}
