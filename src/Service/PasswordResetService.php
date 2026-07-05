<?php
declare(strict_types=1);

namespace App\Service;

use App\Mailer\UserMailer;
use App\Model\Entity\User;
use App\Model\Table\UsersTable;
use Authentication\PasswordHasher\DefaultPasswordHasher;
use Cake\I18n\DateTime;
use Cake\Log\Log;
use Cake\ORM\TableRegistry;
use Cake\Routing\Router;
use Exception;

/**
 * Service owning all password-management business logic.
 *
 * - generateAndSendToken(): initiate a "forgot password" flow
 * - validateToken(): check a token is valid and unexpired
 * - consumeToken(): exchange a valid token for a new password
 * - changePassword(): authenticated self-service password change
 */
class PasswordResetService
{
    private UsersTable $usersTable;
    private ?UserMailer $mailer;

    /**
     * @param \App\Model\Table\UsersTable|null $usersTable Inject for testing.
     * @param \App\Mailer\UserMailer|null      $mailer     Inject for testing.
     */
    public function __construct(?UsersTable $usersTable = null, ?UserMailer $mailer = null)
    {
        /** @var \App\Model\Table\UsersTable $table */
        $table = $usersTable ?? TableRegistry::getTableLocator()->get('Users');
        $this->usersTable = $table;
        $this->mailer = $mailer;
    }

    /**
     * Generate a reset token for the given email and send a reset-link email.
     *
     * Always returns true (unified response prevents account enumeration).
     *
     * @param string $email The email address to look up.
     * @return bool Always true.
     */
    public function generateAndSendToken(string $email): bool
    {
        if ($email === '') {
            return true;
        }

        /** @var \App\Model\Entity\User|null $user */
        $user = $this->usersTable->find()->where(['email' => $email])->first();

        if (!$user instanceof User) {
            return true; // Anti-enumeration: don't reveal missing accounts
        }

        $token = bin2hex(random_bytes(32)); // 64-char hex
        $user->token = $token;
        $user->token_expires = new DateTime('+1 hour');

        if (!$this->usersTable->save($user)) {
            return true; // Save failure is treated as silent
        }

        // Build the absolute reset URL using the router; fall back to a relative path
        // if the router is not fully initialised (e.g. in unit-test context).
        try {
            $resetUrl = Router::url([
                'plugin' => false,
                'prefix' => false,
                'controller' => 'Users',
                'action' => 'resetPasswordForm',
                $token,
            ], true);
        } catch (Exception $e) {
            $resetUrl = '/users/reset-password/' . $token;
        }

        try {
            $mailer = $this->mailer ?? new UserMailer('default');
            $mailer->send('resetPassword', [$user, $resetUrl]);
        } catch (Exception $e) {
            Log::error('[PasswordResetService] Email failed for user id=' . $user->id
                . ': ' . $e->getMessage());
        }

        return true;
    }

    /**
     * Validate a reset token.
     *
     * @param string $token The token to validate.
     * @return \App\Model\Entity\User|null User if token is valid and unexpired, null otherwise.
     */
    public function validateToken(string $token): ?User
    {
        if ($token === '') {
            return null;
        }

        /** @var \App\Model\Entity\User|null $user */
        $user = $this->usersTable->find()
            ->where([
                'token' => $token,
                'token_expires >=' => new DateTime(),
            ])
            ->first();

        return $user instanceof User ? $user : null;
    }

    /**
     * Consume a valid reset token: update the user's password and clear the token.
     *
     * @param string $token       The one-time reset token.
     * @param string $newPassword Plain-text new password (will be hashed by UsersTable::beforeSave).
     * @return bool True on success, false if token is invalid/expired.
     */
    public function consumeToken(string $token, string $newPassword): bool
    {
        $user = $this->validateToken($token);
        if ($user === null) {
            return false;
        }

        $user->password = $newPassword;
        $user->token = null;
        $user->token_expires = null;

        return (bool)$this->usersTable->save($user);
    }

    /**
     * Change the password for an authenticated user after verifying the current password.
     *
     * @param int    $userId          The authenticated user's ID.
     * @param string $currentPassword Plain-text current password for verification.
     * @param string $newPassword     Plain-text new password.
     * @return bool True on success, false if current password is wrong.
     */
    public function changePassword(int $userId, string $currentPassword, string $newPassword): bool
    {
        /** @var \App\Model\Entity\User|null $user */
        $user = $this->usersTable->get($userId);

        if (!$user instanceof User) {
            return false;
        }

        $hasher = new DefaultPasswordHasher();
        if (!$hasher->check($currentPassword, (string)$user->password)) {
            return false;
        }

        $user->password = $newPassword;

        return (bool)$this->usersTable->save($user);
    }
}
