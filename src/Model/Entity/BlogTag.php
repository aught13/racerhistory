<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * BlogTag Entity
 *
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property \Cake\I18n\DateTime|null $created
 * @property \Cake\I18n\DateTime|null $modified
 * @property \App\Model\Entity\BlogPost[] $blog_posts
 * @property \Cake\ORM\Entity $_joinData
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
