#!/usr/bin/env php
<?php
declare(strict_types=1);

/**
 * run-release-migrations.php
 *
 * Cron-safe migration runner for shared hosting.
 * Runs migrations only when a new deployed release marker is detected.
 */

$appRoot = realpath(__DIR__ . '/..') ?: dirname(__DIR__);
$releaseFile = $appRoot . '/.release_sha';
$stateDir = $appRoot . '/tmp/deploy';
$stateFile = $stateDir . '/.last_migrated_release';
$lockDir = $stateDir . '/.migrate_lock';

$phpBin = PHP_BINARY;

$log = static function (string $message): void {
    $timestamp = date('Y-m-d H:i:s');
    echo $timestamp . ' [release-migrate] ' . $message . PHP_EOL;
};

if (!file_exists($releaseFile)) {
    $log('no .release_sha marker found, skipping');
    exit(0);
}

$currentRelease = trim((string)file_get_contents($releaseFile));
if ($currentRelease === '') {
    $log('empty .release_sha marker, skipping');
    exit(0);
}

if (!is_dir($stateDir) && !mkdir($stateDir, 0775, true) && !is_dir($stateDir)) {
    $log('failed to create state directory: ' . $stateDir);
    exit(1);
}

$lastRelease = file_exists($stateFile) ? trim((string)file_get_contents($stateFile)) : '';
if ($currentRelease === $lastRelease) {
    $log('release ' . $currentRelease . ' already migrated');
    exit(0);
}

if (!@mkdir($lockDir, 0775) && !is_dir($lockDir)) {
    $log('another migration run is in progress, skipping');
    exit(0);
}

register_shutdown_function(static function () use ($lockDir): void {
    if (is_dir($lockDir)) {
        @rmdir($lockDir);
    }
});

if (!chdir($appRoot)) {
    $log('failed to switch to app root: ' . $appRoot);
    exit(1);
}

$log('new release detected (' . $currentRelease . '); running migrations');

$migrateCmd = escapeshellarg($phpBin) . ' bin/cake migrations migrate --no-interaction';
passthru($migrateCmd, $migrateExitCode);
if ($migrateExitCode !== 0) {
    $log('migration command failed with exit code ' . (string)$migrateExitCode);
    exit($migrateExitCode);
}

$cacheCmd = escapeshellarg($phpBin) . ' bin/cake cache clear_all';
passthru($cacheCmd, $cacheExitCode);
if ($cacheExitCode !== 0) {
    $log('cache clear returned non-zero exit code ' . (string)$cacheExitCode . ' (continuing)');
}

if (file_put_contents($stateFile, $currentRelease . PHP_EOL) === false) {
    $log('failed to write state file: ' . $stateFile);
    exit(1);
}

$log('migration run complete for release ' . $currentRelease);
exit(0);
