<?php
declare(strict_types=1);

namespace App\Model\Table;

use ArrayObject;
use Cake\Datasource\EntityInterface;
use Cake\Event\EventInterface;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Utility\Text;
use Cake\Validation\Validator;

/**
 * @property \App\Model\Table\BlogTagsTable&\Cake\ORM\Association\BelongsToMany $BlogTags
 * @method \App\Model\Entity\BlogPost newEmptyEntity()
 * @method \App\Model\Entity\BlogPost newEntity(array $data, array $options = [])
 * @method \App\Model\Entity\BlogPost[] newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\BlogPost get(mixed $primaryKey, array|string $finder = 'all', \Psr\SimpleCache\CacheInterface|string|null $cache = null, \Closure|string|null $cacheKey = null, mixed ...$args)
 * @method \App\Model\Entity\BlogPost findOrCreate(\Cake\ORM\Query\SelectQuery|callable|array $search, ?callable $callback = null, array $options = [])
 * @method \App\Model\Entity\BlogPost patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \App\Model\Entity\BlogPost[] patchEntities(iterable $entities, array $data, array $options = [])
 * @method \App\Model\Entity\BlogPost|false save(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method \App\Model\Entity\BlogPost saveOrFail(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method \App\Model\Entity\BlogPost[]|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\BlogPost>|false saveMany(iterable $entities, array $options = [])
 * @method \App\Model\Entity\BlogPost[]|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\BlogPost> saveManyOrFail(iterable $entities, array $options = [])
 * @method \App\Model\Entity\BlogPost[]|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\BlogPost>|false deleteMany(iterable $entities, array $options = [])
 * @method \App\Model\Entity\BlogPost[]|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\BlogPost> deleteManyOrFail(iterable $entities, array $options = [])
 * @mixin \Cake\ORM\Behavior\TimestampBehavior
 * @extends \Cake\ORM\Table<array{Timestamp: \Cake\ORM\Behavior\TimestampBehavior}>
 */
class BlogPostsTable extends Table
{
    /**
     * Initialize table configuration.
     *
     * @param array<string,mixed> $config Configuration.
     */
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->setTable('blog_posts');
        $this->setPrimaryKey('id');
        $this->addBehavior('Timestamp');
        $this->belongsToMany('BlogTags', [
            'joinTable' => 'blog_posts_blog_tags',
        ]);
    }

    /**
     * Default validation rules.
     *
     * @param \Cake\Validation\Validator $validator
     */
    public function validationDefault(Validator $validator): Validator
    {
        $validator
            ->scalar('title')->maxLength('title', 190)->notEmptyString('title')
            ->scalar('slug')->maxLength('slug', 190)->notEmptyString('slug')
            ->scalar('excerpt')->allowEmptyString('excerpt')
            ->scalar('body')->notEmptyString('body')
            ->scalar('status')->maxLength('status', 20)->notEmptyString('status')
            ->boolean('is_published')->notEmptyString('is_published')
            ->dateTime('published_at')->allowEmptyDateTime('published_at')
            ->integer('hero_image_id')->allowEmptyString('hero_image_id')
            ->boolean('is_pinned')->allowEmptyString('is_pinned')
            ->integer('pinned_rank')->allowEmptyString('pinned_rank')
            ->dateTime('pinned_until')->allowEmptyDateTime('pinned_until');

        return $validator;
    }

    /**
     * Build rules.
     *
     * @param \Cake\ORM\RulesChecker $rules
     */
    public function buildRules(RulesChecker $rules): RulesChecker
    {
        $rules->add($rules->isUnique(['slug'], 'Slug must be unique'));

        return $rules;
    }

    /**
     * Auto-populate slug from title when missing.
     *
     * @param \Cake\Event\EventInterface $event
     * @param \Cake\Datasource\EntityInterface $entity
     * @param \ArrayObject $options
     */
    public function beforeSave(EventInterface $event, EntityInterface $entity, ArrayObject $options): void
    {
        if ($entity->isNew() && !$entity->get('slug') && $entity->get('title')) {
            $base = Text::slug((string)$entity->get('title')) ?: uniqid('post-', true);
            $entity->set('slug', mb_strtolower($base));
        }
    }
}
