<?php
/**
 * Minimal standalone layout for the /install deploy audit page.
 * No nav, no Turbo, no auth UI — just Bootstrap for styling.
 *
 * @var \App\View\AppView $this
 */
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?= $this->Html->charset() ?>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>RacerHistory — Deployment Audit</title>
    <?= $this->Html->meta('icon') ?>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-T3c6CoIi6uLrA9TneNEoa7RxnatzjcDSCmG1MXxSR1GAsXEV/Dwwykc2MPK8M2HN" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <style>
        body { background: #f8f9fa; }
        .audit-header { background: #002144; color: #fff; }
        .status-ok   { color: #198754; }
        .status-warn { color: #ffc107; }
        .status-fail { color: #dc3545; }
        .icon-ok::before   { content: "\f26a"; } /* bi-check-circle-fill */
        .icon-warn::before { content: "\f33b"; } /* bi-exclamation-triangle-fill */
        .icon-fail::before { content: "\f62a"; } /* bi-x-circle-fill */
    </style>
</head>
<body>
    <?= $this->fetch('content') ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-C6RzsynM9kWDrMNeT87bh95OGNyZPhcTNXj1NW7RuBCsyN/o0jlpcV8Qyq46cDfL" crossorigin="anonymous">
    </script>
</body>
</html>
