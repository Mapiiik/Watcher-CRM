<?php
declare(strict_types=1);

use Migrations\BaseMigration;
use Migrations\Db\Literal;

/**
 * A folder: what is kept about one thing, in one place.
 *
 * A document is a paper and has variants, because we draw it and have to hand back what was
 * signed. A folder is the other half of the same shelf - photographs from a roof, a protocol, a
 * scan of an inspection, a drawing - and none of it is drawn by us or signed by anybody. What is
 * in one is an ordinary file of the store, filed under the folder.
 *
 * The day may be missing, and that is the difference between the two kinds of folder: one records
 * something that happened and is then left alone, the other is a standing place that is kept up
 * to date. A folder with neither a day nor a name could not be told from the next one of its
 * type, which is what the rules on the table are for.
 *
 * A folder hangs on a customer, on one of their contracts, or on both - a route
 * naming a contract names the customer with it, so what is filed against a connection
 * is found under the customer as well. Which of them a kind of folder has to carry is its type's to say, so every one of
 * them is nullable here and none is required by the schema.
 */
class CreateDocumentations extends BaseMigration
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

        $this->table('documentations', ['id' => false, 'primary_key' => ['id']])
            ->addColumn('id', 'uuid', [
                'default' => Literal::from('uuid_generate_v4()'),
                'limit' => null,
                'null' => false,
            ])
            ->addColumn('customer_id', 'uuid', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('contract_id', 'uuid', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('documentation_type_id', 'uuid', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            // The day it is about, which is not the day it was filed - that one is `created`, and
            // a folder of photographs from March may well be put together in May.
            ->addColumn('happened_on', 'date', [
                'default' => null,
                'null' => true,
            ])
            ->addColumn('name', 'string', [
                'default' => null,
                'limit' => null,
                'null' => true,
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
            // What a card asks for, and what the listing of everything asks for. The standing
            // folders come out first either way, which is where they belong.
            ->addIndex(['customer_id', 'happened_on'])
            ->addIndex(['contract_id', 'happened_on'])
            ->addIndex(['documentation_type_id', 'happened_on'])
            ->addForeignKey('customer_id', 'customers', 'id')
            ->addForeignKey('contract_id', 'contracts', 'id')
            ->addForeignKey('documentation_type_id', 'documentation_types', 'id')
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
        $this->table('documentations')->drop()->save();
    }
}
