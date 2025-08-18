<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\Table;
use Cake\Validation\Validator;

class ImageUsagesTable extends Table
{
    /**
     * Initialize table.
     *
     * @param array<string,mixed> $config Config.
     */
    public function initialize(array $config): void
    {
        parent::initialize($config);
        $this->setTable('image_usages');
        $this->setPrimaryKey('id');
        $this->belongsTo('Images', ['foreignKey' => 'image_id']);
    }

    /**
     * Validation rules.
     */
    public function validationDefault(Validator $validator): Validator
    {
        $validator
            ->integer('image_id')->notEmptyString('image_id')
            ->scalar('model')->maxLength('model', 120)->notEmptyString('model')
            ->integer('foreign_key')->notEmptyString('foreign_key')
            ->scalar('field')->maxLength('field', 60)->notEmptyString('field');

        return $validator;
    }
}
