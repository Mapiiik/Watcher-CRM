<?php
declare(strict_types=1);

use Migrations\BaseMigration;
use Migrations\Db\Literal;

/**
 * What kinds of folder there are.
 *
 * Rows rather than code, because what an installation files is its own business - one keeps
 * folders for installations and complaints, another for the documentation of a network and the
 * lease on a mast.
 *
 * What a type says about the folders under it, the operator says. A folder recording something
 * that happened wants the day it happened on, while one that is simply kept up to date has no day
 * to give, and what a kind of folder has to be filed under is the same sort of question. The
 * `*_required` family is the one the task types already carry, and it is here for the same
 * reason: the vocabulary is the operator's, and the code only asks.
 *
 * Here rather than with the plugin that reads it, because the plugin's migrations run after these
 * - they have to, the shape of `users` is not settled until these are done - and the folders
 * below name records this application knows and the plugin does not.
 */
class CreateDocumentationTypes extends BaseMigration
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

        $this->table('documentation_types', ['id' => false, 'primary_key' => ['id']])
            ->addColumn('id', 'uuid', [
                'default' => Literal::from('uuid_generate_v4()'),
                'limit' => null,
                'null' => false,
            ])
            ->addColumn('name', 'string', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            // Where it sits in the list somebody picks from. The list is short and read often, so
            // the order it reads in is worth saying rather than leaving to the alphabet.
            ->addColumn('position', 'integer', [
                'default' => 0,
                'limit' => null,
                'null' => false,
            ])
            // A type that is no longer offered is not gone: the folders already filed under it go
            // on reading the same, and only new ones stop being able to choose it.
            ->addColumn('currently_offered', 'boolean', [
                'default' => true,
                'null' => false,
            ])
            ->addColumn('date_required', 'boolean', [
                'default' => false,
                'null' => false,
            ])
            ->addColumn('customer_required', 'boolean', [
                'default' => false,
                'null' => false,
            ])
            ->addColumn('contract_required', 'boolean', [
                'default' => false,
                'null' => false,
            ])
            ->addColumn('note', 'text', [
                'default' => null,
                'null' => true,
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
            ->addIndex(['position'])
            ->addForeignKey('created_by', 'users', 'id')
            ->addForeignKey('modified_by', 'users', 'id')
            ->create();
    }

    /**
     * Down Method.
     *
     * @return void
     */
    public function down(): void
    {
        $this->table('documentation_types')->drop()->save();
    }
}
