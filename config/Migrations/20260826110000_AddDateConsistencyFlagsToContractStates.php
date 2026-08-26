<?php
declare(strict_types=1);

use Migrations\BaseMigration;

class AddDateConsistencyFlagsToContractStates extends BaseMigration
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

        // The end dates the contract carries must agree with how far its records reach
        $table->addColumn('requires_versions_matching_termination', 'boolean', [
            'default' => false,
            'null' => false,
            'comment' => 'The last contract version must end on the termination date',
        ]);

        $table->addColumn('requires_billings_matching_termination', 'boolean', [
            'default' => false,
            'null' => false,
            'comment' => 'The last billing must end on the termination date',
        ]);

        $table->addColumn('requires_equipments_matching_uninstallation', 'boolean', [
            'default' => false,
            'null' => false,
            'comment' => 'The last borrowed equipment must be returned on the uninstallation date',
        ]);

        $table->update();
    }
}
