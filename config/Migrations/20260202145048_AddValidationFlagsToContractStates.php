<?php
declare(strict_types=1);

use Migrations\BaseMigration;

class AddValidationFlagsToContractStates extends BaseMigration
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
        $table = $this->table('contract_states');

        // For new contracts (for "add" method)
        $table->addColumn('usable_for_new_contract', 'boolean', [
            'default' => true,
            'null' => false,
            'comment' => 'State can be selected when creating a new contract',
        ]);

        // Tasks
        $table->addColumn('requires_open_task_type_id', 'uuid', [
            'null' => true,
            'comment' => 'Requires an open task of given task_type',
        ]);

        $table->addColumn('requires_no_open_tasks', 'boolean', [
            'default' => false,
            'null' => false,
            'comment' => 'No open tasks may exist for the contract',
        ]);

        // Billings
        $table->addColumn('requires_no_active_billings', 'boolean', [
            'default' => false,
            'null' => false,
            'comment' => 'No billing active at current time is allowed',
        ]);

        $table->addColumn('requires_no_future_billings', 'boolean', [
            'default' => false,
            'null' => false,
            'comment' => 'No billing starting in the future is allowed',
        ]);

        // Network
        $table->addColumn('requires_no_assigned_ip_addresses_or_networks', 'boolean', [
            'default' => false,
            'null' => false,
            'comment' => 'All IP addresses and networks must be unassigned',
        ]);

        $table->addColumn('requires_no_active_radius_accounts', 'boolean', [
            'default' => false,
            'null' => false,
            'comment' => 'No active RADIUS accounts may exist',
        ]);

        // Hardware
        $table->addColumn('requires_no_borrowed_equipments', 'boolean', [
            'default' => false,
            'null' => false,
            'comment' => 'No borrowed equipments may be active',
        ]);

        // Consistent dates (contracts.*)
        $table->addColumn('requires_installation_date', 'boolean', [
            'default' => false,
            'null' => false,
            'comment' => 'Installation date must be set',
        ]);

        $table->addColumn('requires_uninstallation_date', 'boolean', [
            'default' => false,
            'null' => false,
            'comment' => 'Uninstallation date must be set',
        ]);

        $table->addColumn('requires_termination_date', 'boolean', [
            'default' => false,
            'null' => false,
            'comment' => 'Termination date must be set',
        ]);

        // Contract versions
        $table->addColumn('requires_contract_version', 'boolean', [
            'default' => false,
            'null' => false,
            'comment' => 'At least one contract version must exist',
        ]);

        $table->addColumn('requires_active_contract_version', 'boolean', [
            'default' => false,
            'null' => false,
            'comment' => 'An active contract version must exist',
        ]);

        $table->addColumn('requires_active_or_future_contract_version', 'boolean', [
            'default' => false,
            'null' => false,
            'comment' => 'An active or future contract version must exist',
        ]);

        $table->addColumn('requires_no_active_or_future_contract_versions', 'boolean', [
            'default' => false,
            'null' => false,
            'comment' => 'No active or future contract versions may exist',
        ]);

        $table->addColumn('requires_no_active_obligations', 'boolean', [
            'default' => false,
            'null' => false,
            'comment' => 'No active contractual obligations may exist',
        ]);

        // Foreign key to task_types
        $table->addForeignKey(
            'requires_open_task_type_id',
            'task_types',
            'id',
        );

        $table->update();
    }
}
