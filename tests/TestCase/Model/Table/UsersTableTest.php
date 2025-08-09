<?php
declare(strict_types=1);

namespace App\Test\TestCase\Model\Table;

use App\Model\Table\UsersTable;
use Cake\TestSuite\TestCase;

/**
 * App\Model\Table\UsersTable Test Case
 */
class UsersTableTest extends TestCase
{
    /**
     * Test subject
     *
     * @var \App\Model\Table\UsersTable
     */
    protected $Users;

    /**
     * Fixtures
     *
     * @var array<string>
     */
    protected array $fixtures = [
        'app.Users',
    ];

    /**
     * setUp method
     *
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();
        $config = $this->getTableLocator()->exists('Users') ? [] : ['className' => UsersTable::class];
        $this->Users = $this->getTableLocator()->get('Users', $config);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown(): void
    {
        unset($this->Users);

        parent::tearDown();
    }

    /**
     * Test validationDefault method
     *
     * @return void
     */
    public function testValidationDefault(): void
    {
        $validator = $this->Users->getValidator('default');

        // Test username validation
        $this->assertTrue($validator->hasField('username'));
        $this->assertFalse($validator->isEmptyAllowed('username', false));

        // Test email validation
        $this->assertTrue($validator->hasField('email'));
        $this->assertFalse($validator->isEmptyAllowed('email', false));

        // Test password validation
        $this->assertTrue($validator->hasField('password'));
        $this->assertFalse($validator->isEmptyAllowed('password', false));
    }

    /**
     * Test validation rules with valid data
     *
     * @return void
     */
    public function testValidationSuccess(): void
    {
        $data = [
            'username' => 'testuser',
            'email' => 'test@example.com',
            'password' => 'password123',
            'role' => 'user',
            'status' => 'active'
        ];

        $user = $this->Users->newEntity($data);
        $this->assertEmpty($user->getErrors());
    }

    /**
     * Test validation with invalid username
     *
     * @return void
     */
    public function testValidationInvalidUsername(): void
    {
        $data = [
            'username' => 'ab', // Too short
            'email' => 'test@example.com',
            'password' => 'password123'
        ];

        $user = $this->Users->newEntity($data);
        $this->assertNotEmpty($user->getErrors());
        $this->assertArrayHasKey('username', $user->getErrors());
    }

    /**
     * Test validation with invalid email
     *
     * @return void
     */
    public function testValidationInvalidEmail(): void
    {
        $data = [
            'username' => 'testuser',
            'email' => 'invalid-email',
            'password' => 'password123'
        ];

        $user = $this->Users->newEntity($data);
        $this->assertNotEmpty($user->getErrors());
        $this->assertArrayHasKey('email', $user->getErrors());
    }

    /**
     * Test validation with short password
     *
     * @return void
     */
    public function testValidationShortPassword(): void
    {
        $data = [
            'username' => 'testuser',
            'email' => 'test@example.com',
            'password' => '123' // Too short
        ];

        $user = $this->Users->newEntity($data);
        $this->assertNotEmpty($user->getErrors());
        $this->assertArrayHasKey('password', $user->getErrors());
    }

    /**
     * Test password hashing before save
     *
     * @return void
     */
    public function testPasswordHashing(): void
    {
        $data = [
            'username' => 'hashtest',
            'email' => 'hash@example.com',
            'password' => 'plaintext123',
            'role' => 'user',
            'status' => 'active'
        ];

        $user = $this->Users->newEntity($data);
        $this->Users->save($user);

        // Password should be hashed and different from original
        $this->assertNotEquals('plaintext123', $user->password);
        $this->assertTrue(strlen($user->password) > 50); // Hashed passwords are longer
    }

    /**
     * Test findActive method
     *
     * @return void
     */
    public function testFindActive(): void
    {
        $query = $this->Users->find('active');
        $sql = $query->sql();

        $this->assertStringContainsString('active', $sql);
    }

    /**
     * Test createUser method success
     *
     * @return void
     */
    public function testCreateUserSuccess(): void
    {
        $data = [
            'username' => 'newuser',
            'email' => 'newuser@example.com',
            'password' => 'password123',
            'role' => 'user',
            'status' => 'active'
        ];

        $result = $this->Users->createUser($data);

        $this->assertNotNull($result);
        $this->assertEquals('newuser', $result->username);
        $this->assertEquals('newuser@example.com', $result->email);
    }

    /**
     * Test createUser method failure
     *
     * @return void
     */
    public function testCreateUserFailure(): void
    {
        $data = [
            'username' => 'ab', // Invalid - too short
            'email' => 'invalid-email',
            'password' => '123'
        ];

        $result = $this->Users->createUser($data);

        $this->assertNull($result);
    }
}
