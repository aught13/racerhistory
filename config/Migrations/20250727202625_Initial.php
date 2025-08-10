<?php
declare(strict_types=1);

use Migrations\AbstractMigration;

class Initial extends AbstractMigration
{
    /**
     * Auto ID property - declaration matches parent class for compatibility
     *
     * Note: Different versions of Migrations\AbstractMigration have different
     * type declaration requirements. We need to match the parent exactly.
     */
    public bool $autoId = false;

    /**
     * Up Method.
     *
     * More information on this method is available here:
     * https://book.cakephp.org/phinx/0/en/migrations.html#the-up-method
     * @return void
     */
    public function up(): void
    {
        $this->table('game_eav')
            ->addColumn('id', 'integer', [
                'autoIncrement' => true,
                'default' => null,
                'limit' => null,
                'null' => false,
                'signed' => false,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('game_id', 'integer', [
                'default' => null,
                'limit' => null,
                'null' => true,
                'signed' => false,
            ])
            ->addColumn('key', 'string', [
                'default' => null,
                'limit' => 162,
                'null' => true,
            ])
            ->addColumn('value', 'text', [
                'default' => null,
                'limit' => 16777215,
                'null' => true,
            ])
            ->create();

        $this->table('game_types')
            ->addColumn('id', 'integer', [
                'autoIncrement' => true,
                'default' => null,
                'limit' => null,
                'null' => false,
                'signed' => false,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('game_type_name', 'string', [
                'default' => null,
                'limit' => 162,
                'null' => true,
            ])
            ->addColumn('post', 'boolean', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('conf', 'boolean', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('ind', 'string', [
                'default' => null,
                'limit' => 2,
                'null' => true,
            ])
            ->create();

        // Refactored for MariaDB/SQLite compatibility
        $driver = $this->getAdapter()->getAdapterType();
        $this->table('games')
            ->addColumn('id', 'integer', [
                'autoIncrement' => true,
                'default' => null,
                'limit' => null,
                'null' => false,
                'signed' => false,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('team_season_id', 'integer', [
                'default' => null,
                'limit' => null,
                'null' => false,
                'signed' => false,
            ])
            ->addColumn('game_date', 'date', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('game_time', 'string', [
                'default' => null,
                'limit' => 5,
                'null' => true,
            ])
            ->addColumn('game_duration', 'string', [
                'default' => null,
                'limit' => 5,
                'null' => true,
            ])
            ->addColumn('game_type_id', 'integer', [
                'default' => null,
                'limit' => null,
                'null' => true,
                'signed' => false,
            ])
            ->addColumn('opponent_id', 'integer', [
                'default' => null,
                'limit' => null,
                'null' => true,
                'signed' => false,
            ])
            ->addColumn('place_id', 'integer', [
                'default' => null,
                'limit' => null,
                'null' => true,
                'signed' => false,
            ])
            ->addColumn('site_id', 'integer', [
                'default' => null,
                'limit' => null,
                'null' => true,
                'signed' => false,
            ])
            ->addColumn('hrn', 'integer', [
                'default' => null,
                'limit' => null,
                'null' => false,
                'signed' => true,
            ])
            ->addColumn('post', 'boolean', [
                'default' => false,
                'limit' => null,
                'null' => false,
            ])
            ->addColumn('w', 'string', [
                'default' => '0',
                'limit' => 1,
                'null' => true,
            ])
            ->addColumn('l', 'string', [
                'default' => '0',
                'limit' => 1,
                'null' => true,
            ])
            ->addColumn('pts_mur', 'string', [
                'default' => null,
                'limit' => 3,
                'null' => true,
            ])
            ->addColumn('pts_opp', 'string', [
                'default' => null,
                'limit' => 3,
                'null' => true,
            ])
            ->addColumn('mur_rk', 'string', [
                'default' => null,
                'limit' => 2,
                'null' => true,
            ])
            ->addColumn('opp_rk', 'string', [
                'default' => null,
                'limit' => 2,
                'null' => true,
            ])
            ->addColumn('periods', 'string', [
                'default' => null,
                'limit' => 1,
                'null' => true,
            ])
            ->addColumn('ot', 'string', [
                'default' => null,
                'limit' => 2,
                'null' => true,
            ])
            ->addColumn('attendance', 'string', [
                'default' => null,
                'limit' => 7,
                'null' => true,
            ])
            ->addColumn('game_preview', 'text', [
                'default' => null,
                'limit' => 16777215,
                'null' => true,
            ])
            ->addColumn('game_recap', 'text', [
                'default' => null,
                'limit' => 16777215,
                'null' => true,
            ])
            ->addColumn('game_notes', 'text', [
                'default' => null,
                'limit' => 16777215,
                'null' => true,
            ])
            ->addColumn('created_at', $driver === 'sqlite' ? 'text' : 'timestamp', [
                'default' => $driver === 'sqlite' ? 'CURRENT_TIMESTAMP' : null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('updated_at', $driver === 'sqlite' ? 'text' : 'timestamp', [
                'default' => $driver === 'sqlite' ? 'CURRENT_TIMESTAMP' : null,
                'limit' => null,
                'null' => true,
            ])
            ->create();

        $this->table('opponents')
            ->addColumn('id', 'integer', [
                'autoIncrement' => true,
                'default' => null,
                'limit' => null,
                'null' => false,
                'signed' => false,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('opponent_name', 'string', [
                'default' => null,
                'limit' => 162,
                'null' => true,
            ])
            ->addColumn('opponent_mascot', 'string', [
                'default' => null,
                'limit' => 162,
                'null' => true,
            ])
            ->addColumn('opponent_current', 'integer', [
                'default' => null,
                'limit' => null,
                'null' => true,
                'signed' => false,
            ])
            ->addColumn('opponent_short', 'string', [
                'default' => null,
                'limit' => 30,
                'null' => true,
            ])
            ->addColumn('opponent_abbr', 'string', [
                'default' => null,
                'limit' => 6,
                'null' => true,
            ])
            ->addColumn('place_id', 'integer', [
                'default' => null,
                'limit' => null,
                'null' => true,
                'signed' => false,
            ])
            ->addColumn('created_at', $driver === 'sqlite' ? 'text' : 'timestamp', [
                'default' => $driver === 'sqlite' ? 'CURRENT_TIMESTAMP' : null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('updated_at', $driver === 'sqlite' ? 'text' : 'timestamp', [
                'default' => $driver === 'sqlite' ? 'CURRENT_TIMESTAMP' : null,
                'limit' => null,
                'null' => true,
            ])
            ->create();

        $this->table('persons')
            ->addColumn('id', 'integer', [
                'autoIncrement' => true,
                'default' => null,
                'limit' => null,
                'null' => false,
                'signed' => false,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('first', 'string', [
                'default' => null,
                'limit' => 30,
                'null' => true,
            ])
            ->addColumn('last', 'string', [
                'default' => null,
                'limit' => 30,
                'null' => true,
            ])
            ->addColumn('full', 'string', [
                'default' => null,
                'limit' => 162,
                'null' => true,
            ])
            ->addColumn('display', 'string', [
                'default' => null,
                'limit' => 162,
                'null' => true,
            ])
            ->addColumn('birth', 'date', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('death', 'date', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('person_image', 'string', [
                'default' => null,
                'limit' => 162,
                'null' => true,
            ])
            ->addColumn('created_at', $driver === 'sqlite' ? 'text' : 'timestamp', [
                'default' => $driver === 'sqlite' ? 'CURRENT_TIMESTAMP' : 'current_timestamp()',
                'limit' => null,
                'null' => false,
            ])
            ->addColumn('updated_at', $driver === 'sqlite' ? 'text' : 'timestamp', [
                'default' => $driver === 'sqlite' ? 'CURRENT_TIMESTAMP' : 'current_timestamp()',
                'limit' => null,
                'null' => false,
            ])
            ->create();

        $this->table('places')
            ->addColumn('id', 'integer', [
                'autoIncrement' => true,
                'default' => null,
                'limit' => null,
                'null' => false,
                'signed' => false,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('place_name', 'string', [
                'default' => null,
                'limit' => 162,
                'null' => false,
            ])
            ->addColumn('place_state', 'string', [
                'default' => null,
                'limit' => 162,
                'null' => false,
            ])
            ->addColumn('created_at', $driver === 'sqlite' ? 'text' : 'timestamp', [
                'default' => $driver === 'sqlite' ? 'CURRENT_TIMESTAMP' : null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('updated_at', $driver === 'sqlite' ? 'text' : 'timestamp', [
                'default' => $driver === 'sqlite' ? 'CURRENT_TIMESTAMP' : null,
                'limit' => null,
                'null' => true,
            ])
            ->addIndex(
                [
                    'place_name',
                    'place_state',
                ],
                [
                    'name' => 'duplicate',
                    'unique' => true
                ]
            )
            ->create();

        $this->table('seasons')
            ->addColumn('id', 'integer', [
                'autoIncrement' => true,
                'default' => null,
                'limit' => null,
                'null' => false,
                'signed' => false,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('start', 'string', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addColumn('end', 'string', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addColumn('created_at', $driver === 'sqlite' ? 'text' : 'timestamp', [
                'default' => $driver === 'sqlite' ? 'CURRENT_TIMESTAMP' : 'current_timestamp()',
                'limit' => null,
                'null' => false,
            ])
            ->addColumn('updated_at', $driver === 'sqlite' ? 'text' : 'timestamp', [
                'default' => $driver === 'sqlite' ? 'CURRENT_TIMESTAMP' : null,
                'limit' => null,
                'null' => true,
            ])
            ->create();

        $this->table('sites')
            ->addColumn('id', 'integer', [
                'autoIncrement' => true,
                'default' => null,
                'limit' => null,
                'null' => false,
                'signed' => false,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('place_id', 'integer', [
                'default' => null,
                'limit' => null,
                'null' => false,
                'signed' => false,
            ])
            ->addColumn('site_name', 'string', [
                'default' => null,
                'limit' => 162,
                'null' => false,
            ])
            ->addColumn('capacity', 'string', [
                'default' => null,
                'limit' => 7,
                'null' => true,
            ])
            ->addColumn('site_image', 'string', [
                'default' => null,
                'limit' => 162,
                'null' => true,
            ])
            ->addColumn('site_info', 'text', [
                'default' => null,
                'limit' => 16777215,
                'null' => true,
            ])
            ->addColumn('created_at', $driver === 'sqlite' ? 'text' : 'timestamp', [
                'default' => $driver === 'sqlite' ? 'CURRENT_TIMESTAMP' : null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('updated_at', $driver === 'sqlite' ? 'text' : 'timestamp', [
                'default' => $driver === 'sqlite' ? 'CURRENT_TIMESTAMP' : null,
                'limit' => null,
                'null' => true,
            ])
            ->create();

        $this->table('sports')
            ->addColumn('id', 'integer', [
                'autoIncrement' => true,
                'default' => null,
                'limit' => null,
                'null' => false,
                'signed' => false,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('sport_name', 'string', [
                'default' => null,
                'limit' => 162,
                'null' => false,
            ])
            ->addColumn('created_at', $driver === 'sqlite' ? 'text' : 'timestamp', [
                'default' => $driver === 'sqlite' ? 'CURRENT_TIMESTAMP' : null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('updated_at', $driver === 'sqlite' ? 'text' : 'timestamp', [
                'default' => $driver === 'sqlite' ? 'CURRENT_TIMESTAMP' : null,
                'limit' => null,
                'null' => true,
            ])
            ->addIndex(
                ['sport_name'],
                [
                    'name' => 'name',
                    'unique' => true
                ]
            )
            ->create();

        $this->table('stat_basket_game_box')
            ->addColumn('id', 'integer', [
                'autoIncrement' => true,
                'default' => null,
                'limit' => null,
                'null' => false,
                'signed' => false,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('game_id', 'integer', [
                'default' => null,
                'limit' => null,
                'null' => true,
                'signed' => false,
            ])
            ->addColumn('opponent_id', 'integer', [
                'default' => null,
                'limit' => null,
                'null' => true,
                'signed' => false,
            ])
            ->addColumn('period', 'string', [
                'default' => null,
                'limit' => 10,
                'null' => true,
            ])
            ->addColumn('FGM', 'string', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('FGA', 'string', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('TPM', 'string', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('TPA', 'string', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('FTM', 'string', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('FTA', 'string', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('ORB', 'string', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('DRB', 'string', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('RB', 'string', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('AST', 'string', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('STL', 'string', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('BS', 'string', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('TRN', 'string', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('PF', 'string', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('TF', 'string', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('PTS', 'string', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('PNT', 'string', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('OTO', 'string', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('SND', 'string', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('FB', 'string', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('BN', 'string', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('TIED', 'string', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('LC', 'string', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('created_at', $driver === 'sqlite' ? 'text' : 'timestamp', [
                'default' => $driver === 'sqlite' ? 'CURRENT_TIMESTAMP' : null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('updated_at', $driver === 'sqlite' ? 'text' : 'timestamp', [
                'default' => $driver === 'sqlite' ? 'CURRENT_TIMESTAMP' : null,
                'limit' => null,
                'null' => true,
            ])
            ->create();

        $this->table('stat_basket_game_opponent')
            ->addColumn('id', 'integer', [
                'autoIncrement' => true,
                'default' => null,
                'limit' => null,
                'null' => false,
                'signed' => false,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('game_id', 'integer', [
                'default' => null,
                'limit' => null,
                'null' => true,
                'signed' => false,
            ])
            ->addColumn('period', 'string', [
                'default' => null,
                'limit' => 10,
                'null' => true,
            ])
            ->addColumn('name', 'string', [
                'default' => null,
                'limit' => 162,
                'null' => true,
            ])
            ->addColumn('jersey', 'string', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('position', 'string', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('GP', 'string', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('GS', 'string', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('MIN', 'string', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('FGM', 'string', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('FGA', 'string', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('TPM', 'string', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('TPA', 'string', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('FTM', 'string', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('FTA', 'string', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('ORB', 'string', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('DRB', 'string', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('RB', 'string', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('AST', 'string', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('STL', 'string', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('BS', 'string', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('BD', 'string', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('TRN', 'string', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('PF', 'string', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('TF', 'string', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('FD', 'string', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('PTS', 'string', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('created_at', $driver === 'sqlite' ? 'text' : 'timestamp', [
                'default' => $driver === 'sqlite' ? 'CURRENT_TIMESTAMP' : null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('updated_at', $driver === 'sqlite' ? 'text' : 'timestamp', [
                'default' => $driver === 'sqlite' ? 'CURRENT_TIMESTAMP' : null,
                'limit' => null,
                'null' => true,
            ])
            ->create();

        $this->table('stat_basket_game_person')
            ->addColumn('id', 'integer', [
                'autoIncrement' => true,
                'default' => null,
                'limit' => null,
                'null' => false,
                'signed' => false,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('team_season_roster_id', 'integer', [
                'default' => null,
                'limit' => null,
                'null' => true,
                'signed' => false,
            ])
            ->addColumn('game_id', 'integer', [
                'default' => null,
                'limit' => null,
                'null' => true,
                'signed' => false,
            ])
            ->addColumn('period', 'string', [
                'default' => null,
                'limit' => 10,
                'null' => true,
            ])
            ->addColumn('GP', 'string', [
                'default' => null,
                'limit' => 1,
                'null' => true,
            ])
            ->addColumn('GS', 'string', [
                'default' => null,
                'limit' => 1,
                'null' => true,
            ])
            ->addColumn('MIN', 'string', [
                'default' => null,
                'limit' => 3,
                'null' => true,
            ])
            ->addColumn('FGM', 'string', [
                'default' => null,
                'limit' => 2,
                'null' => true,
            ])
            ->addColumn('FGA', 'string', [
                'default' => null,
                'limit' => 3,
                'null' => true,
            ])
            ->addColumn('TPM', 'string', [
                'default' => null,
                'limit' => 2,
                'null' => true,
            ])
            ->addColumn('TPA', 'string', [
                'default' => null,
                'limit' => 2,
                'null' => true,
            ])
            ->addColumn('FTM', 'string', [
                'default' => null,
                'limit' => 2,
                'null' => true,
            ])
            ->addColumn('FTA', 'string', [
                'default' => null,
                'limit' => 2,
                'null' => true,
            ])
            ->addColumn('ORB', 'string', [
                'default' => null,
                'limit' => 2,
                'null' => true,
            ])
            ->addColumn('DRB', 'string', [
                'default' => null,
                'limit' => 2,
                'null' => true,
            ])
            ->addColumn('RB', 'string', [
                'default' => null,
                'limit' => 2,
                'null' => true,
            ])
            ->addColumn('AST', 'string', [
                'default' => null,
                'limit' => 2,
                'null' => true,
            ])
            ->addColumn('STL', 'string', [
                'default' => null,
                'limit' => 2,
                'null' => true,
            ])
            ->addColumn('BS', 'string', [
                'default' => null,
                'limit' => 2,
                'null' => true,
            ])
            ->addColumn('BD', 'string', [
                'default' => null,
                'limit' => 2,
                'null' => true,
            ])
            ->addColumn('TRN', 'string', [
                'default' => null,
                'limit' => 2,
                'null' => true,
            ])
            ->addColumn('PF', 'string', [
                'default' => null,
                'limit' => 1,
                'null' => true,
            ])
            ->addColumn('TF', 'string', [
                'default' => null,
                'limit' => 1,
                'null' => true,
            ])
            ->addColumn('FD', 'string', [
                'default' => null,
                'limit' => 1,
                'null' => true,
            ])
            ->addColumn('PTS', 'string', [
                'default' => null,
                'limit' => 3,
                'null' => true,
            ])
            ->addColumn('created_at', $driver === 'sqlite' ? 'text' : 'timestamp', [
                'default' => $driver === 'sqlite' ? 'CURRENT_TIMESTAMP' : null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('updated_at', $driver === 'sqlite' ? 'text' : 'timestamp', [
                'default' => $driver === 'sqlite' ? 'CURRENT_TIMESTAMP' : null,
                'limit' => null,
                'null' => true,
            ])
            ->create();

        $this->table('stat_basket_game_team')
            ->addColumn('id', 'integer', [
                'autoIncrement' => true,
                'default' => null,
                'limit' => null,
                'null' => false,
                'signed' => false,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('game_id', 'integer', [
                'default' => null,
                'limit' => null,
                'null' => true,
                'signed' => false,
            ])
            ->addColumn('opp', 'boolean', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addColumn('ORB', 'string', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('DRB', 'string', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('RB', 'string', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('TRN', 'string', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('TF', 'string', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('PTS', 'string', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('created_at', $driver === 'sqlite' ? 'text' : 'timestamp', [
                'default' => $driver === 'sqlite' ? 'CURRENT_TIMESTAMP' : null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('updated_at', $driver === 'sqlite' ? 'text' : 'timestamp', [
                'default' => $driver === 'sqlite' ? 'CURRENT_TIMESTAMP' : null,
                'limit' => null,
                'null' => true,
            ])
            ->create();

        $this->table('stat_basket_season_opponent')
            ->addColumn('id', 'integer', [
                'autoIncrement' => true,
                'default' => null,
                'limit' => null,
                'null' => false,
                'signed' => false,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('team_season_id', 'integer', [
                'default' => null,
                'limit' => null,
                'null' => true,
                'signed' => false,
            ])
            ->addColumn('GP', 'string', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('MIN', 'string', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('FGM', 'string', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('FGA', 'string', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('TPM', 'string', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('TPA', 'string', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('FTM', 'string', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('FTA', 'string', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('ORB', 'string', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('DRB', 'string', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('RB', 'string', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('AST', 'string', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('STL', 'string', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('BS', 'string', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('TRN', 'string', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('PF', 'string', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('TF', 'string', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('PTS', 'string', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('created_at', $driver === 'sqlite' ? 'text' : 'timestamp', [
                'default' => $driver === 'sqlite' ? 'CURRENT_TIMESTAMP' : null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('updated_at', $driver === 'sqlite' ? 'text' : 'timestamp', [
                'default' => $driver === 'sqlite' ? 'CURRENT_TIMESTAMP' : null,
                'limit' => null,
                'null' => true,
            ])
            ->create();

        $this->table('stat_basket_season_person')
            ->addColumn('id', 'integer', [
                'autoIncrement' => true,
                'default' => null,
                'limit' => null,
                'null' => false,
                'signed' => false,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('team_season_roster_id', 'integer', [
                'default' => null,
                'limit' => null,
                'null' => true,
                'signed' => false,
            ])
            ->addColumn('GP', 'string', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('GS', 'string', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('MIN', 'string', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('FGM', 'string', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('FGA', 'string', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('TPM', 'string', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('TPA', 'string', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('FTM', 'string', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('FTA', 'string', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('ORB', 'string', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('DRB', 'string', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('RB', 'string', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('AST', 'string', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('STL', 'string', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('BS', 'string', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('TRN', 'string', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('PF', 'string', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('TF', 'string', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('PTS', 'integer', [
                'default' => null,
                'limit' => null,
                'null' => true,
                'signed' => true,
            ])
            ->addColumn('created_at', $driver === 'sqlite' ? 'text' : 'timestamp', [
                'default' => $driver === 'sqlite' ? 'CURRENT_TIMESTAMP' : null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('updated_at', $driver === 'sqlite' ? 'text' : 'timestamp', [
                'default' => $driver === 'sqlite' ? 'CURRENT_TIMESTAMP' : null,
                'limit' => null,
                'null' => true,
            ])
            ->create();

        $this->table('stat_basket_season_team')
            ->addColumn('id', 'integer', [
                'autoIncrement' => true,
                'default' => null,
                'limit' => null,
                'null' => false,
                'signed' => false,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('team_season_id', 'integer', [
                'default' => null,
                'limit' => null,
                'null' => true,
                'signed' => false,
            ])
            ->addColumn('GP', 'string', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('MIN', 'string', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('FGM', 'string', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('FGA', 'string', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('TPM', 'string', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('TPA', 'string', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('FTM', 'string', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('FTA', 'string', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('ORB', 'string', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('DRB', 'string', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('RB', 'string', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('AST', 'string', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('STL', 'string', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('BS', 'string', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('TRN', 'string', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('PF', 'string', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('TF', 'string', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('PTS', 'string', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('created_at', $driver === 'sqlite' ? 'text' : 'timestamp', [
                'default' => $driver === 'sqlite' ? 'CURRENT_TIMESTAMP' : null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('updated_at', $driver === 'sqlite' ? 'text' : 'timestamp', [
                'default' => $driver === 'sqlite' ? 'CURRENT_TIMESTAMP' : null,
                'limit' => null,
                'null' => true,
            ])
            ->create();

        $this->table('stats')
            ->addColumn('id', 'integer', [
                'autoIncrement' => true,
                'default' => null,
                'limit' => null,
                'null' => false,
                'signed' => false,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('sport_id', 'integer', [
                'default' => null,
                'limit' => null,
                'null' => false,
                'signed' => false,
            ])
            ->addColumn('stat_name', 'string', [
                'default' => null,
                'limit' => 162,
                'null' => false,
            ])
            ->addColumn('stat_table', 'string', [
                'default' => null,
                'limit' => 162,
                'null' => false,
            ])
            ->addColumn('created_at', $driver === 'sqlite' ? 'text' : 'timestamp', [
                'default' => $driver === 'sqlite' ? 'CURRENT_TIMESTAMP' : null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('updated_at', $driver === 'sqlite' ? 'text' : 'timestamp', [
                'default' => $driver === 'sqlite' ? 'CURRENT_TIMESTAMP' : null,
                'limit' => null,
                'null' => true,
            ])
            ->create();

        $this->table('team_season')
            ->addColumn('id', 'integer', [
                'autoIncrement' => true,
                'default' => null,
                'limit' => null,
                'null' => false,
                'signed' => false,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('team_id', 'integer', [
                'default' => null,
                'limit' => null,
                'null' => false,
                'signed' => false,
            ])
            ->addColumn('season_id', 'integer', [
                'default' => null,
                'limit' => null,
                'null' => false,
                'signed' => false,
            ])
            ->addColumn('semester', 'integer', [
                'default' => null,
                'limit' => null,
                'null' => false,
                'signed' => true,
            ])
            ->addColumn('league', 'string', [
                'default' => null,
                'limit' => 240,
                'null' => true,
            ])
            ->addColumn('league_abbr', 'string', [
                'default' => null,
                'limit' => 10,
                'null' => true,
            ])
            ->addColumn('league_finish', 'string', [
                'default' => null,
                'limit' => 240,
                'null' => true,
            ])
            ->addColumn('league_torunament_finish', 'string', [
                'default' => null,
                'limit' => 240,
                'null' => true,
            ])
            ->addColumn('last_post_game', 'string', [
                'default' => null,
                'limit' => 240,
                'null' => true,
            ])
            ->addColumn('team_season_notes', 'string', [
                'default' => null,
                'limit' => 240,
                'null' => true,
            ])
            ->addColumn('team_season_image', 'string', [
                'default' => null,
                'limit' => 162,
                'null' => true,
            ])
            ->addColumn('team_season_preview', 'text', [
                'default' => null,
                'limit' => 16777215,
                'null' => true,
            ])
            ->addColumn('team_season_recap', 'text', [
                'default' => null,
                'limit' => 16777215,
                'null' => true,
            ])
            ->addColumn('created_at', $driver === 'sqlite' ? 'text' : 'timestamp', [
                'default' => $driver === 'sqlite' ? 'CURRENT_TIMESTAMP' : null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('updated_at', $driver === 'sqlite' ? 'text' : 'timestamp', [
                'default' => $driver === 'sqlite' ? 'CURRENT_TIMESTAMP' : null,
                'limit' => null,
                'null' => true,
            ])
            ->create();

        $this->table('team_season_roster')
            ->addColumn('id', 'integer', [
                'autoIncrement' => true,
                'default' => null,
                'limit' => null,
                'null' => false,
                'signed' => true,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('team_season_id', 'integer', [
                'default' => null,
                'limit' => null,
                'null' => false,
                'signed' => false,
            ])
            ->addColumn('person_id', 'integer', [
                'default' => null,
                'limit' => null,
                'null' => false,
                'signed' => false,
            ])
            ->addColumn('roster_year', 'string', [
                'default' => null,
                'limit' => 162,
                'null' => true,
            ])
            ->addColumn('roster_number', 'string', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('roster_position', 'string', [
                'default' => null,
                'limit' => 162,
                'null' => true,
            ])
            ->addColumn('roster_height', 'string', [
                'default' => null,
                'limit' => 162,
                'null' => true,
            ])
            ->addColumn('roster_weight', 'string', [
                'default' => null,
                'limit' => 162,
                'null' => true,
            ])
            ->addColumn('created_at', $driver === 'sqlite' ? 'text' : 'timestamp', [
                'default' => $driver === 'sqlite' ? 'CURRENT_TIMESTAMP' : null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('updated_at', $driver === 'sqlite' ? 'text' : 'timestamp', [
                'default' => $driver === 'sqlite' ? 'CURRENT_TIMESTAMP' : null,
                'limit' => null,
                'null' => true,
            ])
            ->create();

        $this->table('team_season_roster_awards')
            ->addColumn('id', 'integer', [
                'autoIncrement' => true,
                'default' => null,
                'limit' => null,
                'null' => false,
                'signed' => false,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('team_season_roster_id', 'integer', [
                'default' => null,
                'limit' => null,
                'null' => false,
                'signed' => false,
            ])
            ->addColumn('award_type', 'string', [
                'default' => null,
                'limit' => 7,
                'null' => false,
            ])
            ->addColumn('award_category', 'string', [
                'default' => null,
                'limit' => 120,
                'null' => false,
            ])
            ->addColumn('award_name', 'string', [
                'default' => null,
                'limit' => 120,
                'null' => false,
            ])
            ->create();

        $this->table('teams')
            ->addColumn('id', 'integer', [
                'autoIncrement' => true,
                'default' => null,
                'limit' => null,
                'null' => false,
                'signed' => false,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('sport_id', 'integer', [
                'default' => null,
                'limit' => null,
                'null' => false,
                'signed' => false,
            ])
            ->addColumn('team_name', 'string', [
                'default' => null,
                'limit' => 162,
                'null' => true,
            ])
            ->addColumn('team_description', 'string', [
                'default' => null,
                'limit' => 240,
                'null' => true,
            ])
            ->addColumn('abbr', 'string', [
                'default' => null,
                'limit' => 5,
                'null' => false,
            ])
            ->addColumn('gender', 'string', [
                'default' => null,
                'limit' => 1,
                'null' => false,
            ])
            ->addColumn('created_at', $driver === 'sqlite' ? 'text' : 'timestamp', [
                'default' => $driver === 'sqlite' ? 'CURRENT_TIMESTAMP' : null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('updated_at', $driver === 'sqlite' ? 'text' : 'timestamp', [
                'default' => $driver === 'sqlite' ? 'CURRENT_TIMESTAMP' : null,
                'limit' => null,
                'null' => true,
            ])
            ->create();

        $this->table('users')
            ->addColumn('id', 'integer', [
                'autoIncrement' => true,
                'default' => null,
                'limit' => null,
                'null' => false,
                'signed' => true,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('username', 'string', [
                'default' => null,
                'limit' => 100,
                'null' => false,
            ])
            ->addColumn('password', 'string', [
                'default' => null,
                'limit' => 255,
                'null' => false,
            ])
            ->addColumn('email', 'string', [
                'default' => null,
                'limit' => 255,
                'null' => false,
            ])
            ->addColumn('role', 'string', [
                'default' => null,
                'limit' => 20,
                'null' => false,
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addColumn('status', 'string', [
                'default' => null,
                'limit' => 255,
                'null' => false,
            ])
            ->create();
    }

    /**
     * Down Method.
     *
     * More information on this method is available here:
     * https://book.cakephp.org/phinx/0/en/migrations.html#the-down-method
     * @return void
     */
    public function down(): void
    {
        $this->table('game_eav')->drop()->save();
        $this->table('game_types')->drop()->save();
        $this->table('games')->drop()->save();
        $this->table('opponents')->drop()->save();
        $this->table('persons')->drop()->save();
        $this->table('places')->drop()->save();
        $this->table('seasons')->drop()->save();
        $this->table('sites')->drop()->save();
        $this->table('sports')->drop()->save();
        $this->table('stat_basket_game_box')->drop()->save();
        $this->table('stat_basket_game_opponent')->drop()->save();
        $this->table('stat_basket_game_person')->drop()->save();
        $this->table('stat_basket_game_team')->drop()->save();
        $this->table('stat_basket_season_opponent')->drop()->save();
        $this->table('stat_basket_season_person')->drop()->save();
        $this->table('stat_basket_season_team')->drop()->save();
        $this->table('stats')->drop()->save();
        $this->table('team_season')->drop()->save();
        $this->table('team_season_roster')->drop()->save();
        $this->table('team_season_roster_awards')->drop()->save();
        $this->table('teams')->drop()->save();
        $this->table('users')->drop()->save();
    }
}