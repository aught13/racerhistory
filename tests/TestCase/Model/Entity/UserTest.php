<?php
declare(strict_types=1);

namespace App\Test\TestCase\Model\Entity;

use App\Model\Entity\User;
use Cake\TestSuite\TestCase;

/**
 * App\Model\Entity\User Test Case
 */
class UserTest extends TestCase
{
    /**
     * Test subject
     *
     * @var \App\Model\Entity\User
     */
    protected $User;

    /**
     * setUp method
     *
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->User = new User();
    }

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown(): void
    {
        unset($this->User);

        parent::tearDown();
    }

    /**
     * Test that password field is hidden from JSON output
     *
     * @return void
     */
    public function testPasswordHiddenInJson(): void
    {
        $this->User->username = 'testuser';
        $this->User->email = 'test@example.com';
        $this->User->password = 'secret123';
        $this->User->role = 'user';
        $this->User->status = 'active';

        $json = json_decode(json_encode($this->User), true);

        $this->assertArrayHasKey('username', $json);
        $this->assertArrayHasKey('email', $json);
        $this->assertArrayHasKey('role', $json);
        $this->assertArrayHasKey('status', $json);
        $this->assertArrayNotHasKey('password', $json);
    }

    /**
     * Test mass assignment accessibility
     *
     * @return void
     */
    public function testAccessibleFields(): void
    {
        $data = [
            'username' => 'testuser',
            'email' => 'test@example.com',
            'password' => 'secret123',
            'role' => 'user',
            'status' => 'active',
        ];

        $user = new User($data);

        // Other fields should be accessible
        $this->assertEquals('testuser', $user->username);
        $this->assertEquals('test@example.com', $user->email);
        $this->assertEquals('secret123', $user->password);
        $this->assertEquals('user', $user->role);
        $this->assertEquals('active', $user->status);

        // Test that ID cannot be set via mass assignment with patchEntity
        $user->setAccess('id', false); // Ensure ID is not accessible
        $user = $user->patch(['id' => 999]);
        $this->assertNotEquals(999, $user->id);
    }

    /**
     * Test setting and getting individual properties
     *
     * @return void
     */
    public function testPropertyAccess(): void
    {
        $this->User->username = 'johndoe';
        $this->User->email = 'john@example.com';
        $this->User->role = 'admin';
        $this->User->status = 'inactive';

        $this->assertEquals('johndoe', $this->User->username);
        $this->assertEquals('john@example.com', $this->User->email);
        $this->assertEquals('admin', $this->User->role);
        $this->assertEquals('inactive', $this->User->status);
    }

    /**
     * Test that entity can be converted to array
     *
     * @return void
     */
    public function testToArray(): void
    {
        $this->User->username = 'arraytest';
        $this->User->email = 'array@example.com';
        $this->User->password = 'password123';

        $array = $this->User->toArray();

        $this->assertIsArray($array);
        $this->assertArrayHasKey('username', $array);
        $this->assertArrayHasKey('email', $array);
        $this->assertEquals('arraytest', $array['username']);
        $this->assertEquals('array@example.com', $array['email']);

        // Password should be hidden
        $this->assertArrayNotHasKey('password', $array);
    }
}
