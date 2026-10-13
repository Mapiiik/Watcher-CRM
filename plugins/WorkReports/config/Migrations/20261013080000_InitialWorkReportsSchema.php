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
class InitialWorkReportsSchema extends BaseMigration
{
    /**
     * The last migration of this plugin in 4.x.
     *
     * @var int
     */
    private const LAST_OF_4X = 20260923100000;

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
            "SELECT 1 FROM cake_migrations WHERE plugin = 'WorkReports' AND version = %d",
            self::LAST_OF_4X,
        ));
        if ($last !== false) {
            $this->execute(sprintf(
                "DELETE FROM cake_migrations WHERE plugin = 'WorkReports' AND version < %d",
                $this->getVersion(),
            ));

            return;
        }

        if ($this->hasTable('work_reports')) {
            throw new RuntimeException(
                'The database has not been through the last migration of the WorkReports plugin in 4.x.'
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

        $this->table('work_cars', ['id' => false, 'primary_key' => ['id']])
            ->addColumn('id', 'uuid', ['default' => Literal::from('uuid_generate_v4()'), 'null' => false])
            ->addColumn('created', 'timestamptimezone', ['null' => true])
            ->addColumn('created_by', 'uuid', ['null' => true])
            ->addColumn('modified', 'timestamptimezone', ['null' => true])
            ->addColumn('modified_by', 'uuid', ['null' => true])
            ->addColumn('name', 'string', ['null' => false])
            ->addColumn('license_plate', 'string', ['null' => true])
            ->addColumn('owner_id', 'uuid', ['null' => true])
            ->addColumn('active', 'boolean', ['default' => true, 'null' => false])
            ->addColumn('note', 'text', ['null' => true])
            ->create();

        $this->table('work_labels', ['id' => false, 'primary_key' => ['id']])
            ->addColumn('id', 'uuid', ['default' => Literal::from('uuid_generate_v4()'), 'null' => false])
            ->addColumn('created', 'timestamptimezone', ['null' => true])
            ->addColumn('created_by', 'uuid', ['null' => true])
            ->addColumn('modified', 'timestamptimezone', ['null' => true])
            ->addColumn('modified_by', 'uuid', ['null' => true])
            ->addColumn('name', 'string', ['null' => false])
            ->addColumn('caption', 'string', ['null' => true])
            ->addColumn('color', 'string', ['default' => '#cccccc', 'limit' => 7, 'null' => false])
            ->addColumn('active', 'boolean', ['default' => true, 'null' => false])
            ->create();

        $this->table('work_rates', ['id' => false, 'primary_key' => ['id']])
            ->addColumn('id', 'uuid', ['default' => Literal::from('uuid_generate_v4()'), 'null' => false])
            ->addColumn('created', 'timestamptimezone', ['null' => true])
            ->addColumn('created_by', 'uuid', ['null' => true])
            ->addColumn('modified', 'timestamptimezone', ['null' => true])
            ->addColumn('modified_by', 'uuid', ['null' => true])
            ->addColumn('code', 'string', ['null' => false])
            ->addColumn('name', 'string', ['null' => true])
            ->addColumn('price', 'decimal', ['precision' => 10, 'scale' => 2, 'null' => true])
            ->addColumn('accounting_product_code', 'string', ['null' => true])
            ->addColumn('active', 'boolean', ['default' => true, 'null' => false])
            ->addIndex(['code'], ['name' => 'work_rates_code', 'unique' => true])
            ->create();

        $this->table('work_report_item_collaborators', ['id' => false, 'primary_key' => ['id']])
            ->addColumn('id', 'uuid', ['default' => Literal::from('uuid_generate_v4()'), 'null' => false])
            ->addColumn('created', 'timestamptimezone', ['null' => true])
            ->addColumn('created_by', 'uuid', ['null' => true])
            ->addColumn('modified', 'timestamptimezone', ['null' => true])
            ->addColumn('modified_by', 'uuid', ['null' => true])
            ->addColumn('work_report_item_id', 'uuid', ['null' => false])
            ->addColumn('user_id', 'uuid', ['null' => false])
            ->addIndex(['work_report_item_id', 'user_id'], [
                'name' => 'work_report_item_collaborators_work_report_item_id_user_id',
                'unique' => true,
            ])
            ->create();

        $this->table('work_report_item_labels', ['id' => false, 'primary_key' => ['id']])
            ->addColumn('id', 'uuid', ['default' => Literal::from('uuid_generate_v4()'), 'null' => false])
            ->addColumn('created', 'timestamptimezone', ['null' => true])
            ->addColumn('created_by', 'uuid', ['null' => true])
            ->addColumn('modified', 'timestamptimezone', ['null' => true])
            ->addColumn('modified_by', 'uuid', ['null' => true])
            ->addColumn('work_report_item_id', 'uuid', ['null' => false])
            ->addColumn('work_label_id', 'uuid', ['null' => false])
            ->addIndex(['work_report_item_id', 'work_label_id'], [
                'name' => 'work_report_item_labels_work_report_item_id_work_label_id',
                'unique' => true,
            ])
            ->create();

        $this->table('work_report_item_types', ['id' => false, 'primary_key' => ['id']])
            ->addColumn('id', 'uuid', ['default' => Literal::from('uuid_generate_v4()'), 'null' => false])
            ->addColumn('created', 'timestamptimezone', ['null' => true])
            ->addColumn('created_by', 'uuid', ['null' => true])
            ->addColumn('modified', 'timestamptimezone', ['null' => true])
            ->addColumn('modified_by', 'uuid', ['null' => true])
            ->addColumn('name', 'string', ['null' => false])
            ->addColumn('description_required', 'boolean', ['default' => false, 'null' => false])
            ->addColumn('time_mode', 'string', ['default' => 'range', 'null' => false])
            ->addColumn('counts_as_worked', 'boolean', ['default' => false, 'null' => false])
            ->addColumn('reduces_fund', 'boolean', ['default' => false, 'null' => false])
            ->addColumn('active', 'boolean', ['default' => true, 'null' => false])
            ->addColumn('position', 'integer', ['default' => 0, 'null' => false])
            ->create();

        $this->table('work_report_items', ['id' => false, 'primary_key' => ['id']])
            ->addColumn('id', 'uuid', ['default' => Literal::from('uuid_generate_v4()'), 'null' => false])
            ->addColumn('created', 'timestamptimezone', ['null' => true])
            ->addColumn('created_by', 'uuid', ['null' => true])
            ->addColumn('modified', 'timestamptimezone', ['null' => true])
            ->addColumn('modified_by', 'uuid', ['null' => true])
            ->addColumn('work_report_id', 'uuid', ['null' => false])
            ->addColumn('work_report_item_type_id', 'uuid', ['null' => false])
            ->addColumn('date', 'date', ['null' => false])
            ->addColumn('whole_day', 'boolean', ['default' => false, 'null' => false])
            ->addColumn('work_from', 'timestamptimezone', ['null' => true])
            ->addColumn('work_until', 'timestamptimezone', ['null' => true])
            ->addColumn('description', 'text', ['null' => true])
            ->addColumn('customer_id', 'uuid', ['null' => true])
            ->addColumn('contract_id', 'uuid', ['null' => true])
            ->addColumn('task_id', 'uuid', ['null' => true])
            ->addColumn('access_point_id', 'uuid', ['null' => true])
            ->addColumn('private_car_id', 'uuid', ['null' => true])
            ->addColumn('private_car_distance', 'integer', ['null' => true])
            ->addColumn('company_car_id', 'uuid', ['null' => true])
            ->addColumn('company_car_distance', 'integer', ['null' => true])
            ->addColumn('cash_collected', 'decimal', ['precision' => 10, 'scale' => 2, 'null' => true])
            ->addColumn('to_invoice', 'boolean', ['default' => false, 'null' => false])
            ->addColumn('invoice_hours', 'decimal', ['precision' => 6, 'scale' => 2, 'null' => true])
            ->addColumn('work_rate_id', 'uuid', ['null' => true])
            ->addColumn('rate_multiplier', 'decimal', ['default' => 1, 'precision' => 4, 'scale' => 2, 'null' => false])
            ->addColumn('invoice_text', 'text', ['null' => true])
            ->addColumn('invoiced', 'boolean', ['default' => false, 'null' => false])
            ->addColumn('note', 'text', ['null' => true])
            ->addIndex(['work_report_id', 'date'], ['name' => 'work_report_items_work_report_id_date'])
            ->addIndex(['customer_id'], ['name' => 'work_report_items_customer_id'])
            ->addIndex(['contract_id'], ['name' => 'work_report_items_contract_id'])
            ->addIndex(['task_id'], ['name' => 'work_report_items_task_id'])
            ->addIndex(['access_point_id'], ['name' => 'work_report_items_access_point_id'])
            ->create();

        $this->table('work_report_on_calls', ['id' => false, 'primary_key' => ['id']])
            ->addColumn('id', 'uuid', ['default' => Literal::from('uuid_generate_v4()'), 'null' => false])
            ->addColumn('created', 'timestamptimezone', ['null' => true])
            ->addColumn('created_by', 'uuid', ['null' => true])
            ->addColumn('modified', 'timestamptimezone', ['null' => true])
            ->addColumn('modified_by', 'uuid', ['null' => true])
            ->addColumn('work_report_id', 'uuid', ['null' => false])
            ->addColumn('date', 'date', ['null' => false])
            ->addColumn('hours', 'decimal', ['precision' => 5, 'scale' => 2, 'null' => false])
            ->addIndex(['work_report_id', 'date'], [
                'name' => 'work_report_on_calls_work_report_id_date',
                'unique' => true,
            ])
            ->create();

        $this->table('work_report_worker_recipients', ['id' => false, 'primary_key' => ['id']])
            ->addColumn('id', 'uuid', ['default' => Literal::from('uuid_generate_v4()'), 'null' => false])
            ->addColumn('created', 'timestamptimezone', ['null' => true])
            ->addColumn('created_by', 'uuid', ['null' => true])
            ->addColumn('modified', 'timestamptimezone', ['null' => true])
            ->addColumn('modified_by', 'uuid', ['null' => true])
            ->addColumn('work_report_worker_id', 'uuid', ['null' => false])
            ->addColumn('user_id', 'uuid', ['null' => false])
            ->addColumn('may_edit', 'boolean', ['default' => false, 'null' => false])
            ->addIndex(['work_report_worker_id', 'user_id'], [
                'name' => 'work_report_worker_recipients_work_report_worker_id_user_id',
                'unique' => true,
            ])
            ->create();

        $this->table('work_report_workers', ['id' => false, 'primary_key' => ['id']])
            ->addColumn('id', 'uuid', ['default' => Literal::from('uuid_generate_v4()'), 'null' => false])
            ->addColumn('created', 'timestamptimezone', ['null' => true])
            ->addColumn('created_by', 'uuid', ['null' => true])
            ->addColumn('modified', 'timestamptimezone', ['null' => true])
            ->addColumn('modified_by', 'uuid', ['null' => true])
            ->addColumn('user_id', 'uuid', ['null' => false])
            ->addColumn('workload', 'decimal', ['default' => 1, 'precision' => 4, 'scale' => 3, 'null' => false])
            ->addColumn('default_private_car_id', 'uuid', ['null' => true])
            ->addColumn('default_company_car_id', 'uuid', ['null' => true])
            ->addColumn('active', 'boolean', ['default' => true, 'null' => false])
            ->addIndex(['user_id'], ['name' => 'work_report_workers_user_id', 'unique' => true])
            ->create();

        $this->table('work_reports', ['id' => false, 'primary_key' => ['id']])
            ->addColumn('id', 'uuid', ['default' => Literal::from('uuid_generate_v4()'), 'null' => false])
            ->addColumn('created', 'timestamptimezone', ['null' => true])
            ->addColumn('created_by', 'uuid', ['null' => true])
            ->addColumn('modified', 'timestamptimezone', ['null' => true])
            ->addColumn('modified_by', 'uuid', ['null' => true])
            ->addColumn('user_id', 'uuid', ['null' => false])
            ->addColumn('month', 'date', ['null' => false])
            ->addColumn('workload', 'decimal', ['default' => 1, 'precision' => 4, 'scale' => 3, 'null' => false])
            ->addColumn('submitted', 'timestamptimezone', ['null' => true])
            ->addColumn('submitted_by', 'uuid', ['null' => true])
            ->addColumn('returned', 'timestamptimezone', ['null' => true])
            ->addColumn('returned_by', 'uuid', ['null' => true])
            ->addColumn('return_reason', 'text', ['null' => true])
            ->addColumn('note', 'text', ['null' => true])
            ->addColumn('closed_until', 'date', ['null' => true])
            ->addIndex(['user_id', 'month'], ['name' => 'work_reports_user_id_month', 'unique' => true])
            ->create();

        $this->table('work_cars')
            ->addForeignKey('created_by', 'users', 'id', ['constraint' => 'work_cars_created_by_fkey'])
            ->addForeignKey('modified_by', 'users', 'id', ['constraint' => 'work_cars_modified_by_fkey'])
            ->addForeignKey('owner_id', 'users', 'id', ['constraint' => 'work_cars_owner_id_fkey'])
            ->update();

        $this->table('work_labels')
            ->addForeignKey('created_by', 'users', 'id', ['constraint' => 'work_labels_created_by_fkey'])
            ->addForeignKey('modified_by', 'users', 'id', ['constraint' => 'work_labels_modified_by_fkey'])
            ->update();

        $this->table('work_rates')
            ->addForeignKey('created_by', 'users', 'id', ['constraint' => 'work_rates_created_by_fkey'])
            ->addForeignKey('modified_by', 'users', 'id', ['constraint' => 'work_rates_modified_by_fkey'])
            ->update();

        $this->table('work_report_item_collaborators')
            ->addForeignKey('created_by', 'users', 'id', [
                'constraint' => 'work_report_item_collaborators_created_by_fkey',
            ])
            ->addForeignKey('modified_by', 'users', 'id', [
                'constraint' => 'work_report_item_collaborators_modified_by_fkey',
            ])
            ->addForeignKey('user_id', 'users', 'id', ['constraint' => 'work_report_item_collaborators_user_id_fkey'])
            ->addForeignKey('work_report_item_id', 'work_report_items', 'id', [
                'delete' => 'CASCADE',
                'constraint' => 'work_report_item_collaborators_work_report_item_id_fkey',
            ])
            ->update();

        $this->table('work_report_item_labels')
            ->addForeignKey('created_by', 'users', 'id', ['constraint' => 'work_report_item_labels_created_by_fkey'])
            ->addForeignKey('modified_by', 'users', 'id', ['constraint' => 'work_report_item_labels_modified_by_fkey'])
            ->addForeignKey('work_label_id', 'work_labels', 'id', [
                'constraint' => 'work_report_item_labels_work_label_id_fkey',
            ])
            ->addForeignKey('work_report_item_id', 'work_report_items', 'id', [
                'delete' => 'CASCADE',
                'constraint' => 'work_report_item_labels_work_report_item_id_fkey',
            ])
            ->update();

        $this->table('work_report_item_types')
            ->addForeignKey('created_by', 'users', 'id', ['constraint' => 'work_report_item_types_created_by_fkey'])
            ->addForeignKey('modified_by', 'users', 'id', ['constraint' => 'work_report_item_types_modified_by_fkey'])
            ->update();

        $this->table('work_report_items')
            ->addForeignKey('company_car_id', 'work_cars', 'id', [
                'constraint' => 'work_report_items_company_car_id_fkey',
            ])
            ->addForeignKey('contract_id', 'contracts', 'id', ['constraint' => 'work_report_items_contract_id_fkey'])
            ->addForeignKey('created_by', 'users', 'id', ['constraint' => 'work_report_items_created_by_fkey'])
            ->addForeignKey('customer_id', 'customers', 'id', ['constraint' => 'work_report_items_customer_id_fkey'])
            ->addForeignKey('modified_by', 'users', 'id', ['constraint' => 'work_report_items_modified_by_fkey'])
            ->addForeignKey('private_car_id', 'work_cars', 'id', [
                'constraint' => 'work_report_items_private_car_id_fkey',
            ])
            ->addForeignKey('task_id', 'tasks', 'id', ['constraint' => 'work_report_items_task_id_fkey'])
            ->addForeignKey('work_rate_id', 'work_rates', 'id', ['constraint' => 'work_report_items_work_rate_id_fkey'])
            ->addForeignKey('work_report_id', 'work_reports', 'id', [
                'delete' => 'CASCADE',
                'constraint' => 'work_report_items_work_report_id_fkey',
            ])
            ->addForeignKey('work_report_item_type_id', 'work_report_item_types', 'id', [
                'constraint' => 'work_report_items_work_report_item_type_id_fkey',
            ])
            ->update();

        $this->table('work_report_on_calls')
            ->addForeignKey('created_by', 'users', 'id', ['constraint' => 'work_report_on_calls_created_by_fkey'])
            ->addForeignKey('modified_by', 'users', 'id', ['constraint' => 'work_report_on_calls_modified_by_fkey'])
            ->addForeignKey('work_report_id', 'work_reports', 'id', [
                'delete' => 'CASCADE',
                'constraint' => 'work_report_on_calls_work_report_id_fkey',
            ])
            ->update();

        $this->table('work_report_worker_recipients')
            ->addForeignKey('created_by', 'users', 'id', [
                'constraint' => 'work_report_worker_recipients_created_by_fkey',
            ])
            ->addForeignKey('modified_by', 'users', 'id', [
                'constraint' => 'work_report_worker_recipients_modified_by_fkey',
            ])
            ->addForeignKey('user_id', 'users', 'id', ['constraint' => 'work_report_worker_recipients_user_id_fkey'])
            ->addForeignKey('work_report_worker_id', 'work_report_workers', 'id', [
                'delete' => 'CASCADE',
                'constraint' => 'work_report_worker_recipients_work_report_worker_id_fkey',
            ])
            ->update();

        $this->table('work_report_workers')
            ->addForeignKey('created_by', 'users', 'id', ['constraint' => 'work_report_workers_created_by_fkey'])
            ->addForeignKey('default_company_car_id', 'work_cars', 'id', [
                'constraint' => 'work_report_workers_default_company_car_id_fkey',
            ])
            ->addForeignKey('default_private_car_id', 'work_cars', 'id', [
                'constraint' => 'work_report_workers_default_private_car_id_fkey',
            ])
            ->addForeignKey('modified_by', 'users', 'id', ['constraint' => 'work_report_workers_modified_by_fkey'])
            ->addForeignKey('user_id', 'users', 'id', ['constraint' => 'work_report_workers_user_id_fkey'])
            ->update();

        $this->table('work_reports')
            ->addForeignKey('created_by', 'users', 'id', ['constraint' => 'work_reports_created_by_fkey'])
            ->addForeignKey('modified_by', 'users', 'id', ['constraint' => 'work_reports_modified_by_fkey'])
            ->addForeignKey('returned_by', 'users', 'id', ['constraint' => 'work_reports_returned_by_fkey'])
            ->addForeignKey('submitted_by', 'users', 'id', ['constraint' => 'work_reports_submitted_by_fkey'])
            ->addForeignKey('user_id', 'users', 'id', ['constraint' => 'work_reports_user_id_fkey'])
            ->update();
    }
}
