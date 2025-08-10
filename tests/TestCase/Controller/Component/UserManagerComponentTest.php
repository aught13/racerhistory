<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller\Component;

use App\Controller\Component\UserManagerComponent;
use Cake\ORM\TableRegistry;
use Authentication\Authenticator\ResultInterface;
use Authentication\IdentityInterface;
use Cake\Controller\Component\FlashComponent;
use Cake\Controller\ComponentRegistry;
use Cake\Http\Response;
use Cake\Http\ServerRequest;
use Cake\TestSuite\TestCase;

class UserManagerComponentTest extends TestCase
{
    /**
     * Fixtures to load.
     *
     * Using protected visibility so Cake's fixture system reliably detects them.
     *
     * @var array<string>
     */
    public array $fixtures = ['app.Users'];

    /** @var StubController */
    public $controller;
    /** @var \App\Controller\Component\UserManagerComponent */
    public $component;
    /** @var \App\Model\Table\UsersTable */
    public $Users;

    public function setUp(): void
    {
        parent::setUp();
        // Explicit seed baseline users for component tests (manual since fixture extension removed)
        $users = TableRegistry::getTableLocator()->get('Users');
        $users->deleteAll([]);
        $baseline = [
            [
                'id' => 1,
                'username' => 'admin',
                'email' => 'admin@example.com',
                'password' => '$2y$10$usesomesillystringfore7hnbRJHxXVLeakoG8K30oukPsA.ztMG',
                'role' => 'admin',
                'status' => 'active',
            ],
            [
                'id' => 2,
                'username' => 'user',
                'email' => 'user@example.com',
                'password' => '$2y$10$usesomesillystringfore7hnbRJHxXVLeakoG8K30oukPsA.ztMG',
                'role' => 'user',
                'status' => 'inactive',
            ],
        ];
        foreach ($baseline as $row) {
            $entity = $users->newEntity($row, ['accessibleFields' => ['*' => true]]);
            $users->saveOrFail($entity);
        }
        $request = new ServerRequest();
        $this->controller = new StubController($request);

    // Use test fixture-backed table locator
    $this->Users = $this->getTableLocator()->get('Users');
    // Ensure controller uses the same locator so fetchTable() hits fixture data
    $this->controller->setTableLocator($this->getTableLocator());
    $this->controller->Users = $this->Users;


        // Shared ComponentRegistry
        $componentRegistry = new ComponentRegistry();
        $componentRegistry->setController($this->controller);

        // Flash Component (real instance)
        $this->controller->Flash = new FlashComponent($componentRegistry);

        // Authentication Component
        $this->controller->Authentication = $this->getMockBuilder('Authentication\Controller\Component\AuthenticationComponent')
            ->disableOriginalConstructor()
            ->getMock();
        $this->controller->Authentication->method('getResult')->willReturn(
            $this->createMock(ResultInterface::class),
        );
        $this->controller->Authentication->method('getIdentity')->willReturn(
            $this->createMock(IdentityInterface::class),
        );
        $this->controller->Authentication->method('logout')->willReturn(null);

        $this->component = new UserManagerComponent($componentRegistry);
    }

    // Isolated unit tests

    public function testCreateUser(): void
    {
        $result = $this->component->createUser($this->controller, [
            'username' => 'testuser',
            'password' => 'testpass123',
            'email' => 'testuser@example.com',
            'role' => 'user',
            'status' => 'active',
        ]);
        $this->assertInstanceOf(Response::class, $result);
        $this->assertNotEmpty($this->Users->find()->where(['username' => 'testuser'])->first());
    }

    public function testUpdateUser(): void
    {
        $result = $this->component->updateUser($this->controller, 1, ['username' => 'updated', 'password' => 'updated']);
        $this->assertInstanceOf(Response::class, $result);
    }

    public function testDeleteUser(): void
    {
        $mockRequest = $this->getMockBuilder(ServerRequest::class)
            ->disableOriginalConstructor()
            ->getMock();
        $mockRequest->method('allowMethod')->willReturn(true);
        $this->controller->setRequest($mockRequest);
        $result = $this->component->deleteUser($this->controller, 1);
        $this->assertInstanceOf(Response::class, $result);
    }

    public function testApproveUser(): void
    {
        $result = $this->component->approveUser($this->controller, 1);
        $this->assertInstanceOf(Response::class, $result);
    }

    public function testBulkActivate(): void
    {
        $mockRequest = $this->getMockBuilder(ServerRequest::class)
            ->disableOriginalConstructor()
            ->getMock();
        $mockRequest->method('allowMethod')->willReturn(true);
        $mockRequest->method('getData')->willReturnCallback(function ($key = null) {
            return $key === 'user_ids' ? [2] : null;
        });
        $this->controller->setRequest($mockRequest);
        $result = $this->component->bulkActivate($this->controller);
        $this->assertInstanceOf(Response::class, $result);
    }

    public function testBulkDelete(): void
    {
        $mockRequest = $this->getMockBuilder(ServerRequest::class)
            ->disableOriginalConstructor()
            ->getMock();
        $mockRequest->method('allowMethod')->willReturn(true);
        $mockRequest->method('getData')->willReturnCallback(function ($key = null) {
            return $key === 'user_ids' ? [2] : null;
        });
        $this->controller->setRequest($mockRequest);
        $result = $this->component->bulkDelete($this->controller);
        $this->assertInstanceOf(Response::class, $result);
    }

    public function testLogin(): void
    {
        $result = $this->component->login($this->controller);
        $this->assertNull($result);
    }

    public function testLogout(): void
    {
        $result = $this->component->logout($this->controller);
        $this->assertNotNull($result);
    }

    public function testResetPassword(): void
    {
        $result = $this->component->resetPassword($this->controller);
        $this->assertNull($result);
    }

    public function tearDown(): void
    {
        // Reset table state (except original fixture rows) to avoid cumulative growth
        if ($this->Users) {
            // Delete any users with id > 2 (fixture seeds 1 & 2) to restore baseline
            $this->Users->deleteAll(['id >' => 2]);
        }
        parent::tearDown();
    }

    // Integration tests using fixture

    public function testCreateUserIntegration(): void
    {
        $validData = [
            'username' => 'newuser',
            'password' => 'newpassword',
            'email' => 'newuser@example.com',
            'role' => 'user',
            'status' => 'active',
        ];
        $countBefore = $this->Users->find()->count();
        $this->component->createUser($this->controller, $validData);
        $countAfter = $this->Users->find()->count();
        $this->assertEquals($countBefore + 1, $countAfter); // Always expect +1 after creation
        $user = $this->Users->find()->where(['username' => 'newuser'])->first();
        $this->assertNotNull($user);

        // Test validation fail: missing required fields
        $invalidData = [
            'username' => '', // empty username should fail validation
            'password' => 'pw',
            'email' => '', // empty email should fail validation
            'role' => '', // empty role should fail validation
            'status' => 'active',
        ];
        $countBeforeFail = $this->Users->find()->count();
        $this->component->createUser($this->controller, $invalidData);
        $countAfterFail = $this->Users->find()->count();
        $this->assertEquals($countBeforeFail, $countAfterFail); // No new user should be created
        $userFail = $this->Users->find()->where(['username' => ''])->first();
        $this->assertNull($userFail);
    }

    public function testUpdateUserIntegration(): void
    {
        $user = $this->Users->get(1);
        $data = ['username' => 'admin_updated'];
        $this->component->updateUser($this->controller, $user->id, $data);
        $updated = $this->Users->get(1);
        $this->assertEquals('admin_updated', $updated->username);
    }

    public function testDeleteUserIntegration(): void
    {
        $mockRequest = $this->getMockBuilder(ServerRequest::class)
            ->disableOriginalConstructor()
            ->getMock();
        $mockRequest->method('allowMethod')->willReturn(true);
        $this->controller->setRequest($mockRequest);
        $countBefore = $this->Users->find()->count();
        $this->component->deleteUser($this->controller, 2);
        $countAfter = $this->Users->find()->count();
        $this->assertEquals($countBefore - 1, $countAfter);
        $user = $this->Users->find()->where(['id' => 2])->first();
        $this->assertEmpty($user);
    }

    public function testApproveUserIntegration(): void
    {
        $user = $this->Users->get(2);
        $this->assertEquals('inactive', $user->status);
        $this->component->approveUser($this->controller, 2);
        $updated = $this->Users->get(2);
        $this->assertEquals('active', $updated->status);
    }

    public function testBulkActivateIntegration(): void
    {
        $this->Users->find()->where(['status' => 'inactive'])->all();
        $mockRequest = $this->getMockBuilder(ServerRequest::class)
            ->disableOriginalConstructor()
            ->getMock();
        $mockRequest->method('allowMethod')->willReturn(true);
        $mockRequest->method('getData')->willReturnCallback(function ($key = null) {
            return $key === 'user_ids' ? [2] : null;
        });
        $this->controller->setRequest($mockRequest);
        $this->component->bulkActivate($this->controller);
        $user = $this->Users->get(2);
        $this->assertEquals('active', $user->status);
    }

    public function testBulkDeleteIntegration(): void
    {
        $mockRequest = $this->getMockBuilder(ServerRequest::class)
            ->disableOriginalConstructor()
            ->getMock();
        $mockRequest->method('allowMethod')->willReturn(true);
        $mockRequest->method('getData')->willReturnCallback(function ($key = null) {
            return $key === 'user_ids' ? [2] : null;
        });
        $this->controller->setRequest($mockRequest);
        $this->component->bulkDelete($this->controller);
        $user = $this->Users->find()->where(['id' => 2])->first();
        $this->assertEmpty($user);
    }
}
