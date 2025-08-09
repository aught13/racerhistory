<?php
/**
 * Warning Flash Message Element
 *
 * Displays warning flash messages with yellow/caution styling indicators.
 * Used for caution messages, important notices, and advisory feedback.
 *
 * Features:
 * - Pre-styled with 'warning' class for caution messaging
 * - HTML escaping for security (configurable)
 * - Click-to-dismiss functionality
 * - Bootstrap alert-warning compatible
 *
 * Usage Examples:
 * - $this->Flash->warning('Password will expire soon!')
 * - $this->Flash->set('Proceed with caution', ['element' => 'warning'])
 *
 * CSS Classes:
 * - 'message warning': Base styling for warning messages
 * - 'hidden': Applied on click for dismissal
 *
 * @var \App\View\AppView $this
 * @var array $params Flash message parameters
 * @var string $message Warning message content
 */
if (!isset($params['escape']) || $params['escape'] !== false) {
    $message = h($message);
}
?>
<div class="message warning" onclick="this.classList.add('hidden');"><?= $message ?></div>
