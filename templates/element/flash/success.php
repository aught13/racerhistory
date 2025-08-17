<?php

/**
 * Success Flash Message Element
 *
 * Displays success flash messages with green/positive styling indicators.
 * Used for confirmations, successful operations, and positive feedback.
 *
 * Features:
 * - Pre-styled with 'success' class for positive messaging
 * - HTML escaping for security (configurable)
 * - Click-to-dismiss functionality
 * - Bootstrap alert-success compatible
 *
 * Usage Examples:
 * - $this->Flash->success('User created successfully!')
 * - $this->Flash->set('Operation completed', ['element' => 'success'])
 *
 * CSS Classes:
 * - 'message success': Base styling for success messages
 * - 'hidden': Applied on click for dismissal
 *
 * @var \App\View\AppView $this
 * @var array $params Flash message parameters
 * @var string $message Success message content
 */
if (!isset($params['escape']) || $params['escape'] !== false) {
    $message = h($message);
}
?>
<div class="message success" onclick="this.classList.add('hidden')"><?= $message ?></div>
