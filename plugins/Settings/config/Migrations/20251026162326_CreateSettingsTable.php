<?php
declare(strict_types=1);

use Migrations\BaseMigration;
use Migrations\Db\Literal;

class CreateSettingsTable extends BaseMigration
{
    /**
     * Change Method.
     *
     * More information on this method is available here:
     * https://book.cakephp.org/migrations/4/en/migrations.html#the-change-method
     *
     * @return void
     */
    public function change(): void
    {
        // create extension for full UUID support
        $this->execute('CREATE EXTENSION IF NOT EXISTS "uuid-ossp";');

        // create table "settings"
        $table = $this->table('settings', ['id' => false, 'primary_key' => ['id']]);

        $table->addColumn('id', 'uuid', [
            'default' => Literal::from('uuid_generate_v4()'),
            'null' => false,
        ]);
        $table->addColumn('plugin', 'string', [
            'null' => false,
            'comment' => 'Plugin name, e.g. core, radius',
        ]);
        $table->addColumn('key', 'string', [
            'null' => false,
            'comment' => 'Key inside the plugin, e.g. profile, template_config',
        ]);
        $table->addColumn('value', 'jsonb', [
            'null' => false,
            'comment' => 'JSONB value (array, string, number, etc.)',
        ]);
        $table->addColumn('created', 'timestamp', [
            'timezone' => true,
            'default' => null,
            'null' => true,
        ]);
        $table->addColumn('created_by', 'uuid', [
            'default' => null,
            'null' => true,
        ]);
        $table->addColumn('modified', 'timestamp', [
            'timezone' => true,
            'default' => null,
            'null' => true,
        ]);
        $table->addColumn('modified_by', 'uuid', [
            'default' => null,
            'null' => true,
        ]);

        $table->addIndex(['plugin', 'key'], [
            'unique' => true,
            'name' => 'idx_settings_plugin_key',
        ]);
        $table->addForeignKey('created_by', 'users', 'id');
        $table->addForeignKey('modified_by', 'users', 'id');

        $table->create();
    }
}
