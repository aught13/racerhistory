<?php
declare(strict_types=1);

namespace App\Test\TestCase\Mailer;

use App\Mailer\UserMailer;
use App\Model\Entity\User;
use Cake\TestSuite\TestCase;

/**
 * @covers \App\Mailer\UserMailer
 */
class UserMailerTest extends TestCase
{
    /**
     * Tests that resetPassword() configures the message correctly.
     * Calls the action method directly (no transport needed) and inspects
     * the resulting Message object.
     */

    /**
     * Correct recipient and subject are set.
     */
    public function testResetPasswordSetsCorrectRecipientAndSubject(): void
    {
        $user = new User([
            'id' => 42,
            'email' => 'john@example.com',
            'username' => 'johndoe',
            'first_name' => 'John',
            'last_name' => 'Doe',
        ]);
        $resetUrl = 'https://racerhistory.local/users/reset-password/' . str_repeat('a', 64);

        $mailer = new UserMailer();
        $mailer->resetPassword($user, $resetUrl);

        $message = $mailer->getMessage();

        $this->assertArrayHasKey('john@example.com', $message->getTo());
        $this->assertSame('John Doe', $message->getTo()['john@example.com']);
        $this->assertStringContainsString('Reset Your Password', $message->getSubject());
        $this->assertStringContainsString('RacerHistory', $message->getSubject());
        $this->assertSame('both', $message->getEmailFormat());
    }

    /**
     * Falls back to username when first/last name are null.
     */
    public function testResetPasswordFallsBackToUsernameWhenNoName(): void
    {
        $user = new User([
            'id' => 7,
            'email' => 'anon@example.com',
            'username' => 'anon_user',
            'first_name' => null,
            'last_name' => null,
        ]);

        $mailer = new UserMailer();
        $mailer->resetPassword($user, 'https://example.com/reset/abc');

        $message = $mailer->getMessage();
        $this->assertSame('anon_user', $message->getTo()['anon@example.com']);
    }

    /**
     * View vars are populated with user, resetUrl, and displayName.
     */
    public function testResetPasswordSetsViewVars(): void
    {
        $user = new User([
            'id' => 1,
            'email' => 'test@example.com',
            'username' => 'tester',
        ]);
        $resetUrl = 'https://example.com/users/reset-password/token123';

        $mailer = new UserMailer();
        $mailer->resetPassword($user, $resetUrl);

        $vars = $mailer->viewBuilder()->getVars();
        $this->assertArrayHasKey('resetUrl', $vars);
        $this->assertSame($resetUrl, $vars['resetUrl']);
        $this->assertArrayHasKey('user', $vars);
        $this->assertArrayHasKey('displayName', $vars);
    }
}
