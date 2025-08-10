<?php
/**
 * 500 Internal Server Error Template
 *
 * Provides environment-aware error output:
 * - In debug mode show detailed file reference and developer tooling links
 * - In production show a generic, user-friendly message with no sensitive data
 *
 * Accessibility: Uses semantic headings and ARIA role for assistive tech.
 *
 * @var \App\View\AppView $this
 * @var string $message
 * @var string $url
 */
use Cake\Core\Configure;
use Cake\Error\Debugger;

if (Configure::read('debug')) {
    $this->setLayout('dev_error');
    $this->assign('title', $message);
    $this->assign('templateName', 'error500.php');
    $this->start('file');
    echo $this->element('auto_table_warning');
    $this->end();
} else {
    $this->setLayout('error');
    $this->assign('title', 'Internal Server Error');
}
?>

<?php if (Configure::read('debug')): ?>
    <div class="error-debug" role="alert" aria-live="assertive">
        <h2><?= __d('cake', 'An Internal Error Has Occurred.') ?></h2>
        <p><strong><?= __d('cake', 'Error') ?>:</strong> <?= h($message) ?></p>
        <?php if (isset($error) && $error instanceof \Throwable): ?>
            <?php $file = $error->getFile(); $line = $error->getLine(); ?>
            <p><strong>Location:</strong> <?= $this->Html->link(sprintf('%s, line %s', Debugger::trimPath($file), $line), Debugger::editorUrl($file, $line)); ?></p>
        <?php endif; ?>
    </div>
<?php else: ?>
    <div class="error-container" role="alert" aria-live="assertive">
        <h1>500 - Internal Server Error</h1>
        <p><strong>Error:</strong> An unexpected error occurred.</p>
        <p>We encountered a problem while processing your request. Please try again later.</p>
        <p><a href="<?= $this->Url->build('/') ?>">Return to Home</a></p>
    </div>
<?php endif; ?>
