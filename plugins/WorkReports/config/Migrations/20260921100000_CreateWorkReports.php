<?php
declare(strict_types=1);

use Migrations\BaseMigration;
use Migrations\Db\Literal;
use Migrations\Db\Table;

/**
 * Work reports: a month of one worker, its items, and what the items are described by.
 *
 * An item is both a line of the timesheet and a record of work done at a customer, so the link to
 * the customer, the contract and the billing live on the item itself.
 */
class CreateWorkReports extends BaseMigration
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

        $this->footprinted($this->uuidTable('work_report_item_types'))
            ->addColumn('name', 'string', ['null' => false])
            ->addColumn('description_required', 'boolean', ['default' => false, 'null' => false])
            ->addColumn('time_mode', 'string', ['default' => 'range', 'null' => false])
            ->addColumn('counts_as_worked', 'boolean', ['default' => false, 'null' => false])
            ->addColumn('reduces_fund', 'boolean', ['default' => false, 'null' => false])
            ->addColumn('active', 'boolean', ['default' => true, 'null' => false])
            ->addColumn('position', 'integer', ['default' => 0, 'null' => false])
            ->create();

        $this->footprinted($this->uuidTable('work_rates'))
            ->addColumn('code', 'string', ['null' => false])
            ->addColumn('name', 'string', ['null' => true])
            ->addColumn('price', 'decimal', ['precision' => 10, 'scale' => 2, 'null' => true])
            ->addColumn('accounting_product_code', 'string', ['null' => true])
            ->addColumn('active', 'boolean', ['default' => true, 'null' => false])
            ->addIndex(['code'], ['unique' => true])
            ->create();

        $this->footprinted($this->uuidTable('work_labels'))
            ->addColumn('name', 'string', ['null' => false])
            ->addColumn('caption', 'string', ['null' => true])
            ->addColumn('color', 'string', ['limit' => 7, 'default' => '#cccccc', 'null' => false])
            ->addColumn('active', 'boolean', ['default' => true, 'null' => false])
            ->create();

        // a car without an owner is a company car, one with an owner is that person's own
        $this->footprinted($this->uuidTable('work_cars'))
            ->addColumn('name', 'string', ['null' => false])
            ->addColumn('license_plate', 'string', ['null' => true])
            ->addColumn('owner_id', 'uuid', ['null' => true])
            ->addColumn('active', 'boolean', ['default' => true, 'null' => false])
            ->addColumn('note', 'text', ['null' => true])
            ->addForeignKey('owner_id', 'users', 'id')
            ->create();

        $this->footprinted($this->uuidTable('work_report_workers'))
            ->addColumn('user_id', 'uuid', ['null' => false])
            ->addColumn('workload', 'decimal', ['precision' => 4, 'scale' => 3, 'default' => 1, 'null' => false])
            ->addColumn('default_private_car_id', 'uuid', ['null' => true])
            ->addColumn('default_company_car_id', 'uuid', ['null' => true])
            ->addColumn('active', 'boolean', ['default' => true, 'null' => false])
            ->addIndex(['user_id'], ['unique' => true])
            ->addForeignKey('user_id', 'users', 'id')
            ->addForeignKey('default_private_car_id', 'work_cars', 'id')
            ->addForeignKey('default_company_car_id', 'work_cars', 'id')
            ->create();

        // who gets the worker's reports when they are submitted and may see them, and whether
        // they may also change the items and return the report
        $this->footprinted($this->uuidTable('work_report_worker_recipients'))
            ->addColumn('work_report_worker_id', 'uuid', ['null' => false])
            ->addColumn('user_id', 'uuid', ['null' => false])
            ->addColumn('may_edit', 'boolean', ['default' => false, 'null' => false])
            ->addIndex(['work_report_worker_id', 'user_id'], ['unique' => true])
            ->addForeignKey('work_report_worker_id', 'work_report_workers', 'id', ['delete' => 'CASCADE'])
            ->addForeignKey('user_id', 'users', 'id')
            ->create();

        // the workload is written down when the month starts, so that changing it later does not
        // rewrite the months already reported
        $this->footprinted($this->uuidTable('work_reports'))
            ->addColumn('user_id', 'uuid', ['null' => false])
            ->addColumn('month', 'date', ['null' => false])
            ->addColumn('workload', 'decimal', ['precision' => 4, 'scale' => 3, 'default' => 1, 'null' => false])
            ->addColumn('submitted', 'timestamp', ['timezone' => true, 'null' => true])
            ->addColumn('submitted_by', 'uuid', ['null' => true])
            // the last time it was returned for correction, and why
            ->addColumn('returned', 'timestamp', ['timezone' => true, 'null' => true])
            ->addColumn('returned_by', 'uuid', ['null' => true])
            ->addColumn('return_reason', 'text', ['null' => true])
            ->addColumn('note', 'text', ['null' => true])
            ->addIndex(['user_id', 'month'], ['unique' => true])
            ->addForeignKey('user_id', 'users', 'id')
            ->addForeignKey('submitted_by', 'users', 'id')
            ->addForeignKey('returned_by', 'users', 'id')
            ->create();

        $this->footprinted($this->uuidTable('work_report_items'))
            ->addColumn('work_report_id', 'uuid', ['null' => false])
            ->addColumn('work_report_item_type_id', 'uuid', ['null' => false])
            ->addColumn('date', 'date', ['null' => false])
            ->addColumn('whole_day', 'boolean', ['default' => false, 'null' => false])
            ->addColumn('work_from', 'timestamp', ['timezone' => true, 'null' => true])
            ->addColumn('work_until', 'timestamp', ['timezone' => true, 'null' => true])
            ->addColumn('description', 'text', ['null' => true])
            ->addColumn('customer_id', 'uuid', ['null' => true])
            ->addColumn('contract_id', 'uuid', ['null' => true])
            ->addColumn('task_id', 'uuid', ['null' => true])
            // an access point of the NMS, which is why there is no foreign key to it
            ->addColumn('access_point_id', 'uuid', ['null' => true])
            ->addColumn('private_car_id', 'uuid', ['null' => true])
            ->addColumn('private_car_distance', 'integer', ['null' => true])
            ->addColumn('company_car_id', 'uuid', ['null' => true])
            ->addColumn('company_car_distance', 'integer', ['null' => true])
            ->addColumn('cash_collected', 'decimal', ['precision' => 10, 'scale' => 2, 'null' => true])
            ->addColumn('to_invoice', 'boolean', ['default' => false, 'null' => false])
            ->addColumn('invoice_hours', 'decimal', ['precision' => 6, 'scale' => 2, 'null' => true])
            ->addColumn('work_rate_id', 'uuid', ['null' => true])
            ->addColumn('rate_multiplier', 'decimal', ['precision' => 4, 'scale' => 2, 'default' => 1, 'null' => false])
            ->addColumn('invoice_text', 'text', ['null' => true])
            ->addColumn('invoiced', 'boolean', ['default' => false, 'null' => false])
            ->addColumn('note', 'text', ['null' => true])
            ->addIndex(['work_report_id', 'date'])
            ->addIndex(['customer_id'])
            ->addIndex(['contract_id'])
            ->addIndex(['task_id'])
            ->addIndex(['access_point_id'])
            ->addForeignKey('work_report_id', 'work_reports', 'id', ['delete' => 'CASCADE'])
            ->addForeignKey('work_report_item_type_id', 'work_report_item_types', 'id')
            ->addForeignKey('customer_id', 'customers', 'id')
            ->addForeignKey('contract_id', 'contracts', 'id')
            ->addForeignKey('task_id', 'tasks', 'id')
            ->addForeignKey('private_car_id', 'work_cars', 'id')
            ->addForeignKey('company_car_id', 'work_cars', 'id')
            ->addForeignKey('work_rate_id', 'work_rates', 'id')
            ->create();

        $this->footprinted($this->uuidTable('work_report_item_labels'))
            ->addColumn('work_report_item_id', 'uuid', ['null' => false])
            ->addColumn('work_label_id', 'uuid', ['null' => false])
            ->addIndex(['work_report_item_id', 'work_label_id'], ['unique' => true])
            ->addForeignKey('work_report_item_id', 'work_report_items', 'id', ['delete' => 'CASCADE'])
            ->addForeignKey('work_label_id', 'work_labels', 'id')
            ->create();

        $this->footprinted($this->uuidTable('work_report_item_collaborators'))
            ->addColumn('work_report_item_id', 'uuid', ['null' => false])
            ->addColumn('user_id', 'uuid', ['null' => false])
            ->addIndex(['work_report_item_id', 'user_id'], ['unique' => true])
            ->addForeignKey('work_report_item_id', 'work_report_items', 'id', ['delete' => 'CASCADE'])
            ->addForeignKey('user_id', 'users', 'id')
            ->create();

        // the hours are written down from the settings, so that changing them later does not
        // rewrite the months already reported
        $this->footprinted($this->uuidTable('work_report_on_calls'))
            ->addColumn('work_report_id', 'uuid', ['null' => false])
            ->addColumn('date', 'date', ['null' => false])
            ->addColumn('hours', 'decimal', ['precision' => 5, 'scale' => 2, 'null' => false])
            ->addIndex(['work_report_id', 'date'], ['unique' => true])
            ->addForeignKey('work_report_id', 'work_reports', 'id', ['delete' => 'CASCADE'])
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
        foreach (
            [
                'work_report_on_calls',
                'work_report_item_collaborators',
                'work_report_item_labels',
                'work_report_items',
                'work_reports',
                'work_report_worker_recipients',
                'work_report_workers',
                'work_cars',
                'work_labels',
                'work_rates',
                'work_report_item_types',
            ] as $table
        ) {
            $this->table($table)->drop()->save();
        }
    }

    /**
     * A table keyed by a generated uuid.
     *
     * @param string $name Table name.
     * @return \Migrations\Db\Table
     */
    private function uuidTable(string $name): Table
    {
        return $this->table($name, ['id' => false, 'primary_key' => ['id']])
            ->addColumn('id', 'uuid', [
                'default' => Literal::from('uuid_generate_v4()'),
                'null' => false,
            ]);
    }

    /**
     * Adds who and when created and last modified the row.
     *
     * @param \Migrations\Db\Table $table Table to add the columns to.
     * @return \Migrations\Db\Table
     */
    private function footprinted(Table $table): Table
    {
        return $table
            ->addColumn('created', 'timestamp', ['timezone' => true, 'null' => true])
            ->addColumn('created_by', 'uuid', ['null' => true])
            ->addColumn('modified', 'timestamp', ['timezone' => true, 'null' => true])
            ->addColumn('modified_by', 'uuid', ['null' => true])
            ->addForeignKey('created_by', 'users', 'id')
            ->addForeignKey('modified_by', 'users', 'id');
    }
}
