<?php
declare(strict_types=1);

use Migrations\BaseMigration;
use Migrations\Db\Literal;
use Migrations\Migration\IrreversibleMigrationException;

/**
 * Where 5.x begins: the tables the migrations of this plugin in 4.x built, in one step.
 *
 * An empty database is given all of them. One that 4.x brought to the last migration of this plugin
 * has them already and only forgets the steps that led there. Any other is turned away, as those
 * steps are no longer here to take it the rest of the way.
 */
class InitialSettingsSchema extends BaseMigration
{
    /**
     * The last migration of this plugin in 4.x.
     *
     * @var int
     */
    private const LAST_OF_4X = 20251026162326;

    /**
     * Up Method.
     *
     * @return void
     */
    public function up(): void
    {
        if ($this->hasTable('phinxlog')) {
            throw new RuntimeException(
                'The database keeps the record of its migrations in the phinxlog tables of 4.x.'
                . ' Move it with `bin/cake migrations upgrade --drop-tables`, then migrate again.',
            );
        }

        $last = $this->fetchRow(sprintf(
            "SELECT 1 FROM cake_migrations WHERE plugin = 'Settings' AND version = %d",
            self::LAST_OF_4X,
        ));
        if ($last !== false) {
            $this->execute(sprintf(
                "DELETE FROM cake_migrations WHERE plugin = 'Settings' AND version < %d",
                $this->getVersion(),
            ));

            return;
        }

        if ($this->hasTable('settings')) {
            throw new RuntimeException(
                'The database has not been through the last migration of the Settings plugin in 4.x.'
                . ' Update the application to the last 4.x release and migrate there first.',
            );
        }

        $this->createTables();
    }

    /**
     * Down Method.
     *
     * @return void
     */
    public function down(): void
    {
        throw new IrreversibleMigrationException('What 5.x begins with is not taken apart.');
    }

    /**
     * The tables as 4.x left them.
     *
     * @return void
     */
    private function createTables(): void
    {
        $this->execute('CREATE EXTENSION IF NOT EXISTS "uuid-ossp"');

        $this->table('settings', ['id' => false, 'primary_key' => ['id']])
            ->addColumn('id', 'uuid', ['default' => Literal::from('uuid_generate_v4()'), 'null' => false])
            ->addColumn('plugin', 'string', ['null' => false, 'comment' => 'Plugin name, e.g. core, radius'])
            ->addColumn('key', 'string', [
                'null' => false,
                'comment' => 'Key inside the plugin, e.g. profile, template_config',
            ])
            ->addColumn('value', 'json', ['null' => false, 'comment' => 'JSONB value (array, string, number, etc.)'])
            ->addColumn('created', 'timestamptimezone', ['null' => true])
            ->addColumn('created_by', 'uuid', ['null' => true])
            ->addColumn('modified', 'timestamptimezone', ['null' => true])
            ->addColumn('modified_by', 'uuid', ['null' => true])
            ->addIndex(['plugin', 'key'], ['name' => 'idx_settings_plugin_key', 'unique' => true])
            ->create();

        $this->table('settings')
            ->addForeignKey('created_by', 'users', 'id', ['constraint' => 'settings_created_by_fkey'])
            ->addForeignKey('modified_by', 'users', 'id', ['constraint' => 'settings_modified_by_fkey'])
            ->update();
    }
}
