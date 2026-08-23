<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller;

use App\Test\TestCase\Support\AuthTestTrait;
use Cake\I18n\DateTime;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

/**
 * @link \App\Controller\UsersController
 */
class UsersControllerTest extends TestCase
{
    use IntegrationTestTrait;
    use AuthTestTrait;

    /**
     * Use fixtures for deterministic baseline instead of manual seeding.
     *
     * @var array<string>
     */
    protected array $fixtures = ['app.Users', 'app.SiteOptions'];

    /** Deterministic 64-char hex token used in reset-form tests. */
    private const VALID_TOKEN = 'aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa';

    /**
     * Sets up the test case.
     */
    public function setUp(): void
    {
        parent::setUp();
    }

    /**
     * Tests register disabled.
     */
    public function testRegisterDisabled(): void
    {
        // CakeDC/Users registration is disabled for the public site.
        $this->get('/users/register');
        $this->assertResponseCode(404);
    }

    /**
     * Runs the login as admin routine.
     */
    private function loginAsAdmin(): void
    {
        $this->mockIdentity();
    }

    /**
     * Seed a reset token onto the given user (defaults to user id=1).
     *
     * @param string $token  Hex token value to seed.
     * @param string $expiry Relative expiry, e.g. '+1 hour' or '-5 minutes'.
     * @param int    $userId User id to seed onto.
     * @return void
     */
    private function seedResetToken(string $token, string $expiry = '+1 hour', int $userId = 1): void
    {
        $users = $this->getTableLocator()->get('Users');
        $user = $users->get($userId);
        $user->token = $token;
        $user->token_expires = new DateTime($expiry);
        $users->saveOrFail($user);
    }

    /**
     * Tests login get.
     */
    public function testLoginGet(): void
    {
        $this->get('/users/login');
        $this->assertResponseOk();
        $this->assertResponseContains('Login');
    }

    /**
     * Tests login post invalid.
     */
    public function testLoginPostInvalid(): void
    {
        $this->enableCsrfToken();
        $this->enableSecurityToken();
        $this->post('/users/login', [
            'username' => 'wrong',
            'password' => 'wrong',
        ]);
        $this->assertResponseOk();
        $this->assertResponseContains('Username or password is incorrect');
    }

    /**
     * Tests login post valid redirects to redirect param.
     */
    public function testLoginPostValidRedirectsToRedirectParam(): void
    {
        $this->enableCsrfToken();
        $this->enableSecurityToken();
        $this->post('/users/login?redirect=/admin', [
            'username' => 'admin',
            'password' => 'administrator',
        ]);
        $this->assertRedirect('/admin');
    }

    /**
     * Tests login redirects to admin when redirect param present.
     */
    public function testLoginRedirectsToAdminWhenRedirectParamPresent(): void
    {
        $this->get('/users/login?redirect=/admin');
        $this->assertResponseOk();
        $this->assertResponseContains('name="redirect"');
        $this->assertResponseContains('value="/admin"');
    }

    /**
     * Tests register get.
     */
    public function testRegisterGet(): void
    {
        $this->get('/users/register');
        $this->assertResponseCode(404);
    }

    /**
     * Tests logout.
     */
    public function testLogout(): void
    {
        $this->loginAsAdmin();
        $this->get('/users/logout');
        $this->assertStringContainsString('/login', $this->_response->getHeaderLine('Location'));
    }

    /**
     * Tests unknown action redirects home.
     */
    public function testUnknownActionRedirectsHome(): void
    {
        $this->get('/users/doesntExist');
        $location = $this->_response->getHeaderLine('Location');
        $this->assertStringContainsString('/users/login', $location);
    }

    /**
     * Tests reset password get.
     */
    public function testResetPasswordGet(): void
    {
        $this->get('/users/resetPassword');
        $this->assertResponseOk();
        $this->assertResponseContains('Enter your email address');
    }

    /**
     * Tests reset password post valid.
     */
    public function testResetPasswordPostValid(): void
    {
        $this->enableCsrfToken();
        $this->enableSecurityToken();
        $this->enableRetainFlashMessages();
        $data = [
            'email' => 'admin@example.com',
        ];
        $this->post('/users/resetPassword', $data);
        $this->assertResponseOk();
        $this->assertFlashMessage('If your email exists, a reset link will be sent.');
    }

    /**
     * Tests reset password post invalid.
     */
    public function testResetPasswordPostInvalid(): void
    {
        $this->enableCsrfToken();
        $this->enableSecurityToken();
        $this->enableRetainFlashMessages();
        $data = [
            'email' => '', // invalid
        ];
        $this->post('/users/resetPassword', $data);
        $this->assertResponseOk();
        $this->assertFlashMessage('If your email exists, a reset link will be sent.');
    }

    // -----------------------------------------------------------------------
    // resetPasswordForm (token consumption - step 2)
    // -----------------------------------------------------------------------

    /**
     * GET with a valid token renders the set-new-password form.
     */
    public function testResetPasswordFormGetValidToken(): void
    {
        $this->seedResetToken(self::VALID_TOKEN, '+1 hour');
        $this->get('/users/reset-password/' . self::VALID_TOKEN);
        $this->assertResponseOk();
        $this->assertResponseContains('Set New Password');
    }

    /**
     * GET with an invalid / missing token redirects back to the email-request form.
     */
    public function testResetPasswordFormGetInvalidToken(): void
    {
        $this->enableRetainFlashMessages();
        $this->get('/users/reset-password/' . str_repeat('b', 64));
        $this->assertRedirectContains('reset-password');
        $this->assertFlashMessageContains('invalid or has expired');
    }

    /**
     * POST with matching passwords resets the password and redirects to login.
     */
    public function testResetPasswordFormPostSuccess(): void
    {
        $this->seedResetToken(self::VALID_TOKEN, '+1 hour');
        $this->enableCsrfToken();
        $this->enableSecurityToken();
        $this->enableRetainFlashMessages();
        $this->post('/users/reset-password/' . self::VALID_TOKEN, [
            'password' => 'newpassword123',
            'confirm_password' => 'newpassword123',
        ]);
        $this->assertRedirectContains('/login');
        $this->assertFlashMessageContains('reset');
    }

    /**
     * POST with an expired token redirects back and shows an error.
     */
    public function testResetPasswordFormPostExpiredToken(): void
    {
        $this->seedResetToken(str_repeat('c', 64), '-5 minutes');
        $this->enableCsrfToken();
        $this->enableSecurityToken();
        $this->enableRetainFlashMessages();
        $this->post('/users/reset-password/' . str_repeat('c', 64), [
            'password' => 'newpassword123',
            'confirm_password' => 'newpassword123',
        ]);
        $this->assertRedirectContains('reset-password');
        $this->assertFlashMessageContains('invalid or has expired');
    }

    /**
     * POST with mismatched passwords shows a validation error.
     */
    public function testResetPasswordFormPostMismatchedPasswords(): void
    {
        $this->seedResetToken(self::VALID_TOKEN, '+1 hour');
        $this->enableCsrfToken();
        $this->enableSecurityToken();
        $this->enableRetainFlashMessages();
        $this->post('/users/reset-password/' . self::VALID_TOKEN, [
            'password' => 'newpassword123',
            'confirm_password' => 'different456',
        ]);
        $this->assertResponseOk();
        $this->assertFlashMessageContains('do not match');
    }

    // -----------------------------------------------------------------------
    // changePassword (authenticated self-service)
    // -----------------------------------------------------------------------

    /**
     * Unauthenticated GET redirects to login.
     */
    public function testChangePasswordGetUnauthenticated(): void
    {
        $this->get('/users/change-password');
        $this->assertRedirectContains('/login');
    }

    /**
     * Authenticated GET renders the change-password form.
     */
    public function testChangePasswordGetAuthenticated(): void
    {
        $this->mockIdentity();
        $this->get('/users/change-password');
        $this->assertResponseOk();
        $this->assertResponseContains('Change Password');
    }

    /**
     * Correct current password updates the password and redirects home.
     */
    public function testChangePasswordPostSuccess(): void
    {
        $this->mockIdentity();
        $this->enableCsrfToken();
        $this->enableSecurityToken();
        $this->enableRetainFlashMessages();
        $this->post('/users/change-password', [
            'current_password' => 'administrator',
            'password' => 'newpassword123',
            'confirm_password' => 'newpassword123',
        ]);
        $this->assertRedirect('/');
        $this->assertFlashMessageContains('updated');
    }

    /**
     * Wrong current password shows an error and keeps the form.
     */
    public function testChangePasswordPostWrongCurrent(): void
    {
        $this->mockIdentity();
        $this->enableCsrfToken();
        $this->enableSecurityToken();
        $this->enableRetainFlashMessages();
        $this->post('/users/change-password', [
            'current_password' => 'badpassword',
            'password' => 'newpassword123',
            'confirm_password' => 'newpassword123',
        ]);
        $this->assertResponseOk();
        $this->assertFlashMessageContains('incorrect');
    }

    /**
     * New passwords that don't match show an error.
     */
    public function testChangePasswordPostMismatchedPasswords(): void
    {
        $this->mockIdentity();
        $this->enableCsrfToken();
        $this->enableSecurityToken();
        $this->enableRetainFlashMessages();
        $this->post('/users/change-password', [
            'current_password' => 'administrator',
            'password' => 'newpassword123',
            'confirm_password' => 'different456',
        ]);
        $this->assertResponseOk();
        $this->assertFlashMessageContains('do not match');
    }

    // Add more tests for other actions as needed
}
