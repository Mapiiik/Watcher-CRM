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
class InitialFilesSchema extends BaseMigration
{
    /**
     * The last migration of this plugin in 4.x.
     *
     * @var int
     */
    private const LAST_OF_4X = 20260910090100;

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
            "SELECT 1 FROM cake_migrations WHERE plugin = 'Files' AND version = %d",
            self::LAST_OF_4X,
        ));
        if ($last !== false) {
            $this->execute(sprintf(
                "DELETE FROM cake_migrations WHERE plugin = 'Files' AND version < %d",
                $this->getVersion(),
            ));

            return;
        }

        if ($this->hasTable('files')) {
            throw new RuntimeException(
                'The database has not been through the last migration of the Files plugin in 4.x.'
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

        $this->table('file_links', ['id' => false, 'primary_key' => ['id']])
            ->addColumn('id', 'uuid', ['default' => Literal::from('uuid_generate_v4()'), 'null' => false])
            ->addColumn('file_id', 'uuid', ['null' => false])
            ->addColumn('model', 'string', ['null' => false])
            ->addColumn('foreign_key', 'uuid', ['null' => false])
            ->addColumn('document_type', 'string', ['null' => false])
            ->addColumn('variant', 'string', ['null' => false])
            ->addColumn('position', 'integer', ['default' => 0, 'null' => false])
            ->addColumn('name', 'string', ['null' => true])
            ->addColumn('meta', 'json', ['default' => '{}', 'null' => false])
            ->addColumn('created', 'timestamptimezone', ['null' => true])
            ->addColumn('created_by', 'uuid', ['null' => true])
            ->addColumn('modified', 'timestamptimezone', ['null' => true])
            ->addColumn('modified_by', 'uuid', ['null' => true])
            ->addIndex(['model', 'foreign_key', 'document_type', 'variant'], [
                'name' => 'file_links_generated',
                'unique' => true,
                'where' => "variant IN ('generated', 'generated-signed-by-us')",
            ])
            ->addIndex(['file_id'], ['name' => 'file_links_file_id'])
            ->addIndex(['model', 'foreign_key', 'document_type', 'variant', 'position'], [
                'name' => 'file_links_model_foreign_key_document_type_variant_position',
            ])
            ->addIndex(['model', 'foreign_key'], ['name' => 'file_links_model_foreign_key'])
            ->create();

        $this->table('files', ['id' => false, 'primary_key' => ['id']])
            ->addColumn('id', 'uuid', ['default' => Literal::from('uuid_generate_v4()'), 'null' => false])
            ->addColumn('hash', 'string', ['null' => false])
            ->addColumn('hash_type', 'string', ['null' => false])
            ->addColumn('byte_size', 'biginteger', ['null' => false])
            ->addColumn('mime_type', 'string', ['null' => false])
            ->addColumn('path', 'string', ['null' => false])
            ->addColumn('created', 'timestamptimezone', ['null' => true])
            ->addColumn('created_by', 'uuid', ['null' => true])
            ->addColumn('modified', 'timestamptimezone', ['null' => true])
            ->addColumn('modified_by', 'uuid', ['null' => true])
            ->addIndex(['hash_type', 'hash'], ['name' => 'files_hash_type_hash', 'unique' => true])
            ->create();

        $this->table('file_links')
            ->addForeignKey('created_by', 'users', 'id', ['constraint' => 'file_links_created_by_fkey'])
            ->addForeignKey('file_id', 'files', 'id', ['constraint' => 'file_links_file_id_fkey'])
            ->addForeignKey('modified_by', 'users', 'id', ['constraint' => 'file_links_modified_by_fkey'])
            ->update();

        $this->table('files')
            ->addForeignKey('created_by', 'users', 'id', ['constraint' => 'files_created_by_fkey'])
            ->addForeignKey('modified_by', 'users', 'id', ['constraint' => 'files_modified_by_fkey'])
            ->update();
    }
}
