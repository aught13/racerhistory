<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * @method \App\Model\Entity\Advertisement newEmptyEntity()
 * @method \App\Model\Entity\Advertisement newEntity(array $data, array $options = [])
 * @method \App\Model\Entity\Advertisement patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \App\Model\Entity\Advertisement|false save(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method \App\Model\Entity\Advertisement|false delete(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @mixin \Cake\ORM\Behavior\TimestampBehavior
 * @extends \Cake\ORM\Table<array{Timestamp: \Cake\ORM\Behavior\TimestampBehavior}>
 */
class AdvertisementsTable extends Table
{
    /**
     * Initialize table configuration.
     *
     * @param array<string,mixed> $config Configuration.
     */
    public function initialize(array $config): void
    {
        parent::initialize($config);
        $this->setTable('advertisements');
        $this->setPrimaryKey('id');
        $this->setDisplayField('title');
        $this->addBehavior('Timestamp');
    }

    /**
     * Default validation rules.
     *
     * @param \Cake\Validation\Validator $validator Validator instance.
     * @return \Cake\Validation\Validator
     */
    public function validationDefault(Validator $validator): Validator
    {
        $validator
            ->scalar('title')
            ->maxLength('title', 190)
            ->requirePresence('title', 'create')
            ->notEmptyString('title')
            ->inList('placement', [
                'below_nav',
                'below_content',
                'footer',
                'homepage_mid',
                'news_after_first',
                'news_every_fifth',
                'news_sidebar_1',
                'news_sidebar_2',
            ])
            ->inList('target_type', ['site', 'content'])
            ->scalar('sport_key')
            ->maxLength('sport_key', 64)
            ->allowEmptyString('sport_key')
            ->integer('team_id')
            ->allowEmptyString('team_id')
            ->boolean('is_active')
            ->requirePresence('is_active', 'create')
            ->notEmptyString('is_active')
            ->scalar('html')
            ->allowEmptyString('html')
            ->inList('mode', ['custom', 'google', 'gpt'])
            ->scalar('sizes_desktop')
            ->allowEmptyString('sizes_desktop')
            ->scalar('sizes_mobile')
            ->allowEmptyString('sizes_mobile')
            ->scalar('gpt_unit_path')
            ->maxLength('gpt_unit_path', 255)
            ->allowEmptyString('gpt_unit_path')
            ->integer('display_order')
            ->requirePresence('display_order', 'create')
            ->notEmptyString('display_order');

        return $validator;
    }
}
