<?php
declare(strict_types=1);

use Migrations\BaseMigration;
use Migrations\Db\Literal;

/**
 * What documents are made of: the bytes, once.
 *
 * A row here is content rather than a document. It is addressed by the hash of what is in it,
 * which is also what its path is derived from, so the same bytes arriving twice - the same paper
 * filed against two records, the same scan uploaded again - are stored once and pointed at twice.
 *
 * Who has it and why is {@see file_links}. Nothing here knows what the content is for.
 */
class CreateFiles extends BaseMigration
{
    /**
     * Up Method.
     *
     * @return void
     */
    public function up(): void
    {
        // create extension for full UUID support
        $this->execute('CREATE EXTENSION IF NOT EXISTS "uuid-ossp";');

        $this->table('files', ['id' => false, 'primary_key' => ['id']])
            ->addColumn('id', 'uuid', [
                'default' => Literal::from('uuid_generate_v4()'),
                'limit' => null,
                'null' => false,
            ])
            // What is in the content, written out, and what worked it out. The algorithm is a
            // column rather than the name of one, so that changing it one day is a migration of
            // the rows instead of a migration of the schema - and so that both can stand side by
            // side while that happens, which is what the two of them are unique over.
            ->addColumn('hash', 'string', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addColumn('hash_type', 'string', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addColumn('byte_size', 'biginteger', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addColumn('mime_type', 'string', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            // Derived from the hash rather than stored for its own sake, but written down all the
            // same: how it is derived may change, and what is already stored may not.
            ->addColumn('path', 'string', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addColumn('created', 'timestamp', [
                'timezone' => true,
                'default' => null,
                'null' => true,
            ])
            ->addColumn('created_by', 'uuid', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            // A row here never changes - the content it stands for cannot. The pair is carried
            // anyway because the footprint behaviour writes neither unless it finds both.
            ->addColumn('modified', 'timestamp', [
                'timezone' => true,
                'default' => null,
                'null' => true,
            ])
            ->addColumn('modified_by', 'uuid', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addIndex(['hash_type', 'hash'], ['unique' => true])
            ->addForeignKey('created_by', 'users', 'id')
            ->addForeignKey('modified_by', 'users', 'id')
            ->create();
    }

    /**
     * Down Method.
     *
     * Written out rather than left to `change()`, which cannot take back the plain SQL above.
     *
     * @return void
     */
    public function down(): void
    {
        $this->table('files')->drop()->save();
    }
}
