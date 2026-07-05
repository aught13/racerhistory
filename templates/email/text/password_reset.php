<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\User $user
 * @var string $resetUrl
 * @var string $displayName
 */
?>
RacerHistory — Reset Your Password
===================================

Hi <?= h($displayName) ?>,

We received a request to reset the password for your RacerHistory account.
Use the link below to choose a new password. This link expires in 1 hour.

<?= $resetUrl ?>

If you did not request a password reset, no action is needed — your password
will not change.

© <?= date('Y') ?> RacerHistory
