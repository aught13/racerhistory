<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\Datasource\EntityInterface;
use Cake\Event\EventInterface;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Utility\Text;
use Cake\Validation\Validator;

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

        $this->belongsTo('HeroImages', [
            'className' => 'Images',
            'foreignKey' => 'hero_image_id',
        ]);
        $this->belongsToMany('BlogTags', [
            'joinTable' => 'blog_posts_blog_tags',
        ]);
    }

    /**
     * Default validation rules.
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
            ->integer('hero_image_id')->allowEmptyString('hero_image_id');

        return $validator;
    }

    /**
     * Build rules.
     */
    public function buildRules(RulesChecker $rules): RulesChecker
    {
        $rules->add($rules->isUnique(['slug'], 'Slug must be unique'));
        $rules->add($rules->existsIn(['hero_image_id'], 'HeroImages'), ['allowNullableNulls' => true]);

        return $rules;
    }

    /**
     * Auto-populate slug from title when missing.
     *
     * @param \Cake\Event\EventInterface $event Event.
     * @param \Cake\Datasource\EntityInterface $entity Entity being saved.
     * @param \ArrayObject $options Save options.
     */
    public function beforeSave(EventInterface $event, EntityInterface $entity, \ArrayObject $options): void
    {
        if ($entity->isNew() && !$entity->get('slug') && $entity->get('title')) {
            $base = Text::slug((string)$entity->get('title')) ?: uniqid('post-', true);
            $entity->set('slug', mb_strtolower($base));
        }
    }
}
