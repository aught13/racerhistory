<?php
declare(strict_types=1);

use Migrations\AbstractMigration;

/**
 * Add storage_subdir column to images for stable path reference.
 */
class AddStorageSubdirToImages extends AbstractMigration
{
    public bool $autoId = false; // keep consistent with other migrations (manual IDs defined elsewhere)

    public function up(): void
    {
        if (!$this->hasTable('images')) {
            return; // safety
        }
        $table = $this->table('images');
        if (!$table->hasColumn('storage_subdir')) {
            $table->addColumn('storage_subdir', 'string', [
                'limit' => 16,
                'default' => '',
                'null' => false,
                'after' => 'filename',
            ])->update();
        }
        // Populate storage_subdir for existing rows by scanning storage/images/YYYY/MM directories.
        $base = ROOT . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'images';
        $rows = $this->fetchAll('SELECT id, filename, storage_subdir FROM images');
        if (!$rows) {
            return;
        }
        // Build mapping: filename => subdir
        $map = [];
        if (is_dir($base)) {
            $years = glob($base . DIRECTORY_SEPARATOR . '*', GLOB_ONLYDIR) ?: [];
            foreach ($years as $yearDir) {
                $months = glob($yearDir . DIRECTORY_SEPARATOR . '*', GLOB_ONLYDIR) ?: [];
                foreach ($months as $monthDir) {
                    $subdir = basename($yearDir) . '/' . basename($monthDir);
                    foreach (glob($monthDir . DIRECTORY_SEPARATOR . '*') as $filePath) {
                        if (is_file($filePath)) {
                            $fn = basename($filePath);
                            if (!isset($map[$fn])) {
                                $map[$fn] = $subdir;
                            }
                        }
                    }
                }
            }
        }
        $nowSubdir = date('Y') . '/' . date('m');
        foreach ($rows as $row) {
            if (!empty($row['storage_subdir'])) {
                continue; // already set
            }
            $subdir = $map[$row['filename']] ?? $nowSubdir;
            $this->execute(sprintf(
                "UPDATE images SET storage_subdir = '%s' WHERE id = %d",
                addslashes($subdir),
                (int)$row['id']
            ));
        }
    }

    public function down(): void
    {
        if ($this->hasTable('images')) {
            $table = $this->table('images');
            if ($table->hasColumn('storage_subdir')) {
                $table->removeColumn('storage_subdir')->update();
            }
        }
    }
}
