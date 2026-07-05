<?php
declare(strict_types=1);

namespace App\Mailer;

use App\Model\Entity\User;
use Cake\Mailer\Mailer;

/**
 * Mailer for user account-related emails.
 */
class UserMailer extends Mailer
{
    /**
     * Send a password-reset link to the user.
     *
     * Template: templates/email/{html,text}/reset_password.php
     * View vars passed: user, resetUrl, displayName
     *
     * @param \App\Model\Entity\User $user     The user requesting a reset.
     * @param string                 $resetUrl Absolute URL containing the one-time token.
     * @return void
     */
    public function resetPassword(User $user, string $resetUrl): void
    {
        $firstName = (string)($user->first_name ?? '');
        $lastName = (string)($user->last_name ?? '');
        $displayName = trim($firstName . ' ' . $lastName);
        if ($displayName === '') {
            $displayName = (string)$user->username;
        }

        $this
            ->setTo((string)$user->email, $displayName)
            ->setSubject('Reset Your Password - RacerHistory')
            ->setEmailFormat('both')
            ->setViewVars([
                'user' => $user,
                'resetUrl' => $resetUrl,
                'displayName' => $displayName,
            ]);
    }
}
