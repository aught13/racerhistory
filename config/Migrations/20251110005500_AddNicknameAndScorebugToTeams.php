<?php
declare(strict_types=1);

use Migrations\AbstractMigration;

class AddNicknameAndScorebugToTeams extends AbstractMigration
{
    /**
     * Whether the tables created in this migration
     * should auto-create an `id` field or not
     *
     * This option is global for all tables created in the migration file.
     * If you set it to false, you have to manually add the primary keys for your
     * tables using the Migrations\Table::addPrimaryKey() method
     *
     * @var bool
     */
    public bool $autoId = false;

    /**
     * Change Method.
     *
     * More information on this method is available here:
     * https://book.cakephp.org/phinx/0/en/migrations.html#the-change-method
     * @return void
     */
    public function change(): void
    {
        $table = $this->table('teams');
        
        $table->addColumn('team_nickname', 'string', [
            'default' => null,
            'limit' => 30,
            'null' => false,
            'after' => 'team_name',
        ]);
        
        $table->addColumn('team_scorebug', 'string', [
            'default' => null,
            'limit' => 6,
            'null' => false,
            'after' => 'team_nickname',
        ]);
        
        $table->update();
    }
}
