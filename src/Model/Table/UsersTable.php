<?php
namespace App\Model\Table;

use Cake\ORM\Table;
use Cake\Validation\Validator;
use Authentication\PasswordHasher\DefaultPasswordHasher;

class UsersTable extends Table
{
    public function beforeSave($event, $entity, $options)
    {
        if (!empty($entity->password) && $entity->isDirty('password')) {
            $entity->password = (new DefaultPasswordHasher())->hash($entity->password);
        }
    }

    public function initialize(array $config): void
    {
        parent::initialize($config);
        $this->setTable('users');
        $this->setDisplayField('email');
        $this->setPrimaryKey('id');

        $this->addBehavior('Timestamp', [
            'created' => 'created_at',
            'modified' => 'updated_at',
        ]);
    }

    public function validationDefault(Validator $validator): Validator
    {
        $validator
            ->requirePresence('username', 'create')
            ->notEmptyString('username', 'A username is required')
            ->add('username', 'unique', [
                'rule' => 'validateUnique',
                'provider' => 'table',
                'message' => 'This username is already taken.'
            ])
            ->email('email')
            ->requirePresence('email', 'create')
            ->notEmptyString('email')
            ->add('email', 'unique', [
                'rule' => 'validateUnique',
                'provider' => 'table',
                'message' => 'This email is already registered.'
            ])
            ->requirePresence('password', 'create')
            ->notEmptyString('password');
        return $validator;
    }
}