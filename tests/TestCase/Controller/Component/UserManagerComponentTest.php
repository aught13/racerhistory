<?php
namespace App\Test\TestCase\Controller\Component;
use Authentication\Authenticator\ResultInterface;
use App\Controller\Component\UserManagerComponent;
use Cake\Controller\Controller;
use Cake\Controller\Component\FlashComponent;
use Cake\Controller\ComponentRegistry;
use Cake\Http\ServerRequest;
use Cake\TestSuite\TestCase;
use Authentication\IdentityInterface;

class StubController extends Controller
{
    public $Users;
    public $Flash;
    public $Authentication;
    protected $_request;

    public function __construct($request = null)
    {
        parent::__construct($request);
        if ($request) {
            $this->request = $request;
        }
    }

    public function setRequest($request)
    {
        $this->_request = $request;
        $this->request = $request;
        return $this;
    }

    public function getRequest(): \Cake\Http\ServerRequest
    {
        return $this->_request ?? parent::getRequest();
    }

    public function redirect($url, int $status = 302, bool $exit = true): \Cake\Http\Response
    {
        // Prevent actual routing during tests
        return new \Cake\Http\Response();
    }
}

class UserManagerComponentTest extends TestCase
{
    public array $fixtures = ['app.Users'];

    public $controller;
    public $component;
    public $Users;

    public function setUp(): void
    {
        parent::setUp();
        $request = new ServerRequest();
        $this->controller = new StubController($request);

        // Users Table
        $this->Users = \Cake\Datasource\FactoryLocator::get('Table')->get('Users');
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
            $this->createMock(ResultInterface::class)
        );
        $this->controller->Authentication->method('getIdentity')->willReturn(
            $this->createMock(IdentityInterface::class)
        );
        $this->controller->Authentication->method('logout')->willReturn(null);

        $this->component = new UserManagerComponent($componentRegistry);
    }

    // Isolated unit tests
    public function testCreateUser(): void
    {
        $result = $this->component->createUser($this->controller, ['username' => 'test', 'password' => 'test']);
        $this->assertInstanceOf(\Cake\Http\Response::class, $result);
    }
    public function testUpdateUser(): void
    {
        $result = $this->component->updateUser($this->controller, 1, ['username' => 'updated', 'password' => 'updated']);
        $this->assertInstanceOf(\Cake\Http\Response::class, $result);
    }
    public function testDeleteUser(): void
    {
        $mockRequest = $this->getMockBuilder(ServerRequest::class)
            ->disableOriginalConstructor()
            ->getMock();
        $mockRequest->method('allowMethod')->willReturn(true);
        $this->controller->setRequest($mockRequest);
        $result = $this->component->deleteUser($this->controller, 1);
        $this->assertInstanceOf(\Cake\Http\Response::class, $result);
    }
    public function testApproveUser(): void
    {
        $result = $this->component->approveUser($this->controller, 1);
        $this->assertInstanceOf(\Cake\Http\Response::class, $result);
    }
    public function testBulkActivate(): void
    {
        $mockRequest = $this->getMockBuilder(ServerRequest::class)
            ->disableOriginalConstructor()
            ->getMock();
        $mockRequest->method('allowMethod')->willReturn(true);
        $this->controller->setRequest($mockRequest);
        $result = $this->component->bulkActivate($this->controller);
        $this->assertInstanceOf(\Cake\Http\Response::class, $result);
    }
    public function testBulkDelete(): void
    {
        $mockRequest = $this->getMockBuilder(ServerRequest::class)
            ->disableOriginalConstructor()
            ->getMock();
        $mockRequest->method('allowMethod')->willReturn(true);
        $this->controller->setRequest($mockRequest);
        $result = $this->component->bulkDelete($this->controller);
        $this->assertInstanceOf(\Cake\Http\Response::class, $result);
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

    // Integration tests using fixture
    public function testCreateUserIntegration(): void
    {
        $validData = [
            'username' => 'newuser',
            'password' => 'newpassword',
            'email' => 'newuser@example.com',
            'role' => 'user',
            'status' => 'active'
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
            'status' => 'active'
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
        $inactiveUsers = $this->Users->find()->where(['status' => 'inactive'])->all();
        $ids = array_map(function($u) { return $u->id; }, iterator_to_array($inactiveUsers));
        $mockRequest = $this->getMockBuilder(ServerRequest::class)
            ->disableOriginalConstructor()
            ->getMock();
        $mockRequest->method('allowMethod')->willReturn(true);
        $mockRequest->method('getData')->willReturn([2]);
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
        $mockRequest->method('getData')->willReturn([2]);
        $this->controller->setRequest($mockRequest);
        $this->component->bulkDelete($this->controller);
        $user = $this->Users->find()->where(['id' => 2])->first();
        $this->assertEmpty($user);
    }
}
