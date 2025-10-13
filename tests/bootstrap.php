<?php
declare(strict_types=1);

/**
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link      https://cakephp.org CakePHP(tm) Project
 * @since     3.0.0
 * @license   https://opensource.org/licenses/mit-license.php MIT License
 */

use Cake\Cache\Cache;
use Cake\Datasource\ConnectionManager;
use Cake\TestSuite\Fixture\SchemaLoader;
use Migrations\TestSuite\Migrator;
use function Cake\Core\env;

// Ensure Composer autoloader is loaded first
require dirname(__DIR__) . '/vendor/autoload.php';

// Load application bootstrap (defines ROOT, paths, config, etc.) before migrations
require dirname(__DIR__) . '/config/bootstrap.php';

// ----------------------------------------------------------------------
// APP_ENV guard & deterministic in-memory test DB
// ----------------------------------------------------------------------
if (env('APP_ENV') === 'production') {
    fwrite(STDERR, '[tests bootstrap] FATAL: APP_ENV=production – refusing to run tests against production environment.' . "\n");
    exit(1);
}

// Decide which backend to use for tests:
//  - If FORCE_MYSQL_TEST=1 and a test config file exists, use that MySQL config (must include a 'test' db name containing 'test').
//  - Otherwise default to in-memory SQLite for maximum isolation + speed.
try {
    $useMysql = env('FORCE_MYSQL_TEST') === '1';
    $testConfigFile = dirname(__DIR__) . '/config/app_local.test.php';
    if ($useMysql && file_exists($testConfigFile)) {
        $cfgArr = include $testConfigFile;
        $sources = $cfgArr['Datasources'] ?? [];
        $primary = $sources['test'] ?? ($sources['default'] ?? []);
        if (empty($primary)) {
            throw new RuntimeException('No test datasource found in app_local.test.php');
        }
        if (!isset($primary['database']) || !str_contains((string)$primary['database'], 'test')) {
            throw new RuntimeException('MySQL test database name must contain "test"');
        }
        foreach (['default', 'test'] as $alias) {
            if (ConnectionManager::getConfig($alias)) {
                ConnectionManager::drop($alias);
            }
        }
        ConnectionManager::setConfig('test', $primary);
        // Alias 'default' to 'test' so ORM defaultConnectionName tables use migrated schema
        ConnectionManager::alias('test', 'default');
    } else {
        // In-memory SQLite fallback (stateless per test process)
        foreach (['default', 'test'] as $alias) {
            if (ConnectionManager::getConfig($alias)) {
                ConnectionManager::drop($alias);
            }
        }
        $sqliteCfg = [
            'className' => 'Cake\\Database\\Connection',
            'driver' => 'Cake\\Database\\Driver\\Sqlite',
            'database' => ':memory:',
            'cacheMetadata' => true,
        ];
        ConnectionManager::setConfig('test', $sqliteCfg);
        ConnectionManager::alias('test', 'default');
    }
} catch (Throwable $t) {
    fwrite(STDERR, '[tests bootstrap] FATAL: Unable to establish isolated test connection: ' . $t->getMessage() . "\n");
    exit(1);
}

// ----------------------------------------------------------------------
// Isolate test suite cache to avoid permission conflicts with web server
// Processes (http user) that may create cache files the test user (patrick)
// cannot modify. We redirect the 'model' cache to a test-only directory.
// ----------------------------------------------------------------------
try {
    $testCacheBase = TMP . 'tests' . DS . 'cache' . DS;
    $modelCacheDir = $testCacheBase . 'models' . DS;
    if (!is_dir($modelCacheDir)) {
        mkdir($modelCacheDir, 0775, true);
    }
    $existing = Cache::getConfig('model');
    if ($existing) {
        Cache::setConfig('model', $existing + ['path' => $modelCacheDir]);
    }
} catch (Throwable $t) {
    // Non-fatal: if isolation fails, continue with shared cache (may emit warnings)
}

try {
    // For now, we'll use the schema.sql file if it exists, instead of running migrations
    if (file_exists('./tests/schema.sql')) {
        (new SchemaLoader())->loadSqlFiles('./tests/schema.sql', 'test');
    } else {
        // If no schema file, try running migrations
        (new Migrator())->run(['connection' => 'test']);
    }
} catch (Exception $e) {
    // Log the error but continue - many tests are fixture-based and don't need migrations
    fwrite(STDERR, '[tests bootstrap] WARNING: Database setup issue: ' . $e->getMessage() . "\n");
}

// NOTE: Manual baseline seeding removed. Rely on fixtures declared per test case.
