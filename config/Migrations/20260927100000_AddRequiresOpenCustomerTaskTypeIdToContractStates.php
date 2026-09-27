<?php
declare(strict_types=1);

use Migrations\BaseMigration;

class AddRequiresOpenCustomerTaskTypeIdToContractStates extends BaseMigration
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

        $table->addColumn('requires_open_customer_task_type_id', 'uuid', [
            'null' => true,
            'comment' => 'Requires an open task of given task_type on the customer of the contract',
        ]);

        $table->addForeignKey(
            'requires_open_customer_task_type_id',
            'task_types',
            'id',
        );

        $table->update();
    }
}
