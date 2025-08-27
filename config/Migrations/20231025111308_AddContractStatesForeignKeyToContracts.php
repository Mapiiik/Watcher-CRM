<?php
declare(strict_types=1);

use Migrations\BaseMigration;

class AddContractStatesForeignKeyToContracts extends BaseMigration
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
        $table = $this->table('contracts');

        $table->addForeignKey('contract_state_id', 'contract_states', 'id', [
            'delete' => 'NO_ACTION',
            'update' => 'NO_ACTION',
        ]);

        $table->update();
    }
}
