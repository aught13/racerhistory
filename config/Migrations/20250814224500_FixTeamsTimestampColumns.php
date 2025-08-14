<?php
declare(strict_types=1);

use Migrations\AbstractMigration;

class FixTeamsTimestampColumns extends AbstractMigration
{
    /**
     * Auto ID property - declaration matches parent class for compatibility
     */
    public bool $autoId = false;

    /**
     * Up Method.
     *
     * Fix teams table timestamp columns to use datetime type for CakePHP 5.x 
     * TimestampBehavior compatibility.
     *
     * @return void
     */
    public function up(): void
    {
        $table = $this->table('teams');
        
        // Remove the old timestamp columns
        $table->removeColumn('created_at');
        $table->removeColumn('updated_at');
        
        // Add new datetime columns with the same names
        $table->addColumn('created_at', 'datetime', [
            'default' => null,
            'null' => false,
        ]);
        $table->addColumn('updated_at', 'datetime', [
            'default' => null,
            'null' => false,
        ]);
        
        $table->update();
    }

    /**
     * Down Method.
     *
     * @return void
     */
    public function down(): void
    {
        $table = $this->table('teams');
        
        // Remove the datetime columns
        $table->removeColumn('created_at');
        $table->removeColumn('updated_at');
        
        // Restore the original timestamp columns
        $driver = $this->getAdapter()->getAdapterType();
        $table->addColumn('created_at', $driver === 'sqlite' ? 'text' : 'timestamp', [
            'default' => $driver === 'sqlite' ? 'CURRENT_TIMESTAMP' : null,
            'limit' => null,
            'null' => true,
        ]);
        $table->addColumn('updated_at', $driver === 'sqlite' ? 'text' : 'timestamp', [
            'default' => $driver === 'sqlite' ? 'CURRENT_TIMESTAMP' : null,
            'limit' => null,
            'null' => true,
        ]);
        
        $table->update();
    }
}