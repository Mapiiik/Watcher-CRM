<?php
declare(strict_types=1);

use Migrations\AbstractMigration;

class AddContractStateIdToContracts extends AbstractMigration
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
        // fetch a default contract state
        $contract_state = $this->fetchRow('SELECT id FROM contract_states WHERE name = \'Default\'');

        $table = $this->table('contracts');
        $table->addColumn('contract_state_id', 'uuid', [
            'default' => $contract_state['id'],
            'limit' => null,
            'null' => false,
        ]);
        $table->update();

        if ($this->isMigratingUp()) {
            $table->changeColumn('contract_state_id', 'uuid', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ]);
            $table->update();
        }
    }
}
