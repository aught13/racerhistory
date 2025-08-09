<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\Table;
use Cake\Validation\Validator;
use Authentication\PasswordHasher\DefaultPasswordHasher;
use Cake\Event\EventInterface;
use Cake\Datasource\EntityInterface;

/**
 * Users Model
 *
 * Handles user data operations, validation, and password management.
 * This table manages user accounts, authentication credentials, and user status.
 *
 * @method \App\Model\Entity\User newEmptyEntity()
 * @method \App\Model\Entity\User newEntity(array $data, array $options = [])
 * @method \App\Model\Entity\User[] newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\User get($primaryKey, $options = [])
 * @method \App\Model\Entity\User findOrCreate($search, ?callable $callback = null, $options = [])
 * @method \App\Model\Entity\User patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \App\Model\Entity\User[] patchEntities(iterable $entities, array $data, array $options = [])
 * @method \App\Model\Entity\User|false save(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\User saveOrFail(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\User[]|\Cake\Datasource\ResultSetInterface|false saveMany(iterable $entities, $options = [])
 * @method \App\Model\Entity\User[]|\Cake\Datasource\ResultSetInterface saveManyOrFail(iterable $entities, $options = [])
 * @method \App\Model\Entity\User[]|\Cake\Datasource\ResultSetInterface|false deleteMany(iterable $entities, $options = [])
 * @method \App\Model\Entity\User[]|\Cake\Datasource\ResultSetInterface deleteManyOrFail(iterable $entities, $options = [])
 */
class UsersTable extends Table
{
    /**
     * Initialize method
     *
     * @param array $config The configuration for the Table.
     * @return void
     */
    public function initialize(array $config): void
    {
        parent::initialize($config);
        $this->setTable('users');
        $this->setDisplayField('username');  // Changed from 'email' to 'username' since that's what we're using for login
        $this->setPrimaryKey('id');

        $this->addBehavior('Timestamp', [
            'created' => 'created',    // Fixed: use 'created' not 'created_at'
            'modified' => 'modified',  // Fixed: use 'modified' not 'updated_at'
        ]);
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
            ->integer('id')
            ->allowEmptyString('id', null, 'create')

            ->requirePresence('username', 'create')
            ->notEmptyString('username', 'A username is required')
            ->minLength('username', 3, 'Username must be at least 3 characters')
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
            ->notEmptyString('password')
            ->minLength('password', 8, 'Password must be at least 8 characters');

        return $validator;
    }

    /**
     * Before save callback.
     *
     * Automatically hashes passwords when they are set or changed.
     *
     * @param \Cake\Event\EventInterface $event The beforeSave event.
     * @param \Cake\Datasource\EntityInterface $entity The entity being saved.
     * @param \ArrayObject $options Additional options.
     * @return void
     */
    public function beforeSave(EventInterface $event, EntityInterface $entity, \ArrayObject $options): void
    {
        if (!empty($entity->password) && $entity->isDirty('password')) {
            $entity->password = (new DefaultPasswordHasher())->hash($entity->password);
        }
    }

    /**
     * Find active users.
     *
     * @param \Cake\ORM\Query $query The query to modify.
     * @param array $options Options for the find.
     * @return \Cake\ORM\Query
     */
    public function findActive($query, $options)
    {
        return $query->where(['active' => true]);
    }

    /**
     * Create a new user with provided data.
     *
     * @param array $data User data to save.
     * @return \App\Model\Entity\User|null Created user entity or null on failure.
     */
    public function createUser(array $data): ?EntityInterface
    {
        $user = $this->newEntity($data);
        if ($this->save($user)) {
            return $user;
        }
        return null;
    }
}
