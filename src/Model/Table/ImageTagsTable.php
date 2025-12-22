<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\Table;
use Cake\Validation\Validator;

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
