<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * BlogPost Entity
 *
 * @property int $id
 * @property string $title
 * @property string $slug
 * @property string|null $excerpt
 * @property string $body
 * @property string $status
 * @property bool $is_published
 * @property \DateTimeInterface|null $published_at
 * @property int|null $hero_image_id
 * @property \DateTimeInterface|null $created
 * @property \DateTimeInterface|null $modified
 * @property \Cake\Collection\CollectionInterface|array<\App\Model\Entity\BlogTag> $blog_tags
 * @property \App\Model\Entity\Image|null $hero_image
 */
class BlogPost extends Entity
{
    /**
     * Mass assignment rules.
     *
     * @var array<string,bool>
     */
    protected array $_accessible = [
        'title' => true,
        'slug' => true,
        'excerpt' => true,
        'body' => true,
        'status' => true,
        'is_published' => true,
        'published_at' => true,
        'hero_image_id' => true,
        'created' => true,
        'modified' => true,
        'blog_tags' => true,
        'hero_image' => true,
    ];
}
