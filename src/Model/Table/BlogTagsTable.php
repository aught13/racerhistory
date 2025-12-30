<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

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
     */
    public function buildRules(RulesChecker $rules): RulesChecker
    {
        $rules->add($rules->isUnique(['slug'], 'Slug must be unique'));

        return $rules;
    }
}
