<?php
declare(strict_types=1);

namespace App\Model\Table;

use App\Model\Entity\User;
use ArrayObject;
use Authentication\PasswordHasher\DefaultPasswordHasher;
use Cake\Datasource\EntityInterface;
use Cake\Event\EventInterface;
use Cake\I18n\DateTime;
use Cake\ORM\Query\SelectQuery;
use Cake\ORM\Table;
use Cake\ORM\TableRegistry;
use Cake\Validation\Validator;
use Throwable;

/**
 * Users Model
 *
 * Handles user data operations, validation, and password management.
 * This table manages user accounts, authentication credentials, and user status.
 *
 * @method \App\Model\Entity\User newEmptyEntity()
 * @method \App\Model\Entity\User newEntity(array $data, array $options = [])
 * @method \App\Model\Entity\User[] newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\User get(mixed $primaryKey, array|string $finder = 'all', \Psr\SimpleCache\CacheInterface|string|null $cache = null, \Closure|string|null $cacheKey = null, mixed ...$args)
 * @method \App\Model\Entity\User findOrCreate(\Cake\ORM\Query\SelectQuery|callable|array $search, ?callable $callback = null, array $options = [])
 * @method \App\Model\Entity\User patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \App\Model\Entity\User[] patchEntities(iterable $entities, array $data, array $options = [])
 * @method \App\Model\Entity\User|false save(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method \App\Model\Entity\User saveOrFail(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method \App\Model\Entity\User[]|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\User>|false saveMany(iterable $entities, array $options = [])
 * @method \App\Model\Entity\User[]|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\User> saveManyOrFail(iterable $entities, array $options = [])
 * @method \App\Model\Entity\User[]|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\User>|false deleteMany(iterable $entities, array $options = [])
 * @method \App\Model\Entity\User[]|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\User> deleteManyOrFail(iterable $entities, array $options = [])
 * @mixin \Cake\ORM\Behavior\TimestampBehavior
 * @extends \Cake\ORM\Table<array{Timestamp: \Cake\ORM\Behavior\TimestampBehavior}>
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
        $this->setDisplayField('username'); // Changed from 'email' to 'username' since that's what we're using for login
        $this->setPrimaryKey('id');

        $this->addBehavior('Timestamp', [
            'created' => 'created', // Fixed: use 'created' not 'created_at'
            'modified' => 'modified', // Fixed: use 'modified' not 'updated_at'
        ]);

        $this->belongsTo('ProfileImages', [
            'className' => 'Images',
            'foreignKey' => 'profile_image_id',
        ]);
        $this->belongsTo('Roles', [
            'foreignKey' => 'role_id',
            'propertyName' => 'role_record',
        ]);
        $this->hasMany('BlogPosts', [
            'foreignKey' => 'user_id',
        ]);
        $this->hasMany('Uploads', [
            'className' => 'Images',
            'foreignKey' => 'user_id',
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
                'message' => 'This username is already taken.',
            ])

            ->email('email')
            ->requirePresence('email', 'create')
            ->notEmptyString('email')
            ->add('email', 'unique', [
                'rule' => 'validateUnique',
                'provider' => 'table',
                'message' => 'This email is already registered.',
            ])

            ->requirePresence('password', 'create')
            ->notEmptyString('password')
            ->minLength('password', 8, 'Password must be at least 8 characters')

            ->allowEmptyString('display_name')
            ->allowEmptyString('bio')
            ->allowEmptyString('website_url')
            ->integer('role_id')
            ->allowEmptyString('role_id')
            ->allowEmptyString('role')
            ->allowEmptyArray('social_links');

        return $validator;
    }

    /**
     * Before save callback.
     *
     * Automatically hashes passwords when they are set or changed.
     * Synchronizes 'active' field with 'status' for backward compatibility.
     *
     * @param \Cake\Event\EventInterface       $event   The beforeSave event.
     * @param \App\Model\Entity\User $entity  The entity being saved.
     * @param \ArrayObject                     $options Additional options.
     * @return void
     */
    public function beforeSave(EventInterface $event, EntityInterface $entity, ArrayObject $options): void
    {
        // Type assertion for IDE and static analysis
        assert($entity instanceof User);

        // Hash password if changed
        if (!empty($entity->password) && $entity->isDirty('password')) {
            $entity->password = (new DefaultPasswordHasher())->hash($entity->password);
        }

        // Synchronize 'active' boolean with 'status' string for backward compatibility
        if ($entity->isDirty('status')) {
            $entity->active = ($entity->status === 'active');
        }
        if ($entity->isDirty('active')) {
            $entity->status = $entity->active ? 'active' : 'inactive';
        }

        // Set is_superuser based on role
        if ($entity->isDirty('role')) {
            $entity->is_superuser = ($entity->role === 'admin');
        }

        // Keep legacy string role and new role_id in sync during migration to
        // database-backed RBAC. If the roles table is not available yet (tests,
        // partial migrations), fail soft and preserve current behavior.
        try {
            $rolesTable = TableRegistry::getTableLocator()->get('Roles');

            if ($entity->isDirty('role_id') && $entity->role_id) {
                $roleRow = $rolesTable->find()
                    ->select(['name'])
                    ->where(['id' => (int)$entity->role_id])
                    ->disableHydration()
                    ->first();
                if (is_array($roleRow) && !empty($roleRow['name'])) {
                    $entity->role = strtolower((string)$roleRow['name']);
                    $entity->is_superuser = ($entity->role === 'admin');
                }
            } elseif ($entity->isDirty('role') && !empty($entity->role) && !$entity->role_id) {
                $normalizedRole = match (strtolower((string)$entity->role)) {
                    'author' => 'blogger',
                    default => strtolower((string)$entity->role),
                };
                $roleRow = $rolesTable->find()
                    ->select(['id'])
                    ->where(['LOWER(name)' => $normalizedRole])
                    ->disableHydration()
                    ->first();
                if (is_array($roleRow) && !empty($roleRow['id'])) {
                    $entity->role_id = (int)$roleRow['id'];
                }
            }
        } catch (Throwable) {
            // Preserve existing save flow when the RBAC schema is not yet present.
        }

        // Set activation_date when user becomes active
        if ($entity->isDirty('active') && $entity->active && !$entity->activation_date) {
            $entity->activation_date = new DateTime();
        }
    }

    /**
     * Find active users.
     *
     * @param \Cake\ORM\Query\SelectQuery $query   The query to modify.
     * @param array                       $options Options for the find.
     * @return \Cake\ORM\Query\SelectQuery
     */
    public function findActive(SelectQuery $query, array $options): SelectQuery
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
