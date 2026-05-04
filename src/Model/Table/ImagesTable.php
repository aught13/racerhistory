<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\Datasource\EntityInterface;
use Cake\Event\EventInterface;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Utility\Text;
use Cake\Validation\Validator;

/**
 * @property \App\Model\Table\ImageTagsTable&\Cake\ORM\Association\BelongsToMany $ImageTags
 * @method \App\Model\Entity\Image newEmptyEntity()
 * @method \App\Model\Entity\Image newEntity(array $data, array $options = [])
 * @method \App\Model\Entity\Image[] newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\Image get(mixed $primaryKey, array|string $finder = 'all', \Psr\SimpleCache\CacheInterface|string|null $cache = null, \Closure|string|null $cacheKey = null, mixed ...$args)
 * @method \App\Model\Entity\Image findOrCreate(\Cake\ORM\Query\SelectQuery|callable|array $search, ?callable $callback = null, array $options = [])
 * @method \App\Model\Entity\Image patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \App\Model\Entity\Image[] patchEntities(iterable $entities, array $data, array $options = [])
 * @method \App\Model\Entity\Image|false save(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method \App\Model\Entity\Image saveOrFail(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method \App\Model\Entity\Image[]|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\Image>|false saveMany(iterable $entities, array $options = [])
 * @method \App\Model\Entity\Image[]|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\Image> saveManyOrFail(iterable $entities, array $options = [])
 * @method \App\Model\Entity\Image[]|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\Image>|false deleteMany(iterable $entities, array $options = [])
 * @method \App\Model\Entity\Image[]|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\Image> deleteManyOrFail(iterable $entities, array $options = [])
 * @mixin \Cake\ORM\Behavior\TimestampBehavior
 * @extends \Cake\ORM\Table<array{Timestamp: \Cake\ORM\Behavior\TimestampBehavior}>
 */
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
     *
     * @param \Cake\Validation\Validator $validator
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
     *
     * @param \Cake\ORM\RulesChecker $rules
     */
    public function buildRules(RulesChecker $rules): RulesChecker
    {
        $rules->add($rules->isUnique(['hash'], 'Duplicate image already exists.'));

        return $rules;
    }

    /**
     * Before save hook.
     *
     * @param \Cake\Event\EventInterface $event
     * @param \Cake\Datasource\EntityInterface $entity
     * @param mixed $options
     * @phpcsSuppress SlevomatCodingStandard\TypeHints\ParameterTypeHint.MissingNativeTypeHint
     */
    public function beforeSave(EventInterface $event, EntityInterface $entity, mixed $options): void
    {
        if ($entity->isNew() && !$entity->get('filename')) {
            $entity->set('filename', Text::uuid());
        }
    }
}
