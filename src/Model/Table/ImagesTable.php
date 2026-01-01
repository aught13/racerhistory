<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\Datasource\EntityInterface;
use Cake\Event\EventInterface;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Utility\Text;
use Cake\Validation\Validator;

class ImagesTable extends Table
{
    /**
     * Initialize table.
     *
     * @param array<string,mixed> $config Config.
     */
    public function initialize(array $config): void
    {
        parent::initialize($config);
        $this->setTable('images');
        $this->setPrimaryKey('id');
        $this->addBehavior('Timestamp');
        $this->belongsToMany('ImageTags', [
            'foreignKey' => 'image_id',
            'targetForeignKey' => 'image_tag_id',
            'joinTable' => 'images_image_tags',
        ]);
    }

    /**
     * Validation rules.
     */
    public function validationDefault(Validator $validator): Validator
    {
        $validator
            ->scalar('filename')->maxLength('filename', 255)->notEmptyString('filename')
            ->scalar('storage_subdir')->maxLength('storage_subdir', 16)->allowEmptyString('storage_subdir')
            ->scalar('storage_path')->maxLength('storage_path', 190)->notEmptyString('storage_path')
            ->scalar('original_name')->allowEmptyString('original_name')
            ->scalar('mime')->maxLength('mime', 100)->notEmptyString('mime')
            ->scalar('ext')->allowEmptyString('ext')
            ->integer('byte_size')->notEmptyString('byte_size')
            ->integer('width')->allowEmptyString('width')
            ->integer('height')->allowEmptyString('height')
            ->scalar('hash')->maxLength('hash', 64)->notEmptyString('hash')
            ->scalar('status')->maxLength('status', 20)->notEmptyString('status');

        return $validator;
    }

    /**
     * Rules checker (unique hash).
     */
    public function buildRules(RulesChecker $rules): RulesChecker
    {
        $rules->add($rules->isUnique(['hash'], 'Duplicate image already exists.'));

        return $rules;
    }

    /**
     * Before save hook.
     *
     * @param \Cake\Event\EventInterface $event Event.
     * @param \Cake\Datasource\EntityInterface $entity Entity.
     * @param mixed $options Options (Cake core may pass ArrayObject)
     * @phpcsSuppress SlevomatCodingStandard\TypeHints\ParameterTypeHint.MissingNativeTypeHint
     */
    public function beforeSave(EventInterface $event, EntityInterface $entity, mixed $options): void
    {
        if ($entity->isNew() && !$entity->get('filename')) {
            $entity->set('filename', Text::uuid());
        }
    }
}
