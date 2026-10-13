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
class InitialBookkeepingSchema extends BaseMigration
{
    /**
     * The last migration of this plugin in 4.x.
     *
     * @var int
     */
    private const LAST_OF_4X = 20260808193000;

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
            "SELECT 1 FROM cake_migrations WHERE plugin = 'Bookkeeping' AND version = %d",
            self::LAST_OF_4X,
        ));
        if ($last !== false) {
            $this->execute(sprintf(
                "DELETE FROM cake_migrations WHERE plugin = 'Bookkeeping' AND version < %d",
                $this->getVersion(),
            ));

            return;
        }

        if ($this->hasTable('invoices')) {
            throw new RuntimeException(
                'The database has not been through the last migration of the Bookkeeping plugin in 4.x.'
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

        $this->table('invoices', ['id' => false, 'primary_key' => ['id']])
            ->addColumn('id', 'uuid', ['default' => Literal::from('uuid_generate_v4()'), 'null' => false])
            ->addColumn('number', 'string', ['null' => false])
            ->addColumn('variable_symbol', 'string', ['null' => true])
            ->addColumn('creation_date', 'date', ['null' => false])
            ->addColumn('due_date', 'date', ['null' => false])
            ->addColumn('text', 'text', ['null' => true])
            ->addColumn('total', 'decimal', ['precision' => 10, 'scale' => 2, 'null' => false])
            ->addColumn('debt', 'decimal', ['precision' => 10, 'scale' => 2, 'null' => false])
            ->addColumn('payment_date', 'date', ['null' => true])
            ->addColumn('send_by_email', 'boolean', ['default' => false, 'null' => false])
            ->addColumn('email_sent', 'timestamptimezone', ['null' => true])
            ->addColumn('created', 'timestamptimezone', ['null' => true])
            ->addColumn('modified', 'timestamptimezone', ['null' => true])
            ->addColumn('customer_id', 'uuid', ['null' => true])
            ->addColumn('created_by', 'uuid', ['null' => true])
            ->addColumn('modified_by', 'uuid', ['null' => true])
            ->addColumn('accounting_identifier', 'string', ['null' => true])
            ->addIndex(['accounting_identifier'], ['name' => 'invoices_accounting_identifier', 'unique' => true])
            ->addIndex(['number'], ['name' => 'invoices_number'])
            ->addIndex(['customer_id', 'due_date'], ['name' => 'invoices_customer_id_due_date'])
            ->addIndex(['customer_id'], ['name' => 'invoices_customer_id'])
            ->create();
        // computed by the database, which the table builder cannot ask for
        $this->execute(
            'ALTER TABLE invoices ADD COLUMN creation_year smallint'
            . ' GENERATED ALWAYS AS (EXTRACT(YEAR FROM creation_date)::smallint) STORED',
        );
        $this->table('invoices')
            ->addIndex(['number', 'creation_year'], ['name' => 'invoices_number_creation_year', 'unique' => true])
            ->update();

        $this->table('invoices')
            ->addForeignKey('created_by', 'users', 'id', ['constraint' => 'invoices_created_by_fkey'])
            ->addForeignKey('customer_id', 'customers', 'id', ['constraint' => 'invoices_customer_id_fkey'])
            ->addForeignKey('modified_by', 'users', 'id', ['constraint' => 'invoices_modified_by_fkey'])
            ->update();
    }
}
