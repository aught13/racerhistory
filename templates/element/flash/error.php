<?php
/**
 * Error Flash Message Element
 *
 * Displays error flash messages with red/negative styling indicators.
 * Used for validation errors, operation failures, and error feedback.
 *
 * Features:
 * - Pre-styled with 'error' class for negative messaging
 * - HTML escaping for security (configurable)
 * - Click-to-dismiss functionality
 * - Bootstrap alert-danger compatible
 *
 * Usage Examples:
 * - $this->Flash->error('Username already exists!')
 * - $this->Flash->set('Operation failed', ['element' => 'error'])
 *
 * CSS Classes:
 * - 'message error': Base styling for error messages
 * - 'hidden': Applied on click for dismissal
 *
 * @var \App\View\AppView $this
 * @var array $params Flash message parameters
 * @var string $message Error message content
 */
if (!isset($params['escape']) || $params['escape'] !== false) {
    $message = h($message);
}
?>
<div class="message error" onclick="this.classList.add('hidden');"><?= $message ?></div>
