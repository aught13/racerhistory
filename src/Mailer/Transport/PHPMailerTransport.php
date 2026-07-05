<?php
declare(strict_types=1);

namespace App\Mailer\Transport;

use Cake\Mailer\AbstractTransport;
use Cake\Mailer\Message;
use PHPMailer\PHPMailer\PHPMailer;

/**
 * CakePHP transport wrapper around PHPMailer for SMTP delivery.
 *
 * Configure via the 'phpmailer' key in EmailTransport config:
 *   'host', 'port', 'username', 'password', 'encryption' ('tls'|'ssl'|'none'), 'timeout'
 */
class PHPMailerTransport extends AbstractTransport
{
    /**
     * Send email using PHPMailer.
     *
     * @param \Cake\Mailer\Message $message The message to send.
     * @return array<string, string> Sent message info.
     * @throws \PHPMailer\PHPMailer\Exception On send failure.
     */
    public function send(Message $message): array
    {
        $config = $this->getConfig();

        $mail = new PHPMailer(true); // Exceptions enabled

        $mail->isSMTP();
        $mail->Host = (string)($config['host'] ?? 'localhost');
        $mail->Port = (int)($config['port'] ?? 587);
        $mail->Timeout = (int)($config['timeout'] ?? 30);

        $username = (string)($config['username'] ?? '');
        $password = (string)($config['password'] ?? '');

        if ($username !== '') {
            $mail->SMTPAuth = true;
            $mail->Username = $username;
            $mail->Password = $password;
        }

        $encryption = (string)($config['encryption'] ?? 'tls');
        if ($encryption === 'ssl') {
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        } elseif ($encryption === 'tls') {
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        } else {
            $mail->SMTPAutoTLS = false;
        }

        // From
        foreach ($message->getFrom() as $email => $name) {
            $mail->setFrom($email, (string)$name);
            break;
        }

        // Reply-To
        foreach ($message->getReplyTo() as $email => $name) {
            $mail->addReplyTo($email, (string)$name);
        }

        // To
        foreach ($message->getTo() as $email => $name) {
            $mail->addAddress($email, (string)$name);
        }

        // CC
        foreach ($message->getCc() as $email => $name) {
            $mail->addCC($email, (string)$name);
        }

        // BCC
        foreach ($message->getBcc() as $email => $name) {
            $mail->addBCC($email, (string)$name);
        }

        $mail->Subject = $message->getSubject();

        $htmlBody = $message->getBodyHtml();
        $textBody = $message->getBodyText();

        if ($htmlBody !== '') {
            $mail->isHTML(true);
            $mail->Body = $htmlBody;
            $mail->AltBody = $textBody !== '' ? $textBody : strip_tags($htmlBody);
        } else {
            $mail->isHTML(false);
            $mail->Body = $textBody;
        }

        $mail->send();

        return ['headers' => $mail->getLastMessageID(), 'message' => $mail->Body];
    }
}
