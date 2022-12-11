<?php
declare(strict_types=1);

use Migrations\AbstractMigration;

class AddHaveContractVersionsAndHaveEquipmentsAndHaveIpAddressesAndHaveRadiusAccountsToServiceTypes extends AbstractMigration
{
    /**
     * Change Method.
     *
     * More information on this method is available here:
     * https://book.cakephp.org/phinx/0/en/migrations.html#the-change-method
     *
     * @return void
     */
    public function change()
    {
        $table = $this->table('service_types');
        $table->addColumn('have_contract_versions', 'boolean', [
            'default' => true,
            'null' => false,
        ]);
        $table->addColumn('have_equipments', 'boolean', [
            'default' => true,
            'null' => false,
        ]);
        $table->addColumn('have_ip_addresses', 'boolean', [
            'default' => true,
            'null' => false,
        ]);
        $table->addColumn('have_radius_accounts', 'boolean', [
            'default' => true,
            'null' => false,
        ]);
        $table->update();
    }
}
