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
use Cake\ORM\TableRegistry;
use Cake\TestSuite\Fixture\SchemaLoader;
use Migrations\TestSuite\Migrator;

// Ensure Composer autoloader is loaded first
require dirname(__DIR__) . '/vendor/autoload.php';

// Load application bootstrap (defines ROOT, paths, config, etc.) before migrations
require dirname(__DIR__) . '/config/bootstrap.php';

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
    (new Migrator())->run();
} catch (Exception $e) {
    // If migrations fail, try to use schema loader as fallback
    if (file_exists('./tests/schema.sql')) {
        (new SchemaLoader())->loadSqlFiles('./tests/schema.sql', 'test');
    } else {
        // If no schema file, re-throw the original exception
        throw $e;
    }
}

// Global baseline seed for critical tables (Users, SiteOptions) – kept minimal to reduce runtime.
try {
    $users = TableRegistry::getTableLocator()->get('Users');
    if ($users->find()->count() === 0) {
        $baselineUsers = [
            [
                'id' => 1,
                'username' => 'admin',
                'email' => 'admin@example.com',
                'password' => '$2y$10$usesomesillystringfore7hnbRJHxXVLeakoG8K30oukPsA.ztMG',
                'role' => 'admin',
                'status' => 'active',
            ],
            [
                'id' => 2,
                'username' => 'user',
                'email' => 'user@example.com',
                'password' => '$2y$10$usesomesillystringfore7hnbRJHxXVLeakoG8K30oukPsA.ztMG',
                'role' => 'user',
                'status' => 'inactive',
            ],
        ];
        foreach ($baselineUsers as $row) {
            $entity = $users->newEntity($row, ['accessibleFields' => ['*' => true]]);
            $users->saveOrFail($entity);
        }
    }
    $siteOptions = TableRegistry::getTableLocator()->get('SiteOptions');
    $registration = $siteOptions->find()->where(['option_key' => 'registration'])->first();
    if (!$registration) {
        $registration = $siteOptions->newEntity([
            'option_key' => 'registration',
            'value' => 'true',
        ], ['accessibleFields' => ['*' => true]]);
        $siteOptions->saveOrFail($registration);
    }
} catch (Throwable $t) {
    // ignore seeding issues
}