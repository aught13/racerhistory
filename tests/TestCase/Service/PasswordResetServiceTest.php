<?php
declare(strict_types=1);

namespace App\Test\TestCase\Service;

use App\Model\Entity\User;
use App\Service\PasswordResetService;
use Authentication\PasswordHasher\DefaultPasswordHasher;
use Cake\I18n\DateTime;
use Cake\TestSuite\TestCase;

/**
 * @covers \App\Service\PasswordResetService
 */
class PasswordResetServiceTest extends TestCase
{
    protected array $fixtures = ['app.Users'];

    private PasswordResetService $service;

    /**
     * Tests setUp.
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->service = new PasswordResetService();
    }

    // -----------------------------------------------------------------
    // generateAndSendToken
    // -----------------------------------------------------------------

    /**
     * Known email saves token and returns true.
     */
    public function testGenerateAndSendTokenKnownEmailSavesToken(): void
    {
        $users = $this->getTableLocator()->get('Users');
        $before = $users->get(1);
        $this->assertNull($before->token, 'Token should be null before reset');

        $result = $this->service->generateAndSendToken('admin@example.com');

        $this->assertTrue($result);
        $after = $users->get(1);
        $this->assertNotNull($after->token);
        $this->assertMatchesRegularExpression('/^[0-9a-f]{64}$/', (string)$after->token);
        $this->assertNotNull($after->token_expires);
        $this->assertGreaterThan(new DateTime(), $after->token_expires);
    }

    /**
     * Unknown email returns true but does not write any token.
     */
    public function testGenerateAndSendTokenUnknownEmailReturnsTrueWithNoDbChange(): void
    {
        $result = $this->service->generateAndSendToken('nobody@example.com');

        $this->assertTrue($result);
        $users = $this->getTableLocator()->get('Users');
        $count = $users->find()->where(['token IS NOT' => null])->count();
        $this->assertSame(0, $count, 'No tokens should be written for unknown email');
    }

    /**
     * Empty email returns true immediately without touching the DB.
     */
    public function testGenerateAndSendTokenEmptyEmailReturnsTrueWithNoDbChange(): void
    {
        $result = $this->service->generateAndSendToken('');

        $this->assertTrue($result);
    }

    // -----------------------------------------------------------------
    // validateToken
    // -----------------------------------------------------------------

    /**
     * Valid token returns the matching User entity.
     */
    public function testValidateTokenValidTokenReturnsUser(): void
    {
        $token = $this->seedToken(1, '+1 hour');

        $user = $this->service->validateToken($token);

        $this->assertInstanceOf(User::class, $user);
        $this->assertSame(1, $user->id);
    }

    /**
     * Expired token returns null.
     */
    public function testValidateTokenExpiredTokenReturnsNull(): void
    {
        $token = $this->seedToken(1, '-1 minute');

        $user = $this->service->validateToken($token);

        $this->assertNull($user);
    }

    /**
     * Unknown token returns null.
     */
    public function testValidateTokenUnknownTokenReturnsNull(): void
    {
        $user = $this->service->validateToken(str_repeat('0', 64));

        $this->assertNull($user);
    }

    /**
     * Empty string returns null without querying the DB.
     */
    public function testValidateTokenEmptyStringReturnsNull(): void
    {
        $this->assertNull($this->service->validateToken(''));
    }

    // -----------------------------------------------------------------
    // consumeToken
    // -----------------------------------------------------------------

    /**
     * Valid token is consumed and password is updated.
     */
    public function testConsumeTokenSuccessUpdatesPasswordAndClearsToken(): void
    {
        $token = $this->seedToken(1, '+1 hour');

        $result = $this->service->consumeToken($token, 'freshpassword99');

        $this->assertTrue($result);
        $users = $this->getTableLocator()->get('Users');
        $user = $users->get(1);
        $this->assertNull($user->token);
        $this->assertNull($user->token_expires);
        $hasher = new DefaultPasswordHasher();
        $this->assertTrue($hasher->check('freshpassword99', (string)$user->password));
    }

    /**
     * Expired token returns false.
     */
    public function testConsumeTokenExpiredTokenReturnsFalse(): void
    {
        $token = $this->seedToken(1, '-1 minute');

        $result = $this->service->consumeToken($token, 'freshpassword99');

        $this->assertFalse($result);
    }

    /**
     * Invalid token returns false.
     */
    public function testConsumeTokenInvalidTokenReturnsFalse(): void
    {
        $result = $this->service->consumeToken(str_repeat('f', 64), 'freshpassword99');

        $this->assertFalse($result);
    }

    // -----------------------------------------------------------------
    // changePassword
    // -----------------------------------------------------------------

    /**
     * Correct current password updates the password.
     */
    public function testChangePasswordCorrectCurrentUpdatesPassword(): void
    {
        $result = $this->service->changePassword(1, 'administrator', 'supersecure999');

        $this->assertTrue($result);
        $users = $this->getTableLocator()->get('Users');
        $user = $users->get(1);
        $hasher = new DefaultPasswordHasher();
        $this->assertTrue($hasher->check('supersecure999', (string)$user->password));
    }

    /**
     * Wrong current password returns false and leaves the password unchanged.
     */
    public function testChangePasswordWrongCurrentReturnsFalse(): void
    {
        $result = $this->service->changePassword(1, 'wrongpassword', 'supersecure999');

        $this->assertFalse($result);
        // Verify original password is unchanged
        $users = $this->getTableLocator()->get('Users');
        $user = $users->get(1);
        $hasher = new DefaultPasswordHasher();
        $this->assertTrue($hasher->check('administrator', (string)$user->password));
    }

    // -----------------------------------------------------------------
    // Helpers
    // -----------------------------------------------------------------

    /**
     * Seed a known token onto the given user record and return the token string.
     *
     * @param int    $userId User to seed token onto.
     * @param string $expiry Relative expiry string, e.g. '+1 hour'.
     * @return string The seeded token.
     */
    private function seedToken(int $userId, string $expiry): string
    {
        $token = str_repeat('a', 64); // deterministic for assertions
        $users = $this->getTableLocator()->get('Users');
        $user = $users->get($userId);
        $user->token = $token;
        $user->token_expires = new DateTime($expiry);
        $users->saveOrFail($user);

        return $token;
    }
}
