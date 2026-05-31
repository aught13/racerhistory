<?php
declare(strict_types=1);

namespace App\Service;

use Cake\Core\Configure;

/**
 * DeployAuditService
 *
 * Performs read-only production deployment audit checks,
 * mirroring bin/deploy.sh in pure PHP for browser access.
 */
class DeployAuditService
{
    /**
     * Collected audit results.
     *
     * @var array<array{category: string, label: string, status: string, detail: string}>
     */
    private array $results = [];

    private int $errors = 0;
    private int $warnings = 0;

    /**
     * Run all audit checks and return structured results.
     *
     * @return array{results: array<array{category: string, label: string, status: string, detail: string}>, errors: int, warnings: int, overall: string}
     */
    public function run(): array
    {
        $this->results = [];
        $this->errors = 0;
        $this->warnings = 0;

        $this->checkPhpVersion();
        $this->checkPhpExtensions();
        $this->checkComposer();
        $this->checkNode();
        $this->checkConfig();
        $this->checkDirectoryPermissions();
        $this->checkDependencies();
        $this->checkMigrations();
        $this->checkSecurity();
        $this->checkAssets();

        $overall = 'pass';
        if ($this->errors > 0) {
            $overall = 'fail';
        } elseif ($this->warnings > 0) {
            $overall = 'warn';
        }

        return [
            'results' => $this->results,
            'errors' => $this->errors,
            'warnings' => $this->warnings,
            'overall' => $overall,
        ];
    }

    /**
     * Record a passing check.
     *
     * @param string $category Check category.
     * @param string $label    Short description.
     * @param string $detail   Optional detail text.
     * @return void
     */
    private function ok(string $category, string $label, string $detail = ''): void
    {
        $this->results[] = compact('category', 'label', 'detail') + ['status' => 'ok'];
    }

    /**
     * Record a warning.
     *
     * @param string $category Check category.
     * @param string $label    Short description.
     * @param string $detail   Optional detail text.
     * @return void
     */
    private function warn(string $category, string $label, string $detail = ''): void
    {
        $this->results[] = compact('category', 'label', 'detail') + ['status' => 'warn'];
        $this->warnings++;
    }

    /**
     * Record a failure.
     *
     * @param string $category Check category.
     * @param string $label    Short description.
     * @param string $detail   Optional detail text.
     * @return void
     */
    private function fail(string $category, string $label, string $detail = ''): void
    {
        $this->results[] = compact('category', 'label', 'detail') + ['status' => 'fail'];
        $this->errors++;
    }

    /**
     * Check PHP version meets minimum requirement.
     *
     * @return void
     */
    private function checkPhpVersion(): void
    {
        $cat = 'PHP Version';
        $major = PHP_MAJOR_VERSION;
        $minor = PHP_MINOR_VERSION;
        $ver = $major . '.' . $minor . '.' . PHP_RELEASE_VERSION;

        /** @phpstan-ignore-next-line */
        if ($major >= 8 && $minor >= 1) {
            $this->ok($cat, "PHP {$ver}");
        } else {
            $this->fail($cat, "PHP {$ver} — requires 8.1+");
        }
    }

    /**
     * Verify required PHP extensions are loaded.
     *
     * @return void
     */
    private function checkPhpExtensions(): void
    {
        $cat = 'PHP Extensions';
        $required = ['mbstring', 'intl', 'pdo', 'pdo_mysql', 'simplexml'];
        foreach ($required as $ext) {
            if (extension_loaded($ext)) {
                $this->ok($cat, "ext-{$ext}");
            } else {
                $this->fail($cat, "Missing: ext-{$ext}");
            }
        }
    }

    /**
     * Verify composer.lock is present.
     *
     * @return void
     */
    private function checkComposer(): void
    {
        $cat = 'Composer';
        $composerLock = ROOT . DS . 'composer.lock';
        if (file_exists($composerLock)) {
            $this->ok($cat, 'composer.lock present');
        } else {
            $this->fail($cat, 'composer.lock not found');
        }
    }

    /**
     * Check Node.js tooling availability.
     *
     * @return void
     */
    private function checkNode(): void
    {
        $cat = 'Node.js';
        $packageJson = ROOT . DS . 'package.json';
        if (file_exists($packageJson)) {
            $this->ok($cat, 'package.json present');
        } else {
            $this->warn($cat, 'package.json not found — JS tooling unavailable');
        }
    }

    /**
     * Audit application configuration for production readiness.
     *
     * @return void
     */
    private function checkConfig(): void
    {
        $cat = 'Configuration';
        $localConfig = CONFIG . 'app_local.php';

        if (!file_exists($localConfig)) {
            $this->fail($cat, 'config/app_local.php not found', 'Copy from app_local.example.php and configure');

            return;
        }
        $this->ok($cat, 'config/app_local.php exists');

        // Read file content for pattern matching (no eval)
        $content = file_get_contents($localConfig);
        if ($content === false) {
            $this->warn($cat, 'Could not read config/app_local.php');

            return;
        }

        // Debug mode
        if (Configure::read('debug')) {
            $this->warn($cat, 'Debug mode is ON', 'Set debug to false for production');
        } else {
            $this->ok($cat, 'Debug mode is OFF');
        }

        // Security salt
        if (str_contains($content, '__SALT__')) {
            $this->fail($cat, 'Security salt is default __SALT__', 'Generate a unique salt');
        } else {
            $this->ok($cat, 'Security salt is configured');
        }

        // Database host
        if (preg_match("/'host'\s*=>\s*'([^']+)'/", $content, $m)) {
            $host = $m[1];
            if ($host === 'localhost' || $host === '127.0.0.1') {
                $this->warn($cat, "Database host is '{$host}'", 'Verify this is correct for production');
            } else {
                $this->ok($cat, "Database host: {$host}");
            }
        } else {
            $this->warn($cat, 'Could not detect database host from config');
        }
    }

    /**
     * Check writable directories exist and are accessible.
     *
     * @return void
     */
    private function checkDirectoryPermissions(): void
    {
        $cat = 'Directory Permissions';
        $dirs = [
            'tmp', 'logs', 'tmp' . DS . 'cache',
            'tmp' . DS . 'cache' . DS . 'models',
            'tmp' . DS . 'cache' . DS . 'persistent',
            'tmp' . DS . 'sessions',
            'webroot' . DS . 'img' . DS . 'storage',
        ];
        foreach ($dirs as $dir) {
            $fullPath = ROOT . DS . $dir;
            if (is_dir($fullPath)) {
                if (is_writable($fullPath)) {
                    $this->ok($cat, "{$dir}/ is writable");
                } else {
                    $this->fail($cat, "{$dir}/ exists but is NOT writable");
                }
            } else {
                $this->fail($cat, "{$dir}/ does not exist");
            }
        }
    }

    /**
     * Check vendor dependencies are installed correctly.
     *
     * @return void
     */
    private function checkDependencies(): void
    {
        $cat = 'Dependencies';
        $vendorDir = ROOT . DS . 'vendor';

        if (is_dir($vendorDir)) {
            // Check for dev packages
            if (is_dir($vendorDir . DS . 'phpunit') || is_dir($vendorDir . DS . 'phpstan')) {
                $this->warn(
                    $cat,
                    'Dev dependencies present in vendor/',
                    "Run 'composer install --no-dev' for production",
                );
            } else {
                $this->ok($cat, 'vendor/ exists (no dev packages detected)');
            }
        } else {
            $this->fail($cat, 'vendor/ not found', "Run 'composer install --no-dev'");
        }
    }

    /**
     * Report migration file count.
     *
     * @return void
     */
    private function checkMigrations(): void
    {
        $cat = 'Migrations';
        $migrationsDir = CONFIG . 'Migrations';
        if (is_dir($migrationsDir)) {
            $files = glob($migrationsDir . DS . '*.php');
            $count = $files ? count($files) : 0;
            $this->ok($cat, "{$count} migration file(s) found", "Run 'bin/cake migrations status' to check pending");
        } else {
            $this->warn($cat, 'No migrations directory found');
        }
    }

    /**
     * Check for security risks (debug files, exposed credentials).
     *
     * @return void
     */
    private function checkSecurity(): void
    {
        $cat = 'Security';

        // Debug files
        $debugFiles = [
            'debug_service.php',
            'tmp/debug_collect.php',
            'tmp/debug_tags.php',
            'tmp/check_rosters.php',
        ];
        foreach ($debugFiles as $f) {
            if (file_exists(ROOT . DS . $f)) {
                $this->warn($cat, "Debug file present: {$f}", 'Remove for production');
            }
        }

        // Sensitive files in webroot
        $webroot = ROOT . DS . 'webroot';
        if (file_exists($webroot . DS . '.env')) {
            $this->fail($cat, '.env file in webroot/', 'Publicly accessible — remove immediately');
        }
        if (file_exists($webroot . DS . 'app_local.php')) {
            $this->fail($cat, 'app_local.php in webroot/', 'Credentials exposed — remove immediately');
        }

        // .htaccess
        if (file_exists($webroot . DS . '.htaccess')) {
            $this->ok($cat, 'webroot/.htaccess present');
        } else {
            $this->warn($cat, 'webroot/.htaccess missing', 'Required for Apache URL rewriting');
        }
    }

    /**
     * Verify critical frontend assets are present.
     *
     * @return void
     */
    private function checkAssets(): void
    {
        $cat = 'Frontend Assets';
        $assets = [
            'js/main.js',
            'js/legacy/admin-dashboard.js',
            'js/legacy/blog-view-init-loader.js',
            'js/legacy/game-form-lookups.js',
            'js/legacy/game-view-init-loader.mjs',
            'js/legacy/games-search-init.mjs',
            'js/legacy/games-series-opponents-init.mjs',
            'js/legacy/image-retry.mjs',
            'js/legacy/people-index-init-loader.mjs',
            'js/legacy/person-blog-popover-loader.mjs',
            'js/legacy/person-game-log-tabs-loader.mjs',
            'js/legacy/season-view-init-loader.mjs',
            'js/legacy/seasons-init-loader.mjs',
            'js/legacy/stats-init-loader.mjs',
            'js/legacy/modules/seasons-init.js',
            'js/legacy/crop-selector.js',
            'js/legacy/games_sport_dynamic.js',
            'js/legacy/image-selector.js',
            'js/legacy/person-image.js',
            'js/legacy/sport-aware-game-form.js',
            'webroot/css/cake.css',
            'webroot/dist/manifest.json',
        ];
        foreach ($assets as $asset) {
            if (file_exists(ROOT . DS . $asset)) {
                $this->ok($cat, $asset);
            } else {
                $this->fail($cat, "Missing: {$asset}");
            }
        }

        $manifestPath = ROOT . DS . 'webroot' . DS . 'dist' . DS . 'manifest.json';
        if (file_exists($manifestPath)) {
            $manifestRaw = file_get_contents($manifestPath);
            if ($manifestRaw === false) {
                $this->fail($cat, 'Could not read webroot/dist/manifest.json');
            } else {
                /** @var mixed $manifest */
                $manifest = json_decode($manifestRaw, true);
                if (is_array($manifest) && array_key_exists('js/main.js', $manifest)) {
                    $this->ok($cat, 'Vite manifest contains js/main.js entry');
                } else {
                    $this->fail($cat, 'Vite manifest missing js/main.js entry');
                }
            }
        }

        // TinyMCE
        if (is_dir(ROOT . DS . 'webroot' . DS . 'js' . DS . 'tinymce')) {
            $this->ok($cat, 'TinyMCE library present');
        } else {
            $this->warn($cat, 'webroot/js/tinymce/ not found', 'Admin rich text editors will fail');
        }
    }
}
