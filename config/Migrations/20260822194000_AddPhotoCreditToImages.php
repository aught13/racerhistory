<?php
declare(strict_types=1);

use Migrations\BaseMigration;

class AddPhotoCreditToImages extends BaseMigration
{
    public bool $autoId = false;

    public function up(): void
    {
        $table = $this->table('images');
        if (!$table->hasColumn('photo_credit')) {
            $table->addColumn('photo_credit', 'string', ['limit' => 255, 'null' => true, 'default' => null, 'comment' => 'Photographer credit'])
                ->update();
        }
    }

    public function down(): void
    {
        $table = $this->table('images');
        if ($table->hasColumn('photo_credit')) {
            $table->removeColumn('photo_credit')
                ->update();
        }
    }
}
