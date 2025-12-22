<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\Table;
use Cake\Validation\Validator;

class ImagesImageTagsTable extends Table
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

        $this->setTable('images_image_tags');
        $this->setPrimaryKey('id');
        $this->addBehavior('Timestamp');

        $this->belongsTo('Images', ['foreignKey' => 'image_id']);
        $this->belongsTo('ImageTags', ['foreignKey' => 'image_tag_id']);
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
            ->integer('image_id')->notEmptyString('image_id')
            ->integer('image_tag_id')->notEmptyString('image_tag_id');

        return $validator;
    }
}
