<?php
declare(strict_types=1);

use Migrations\BaseMigration;

class AddNicknameAndScorebugToTeams extends BaseMigration
{
    // No need to override $autoId for this migration; we only alter an existing table.

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
