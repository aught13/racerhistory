<?php
/**
 * Default Layout Template
 *
 * Main application layout featuring Bootstrap 5.3.2 framework with responsive design.
 * Provides consistent header navigation, authentication status, flash messages, and footer.
 *
 * Features:
 * - Bootstrap 5.3.2 CSS/JS loaded from CDN with integrity verification
 * - jQuery 3.7.1 for enhanced JavaScript functionality
 * - Responsive navigation with authentication status display
 * - Bootstrap Icons 1.11.3 for UI elements
 * - Custom cake.css for application-specific styling
 * - Flash message display area
 * - Responsive footer with copyright information
 *
 * CDN Resources:
 * - Bootstrap CSS: sha384-T3c6CoIi6uLrA9TneNEoa7RxnatzjcDSCmG1MXxSR1GAsXEV/Dwwykc2MPK8M2HN
 * - Bootstrap JS: sha384-C6RzsynM9kWDrMNeT87bh95OGNyZPhcTNXj1NW7RuBCsyN/o0jlpcV8Qyq46cDfL
 * - jQuery: sha256-3gJwYp4gU1yPpT2+6Ff9QbYl1Q6Vb6Yh2y9gA+3eGm8=
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP(tm) Project
 * @since         0.10.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 * @var \App\View\AppView $this
 */

?>
<!DOCTYPE html>
<?php
$themeCookie = (string)($this->request->getCookie('theme') ?? '');
$htmlThemeAttribute = in_array($themeCookie, ['light', 'dark'], true)
    ? ' data-theme="' . h($themeCookie) . '"'
    : '';
$content = $this->fetch('content');
$flash = $this->Flash->render();
?>
<html<?= $htmlThemeAttribute ?>>

<head>
    <?= $this->Html->charset() ?>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="turbo-refresh-method" content="morph">
    <meta name="turbo-refresh-scroll" content="reset">
    <meta name="theme-color" content="#002144">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="RacerHistory">
    <link rel="manifest" href="<?= $this->Url->build('/manifest.webmanifest') ?>">
    <link rel="apple-touch-icon" sizes="180x180" href="<?= $this->Url->build('/img/apple-touch-icon-180.png') ?>">
    <title>
        <?= $this->fetch('title') ?> | RacerHistory
    </title>
    <?= $this->Html->meta('icon') ?>

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-T3c6CoIi6uLrA9TneNEoa7RxnatzjcDSCmG1MXxSR1GAsXEV/Dwwykc2MPK8M2HN" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <!-- FontAwesome 6 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" crossorigin="anonymous">
    <!-- DataTables 2.3.8 CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/2.3.8/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/3.2.6/css/buttons.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/responsive/3.0.8/css/responsive.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/scroller/2.4.3/css/scroller.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/searchbuilder/1.8.4/css/searchBuilder.bootstrap5.min.css">
    <link href="https://cdn.jsdelivr.net/npm/datatables.net-datetime@1.6.3/dist/dataTables.dateTime.min.css" rel="stylesheet">

    <!-- Optional: Custom styles -->
    <?= $this->Html->css(['frontend']) ?>

    <?= $this->fetch('meta') ?>
    <?= $this->fetch('css') ?>

    <?php if (class_exists('CakeVite\\View\\Helper\\ViteHelper') || class_exists('Josbeir\\Vite\\View\\Helper\\ViteHelper')) : ?>
        <?php if (method_exists($this->Vite, 'element')) : ?>
            <?= $this->Vite->element('js/main.js') ?>
        <?php else : ?>
            <?= $this->Vite->css(['files' => ['js/main.js']]) ?>
            <?= $this->Vite->script(['files' => ['js/main.js']]); ?>
        <?php endif; ?>
    <?php endif; ?>

    <?= $this->fetch('script') ?>

    <?php
    // Load global ad script in the head so provider scripts (e.g. adsbygoogle)
    // are available before body ad slots render.
    echo $this->element('Ads/global_script');
    ?>
</head>

<?php
$identity = $this->getRequest()->getAttribute('identity');
$role = $identity && method_exists($identity, 'get') ? (string)$identity->get('role') : '';
$isAdmin = $identity && (
    in_array($role, ['admin', 'superadmin'], true) ||
    (bool)($identity->get('is_superuser') ?? false)
);
$controller = (string)$this->request->getParam('controller');
$action = (string)$this->request->getParam('action');
$isMainPage = (
    ($action === 'index' && in_array($controller, ['Blog', 'Seasons', 'People', 'Stats', 'Games'], true))
    || ($controller === 'Pages' && $action === 'display' && (($this->request->getParam('pass')[0] ?? '') === 'home'))
);
$bodyClass = trim(($identity ? 'rh-has-user ' : '') . ($isMainPage ? 'rh-has-head' : ''));
?>
<body class="<?= h($bodyClass) ?>" data-is-main="<?= $isMainPage ? 'true' : 'false' ?>">
    <?= $this->element('Layout/public_shell', [
        'identity' => $identity,
        'isAdmin' => $isAdmin,
        'isMainPage' => $isMainPage,
        'bodyClass' => $bodyClass,
        'content' => $content,
        'flash' => $flash,
    ]) ?>
    
</body>

</html>
