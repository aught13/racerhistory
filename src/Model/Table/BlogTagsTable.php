<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * @property \App\Model\Table\BlogPostsTable&\Cake\ORM\Association\BelongsToMany $BlogPosts
 * @method \App\Model\Entity\BlogTag newEmptyEntity()
 * @method \App\Model\Entity\BlogTag newEntity(array $data, array $options = [])
 * @method \App\Model\Entity\BlogTag[] newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\BlogTag get(mixed $primaryKey, array|string $finder = 'all', \Psr\SimpleCache\CacheInterface|string|null $cache = null, \Closure|string|null $cacheKey = null, mixed ...$args)
 * @method \App\Model\Entity\BlogTag findOrCreate(\Cake\ORM\Query\SelectQuery|callable|array $search, ?callable $callback = null, array $options = [])
 * @method \App\Model\Entity\BlogTag patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \App\Model\Entity\BlogTag[] patchEntities(iterable $entities, array $data, array $options = [])
 * @method \App\Model\Entity\BlogTag|false save(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method \App\Model\Entity\BlogTag saveOrFail(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method \App\Model\Entity\BlogTag[]|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\BlogTag>|false saveMany(iterable $entities, array $options = [])
 * @method \App\Model\Entity\BlogTag[]|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\BlogTag> saveManyOrFail(iterable $entities, array $options = [])
 * @method \App\Model\Entity\BlogTag[]|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\BlogTag>|false deleteMany(iterable $entities, array $options = [])
 * @method \App\Model\Entity\BlogTag[]|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\BlogTag> deleteManyOrFail(iterable $entities, array $options = [])
 * @mixin \Cake\ORM\Behavior\TimestampBehavior
 * @extends \Cake\ORM\Table<array{Timestamp: \Cake\ORM\Behavior\TimestampBehavior}>
 */
class BlogTagsTable extends Table
{
    /**
     * Initialize table configuration.
     *
     * @param array<string,mixed> $config Configuration array.
     */
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->setTable('blog_tags');
        $this->setPrimaryKey('id');
        $this->addBehavior('Timestamp');

        $this->belongsToMany('BlogPosts', [
            'joinTable' => 'blog_posts_blog_tags',
        ]);
    }

    /**
     * Validation rules.
     *
     * @param \Cake\Validation\Validator $validator
     */
    public function validationDefault(Validator $validator): Validator
    {
        $validator
            ->scalar('name')->maxLength('name', 150)->notEmptyString('name')
            ->scalar('slug')->maxLength('slug', 150)->notEmptyString('slug');

        return $validator;
    }

    /**
     * Build rules.
     *
     * @param \Cake\ORM\RulesChecker $rules
     * @return \Cake\ORM\RulesChecker
     */
    public function buildRules(RulesChecker $rules): RulesChecker
    {
        $rules->add($rules->isUnique(['slug'], 'Slug must be unique'));

        return $rules;
    }
}
