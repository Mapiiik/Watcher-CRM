<?php
declare(strict_types=1);

use Migrations\AbstractMigration;

class AddContractIdToCustomerLabels extends AbstractMigration
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
        $table = $this->table('customer_labels');
        $table->addColumn('contract_id', 'uuid', [
            'default' => null,
            'null' => true,
        ]);

        $table->addForeignKey('contract_id', 'contracts', 'id');

        $table->update();
    }
}
