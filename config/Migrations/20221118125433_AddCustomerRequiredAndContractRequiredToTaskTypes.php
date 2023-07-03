<?php
declare(strict_types=1);

use Migrations\AbstractMigration;

class AddCustomerRequiredAndContractRequiredToTaskTypes extends AbstractMigration
{
    /**
     * Change Method.
     *
     * More information on this method is available here:
     * https://book.cakephp.org/phinx/0/en/migrations.html#the-change-method
     *
     * @return void
     */
    public function change(): void
    {
        $table = $this->table('task_types');
        $table->addColumn('customer_required', 'boolean', [
            'default' => false,
            'null' => false,
        ]);
        $table->addColumn('contract_required', 'boolean', [
            'default' => false,
            'null' => false,
        ]);
        $table->update();
    }
}
