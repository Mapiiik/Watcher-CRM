<?php
declare(strict_types=1);

use Migrations\BaseMigration;

class AddContractStateIdToContracts extends BaseMigration
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

        if ($contract_state === false) {
            throw new RuntimeException(
                'Default contract state not found.'
                    . ' Please create a contract state with the name "Default" before running this migration.',
            );
        }

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
