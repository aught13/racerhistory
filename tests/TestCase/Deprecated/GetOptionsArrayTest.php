<?php
declare(strict_types=1);

namespace App\Test\TestCase\Deprecated;

use PHPUnit\Framework\TestCase;

/**
 * Ensure Table::get() is not called with an options array (deprecated pattern).
 *
 * This test will fail if any PHP source file contains the pattern: get(..., [ 'contain' =>
 * which indicates the deprecated options-array usage. It's a simple safeguard to
 * catch accidental reintroductions in future commits.
 */
class GetOptionsArrayTest extends TestCase
{
    public function testNoGetWithOptionsArray(): void
    {
        $root = __DIR__ . '/../../..';
        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($root));
        $matches = [];
        foreach ($iterator as $file) {
            if (!$file->isFile()) {
                continue;
            }
            $path = $file->getPathname();
            if (!str_ends_with($path, '.php')) {
                continue;
            }
            $contents = file_get_contents($path) ?: '';
            if (preg_match('/->get\s*\([^,]+,\s*\[\s*[\'\"]contain\'\"]?\s*=>/i', $contents)) {
                $matches[] = $path;
            }
        }

        $this->assertEmpty($matches, "Found deprecated Table::get(..., ['contain' => ...]) usages: " . implode(', ', $matches));
    }
}
