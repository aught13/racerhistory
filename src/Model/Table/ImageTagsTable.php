<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * @property \App\Model\Table\ImagesTable&\Cake\ORM\Association\BelongsToMany $Images
 * @method \App\Model\Entity\ImageTag newEmptyEntity()
 * @method \App\Model\Entity\ImageTag newEntity(array $data, array $options = [])
 * @method \App\Model\Entity\ImageTag[] newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\ImageTag get(mixed $primaryKey, array|string $finder = 'all', \Psr\SimpleCache\CacheInterface|string|null $cache = null, \Closure|string|null $cacheKey = null, mixed ...$args)
 * @method \App\Model\Entity\ImageTag findOrCreate(\Cake\ORM\Query\SelectQuery|callable|array $search, ?callable $callback = null, array $options = [])
 * @method \App\Model\Entity\ImageTag patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \App\Model\Entity\ImageTag[] patchEntities(iterable $entities, array $data, array $options = [])
 * @method \App\Model\Entity\ImageTag|false save(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method \App\Model\Entity\ImageTag saveOrFail(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method \App\Model\Entity\ImageTag[]|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\ImageTag>|false saveMany(iterable $entities, array $options = [])
 * @method \App\Model\Entity\ImageTag[]|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\ImageTag> saveManyOrFail(iterable $entities, array $options = [])
 * @method \App\Model\Entity\ImageTag[]|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\ImageTag>|false deleteMany(iterable $entities, array $options = [])
 * @method \App\Model\Entity\ImageTag[]|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\ImageTag> deleteManyOrFail(iterable $entities, array $options = [])
 * @mixin \Cake\ORM\Behavior\TimestampBehavior
 * @extends \Cake\ORM\Table<array{Timestamp: \Cake\ORM\Behavior\TimestampBehavior}>
 */
class ImageTagsTable extends Table
{
    /**
     * Initialize table configuration.
     *
     * @param array<string,mixed> $config Configuration array.
     * @return void
     */
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->setTable('image_tags');
        $this->setPrimaryKey('id');
        $this->addBehavior('Timestamp');

        $this->belongsToMany('Images', [
            'joinTable' => 'images_image_tags',
            'foreignKey' => 'image_tag_id',
            'targetForeignKey' => 'image_id',
        ]);
    }

    /**
     * Validation rules.
     *
     * @param \Cake\Validation\Validator $validator Validator instance.
     * @return \Cake\Validation\Validator
     */
    public function validationDefault(Validator $validator): Validator
    {
        $validator
            ->scalar('name')->maxLength('name', 150)->notEmptyString('name')
            ->scalar('slug')->maxLength('slug', 150)->notEmptyString('slug');

        return $validator;
    }
}
