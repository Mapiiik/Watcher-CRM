<?php
declare(strict_types=1);

use Migrations\BaseMigration;

class AddIndexesToContracts extends BaseMigration
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
        $table = $this->table('contracts');

        $table->addIndex(['customer_id']);
        $table->addIndex(['customer_id', 'contract_state_id']);
        $table->addIndex(['customer_id', 'service_type_id']);

        $table->update();
    }
}
