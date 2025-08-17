<?php

/**
 * Default Flash Message Element
 *
 * Displays flash messages with customizable styling and click-to-dismiss functionality.
 * Provides secure message output with optional HTML escaping.
 *
 * Features:
 * - Customizable CSS classes via $params['class']
 * - HTML escaping for security (configurable)
 * - Click-to-dismiss functionality
 * - Bootstrap-compatible styling ready
 *
 * Usage:
 * - $this->Flash->set('Message text')
 * - $this->Flash->set('Message', ['class' => 'alert alert-info'])
 * - $this->Flash->set('HTML message', ['escape' => false])
 *
 * JavaScript:
 * - onclick handler adds 'hidden' class for dismissal
 * - Compatible with CSS transitions for smooth hiding
 *
 * @var \App\View\AppView $this
 * @var array $params Flash message parameters
 * @var string $message Flash message content
 */
$class = 'message';
if (!empty($params['class'])) {
    $class .= ' ' . $params['class'];
}
if (!isset($params['escape']) || $params['escape'] !== false) {
    $message = h($message);
}
?>
<div class="<?= h($class) ?>" onclick="this.classList.add('hidden');"><?= $message ?></div>
