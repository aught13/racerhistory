<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * BlogTag Entity
 */
class BlogTag extends Entity
{
    /**
     * Mass assignment rules.
     *
     * @var array<string,bool>
     */
    protected array $_accessible = [
        'name' => true,
        'slug' => true,
        'created' => true,
        'modified' => true,
        'blog_posts' => true,
    ];
}
