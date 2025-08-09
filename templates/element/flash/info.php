<?php
/**
 * Info Flash Message Element
 *
 * Displays informational flash messages with neutral/blue styling indicators.
 * Used for general information, tips, and neutral feedback.
 *
 * Features:
 * - Default 'message' class for neutral informational styling
 * - HTML escaping for security (configurable)
 * - Click-to-dismiss functionality
 * - Bootstrap alert-info compatible
 *
 * Usage Examples:
 * - $this->Flash->info('Check your email for verification')
 * - $this->Flash->set('FYI: Feature coming soon', ['element' => 'info'])
 *
 * CSS Classes:
 * - 'message': Base styling for informational messages
 * - 'hidden': Applied on click for dismissal
 *
 * @var \App\View\AppView $this
 * @var array $params Flash message parameters
 * @var string $message Informational message content
 */
if (!isset($params['escape']) || $params['escape'] !== false) {
    $message = h($message);
}
?>
<div class="message" onclick="this.classList.add('hidden');"><?= $message ?></div>
