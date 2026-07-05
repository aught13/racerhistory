<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\User $user
 * @var string $resetUrl
 * @var string $displayName
 */
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Reset Your Password</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f5f5f5; margin: 0; padding: 0; }
        .container { max-width: 600px; margin: 40px auto; background: #fff; border-radius: 8px; padding: 40px; box-shadow: 0 2px 4px rgba(0,0,0,.1); }
        h1 { color: #002144; font-size: 22px; margin-top: 0; }
        p { color: #444; line-height: 1.6; }
        .btn { display: inline-block; padding: 12px 28px; background: #002144; color: #fff !important; text-decoration: none; border-radius: 5px; font-size: 16px; margin: 20px 0; }
        .link-fallback { word-break: break-all; color: #555; font-size: 13px; }
        .footer { margin-top: 30px; color: #999; font-size: 12px; border-top: 1px solid #eee; padding-top: 16px; }
    </style>
</head>
<body>
    <div class="container">
        <h1>RacerHistory</h1>
        <p>Hi <?= h($displayName) ?>,</p>
        <p>We received a request to reset the password for your RacerHistory account. Click the button below to choose a new password. This link expires in <strong>1 hour</strong>.</p>
        <p style="text-align:center;">
            <a href="<?= h($resetUrl) ?>" class="btn">Reset Your Password</a>
        </p>
        <p class="link-fallback">
            If the button above doesn't work, copy and paste this URL into your browser:<br>
            <?= h($resetUrl) ?>
        </p>
        <p>If you did not request a password reset, no action is needed &mdash; your password will not change.</p>
        <div class="footer">
            &copy; <?= date('Y') ?> RacerHistory &bull; This is an automated message, please do not reply.
        </div>
    </div>
</body>
</html>
