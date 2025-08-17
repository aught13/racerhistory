<?php

/**
 * 400 Bad Request Error Template
 *
 * Handles display of HTTP 400 Bad Request errors with environment-appropriate content.
 * Shows detailed debug information in development and user-friendly messages in production.
 *
 * Features:
 * - Environment-aware error display (debug vs production)
 * - User-friendly error messaging for production
 * - Debug information display for development
 * - Navigation back to home page
 * - Consistent error page styling
 *
 * Debug Mode (development):
 * - Shows detailed error message
 * - Displays template name for debugging
 * - Includes auto table warning element
 * - Full exception details available
 *
 * Production Mode:
 * - Generic "Bad Request" message
 * - User-friendly explanation
 * - Home page navigation link
 * - No sensitive debug information
 *
 * Security:
 * - No sensitive information exposed in production
 * - Debug information only in development
 * - Safe error message display
 *
 * @var \App\View\AppView $this
 * @var string $message Error message content
 * @var string $url Requested URL that caused the error
 */
use Cake\Core\Configure;

if (Configure::read('debug')) :
    $this->assign('title', $message);
    $this->assign('templateName', 'error400.php');
    $this->start('file');
    echo $this->element('auto_table_warning');
    $this->end();
else :
    $this->assign('title', 'Bad Request');
endif;
?>

<?php if (!Configure::read('debug')) : ?>
<div class="error-container">
    <h1>400 - Bad Request</h1>
    <p><strong>Error:</strong> Bad Request</p>
    <p>The request could not be understood by the server.</p>
    <p><a href="<?= $this->Url->build('/') ?>">Return to Home</a></p>
</div>
<?php endif; ?>
