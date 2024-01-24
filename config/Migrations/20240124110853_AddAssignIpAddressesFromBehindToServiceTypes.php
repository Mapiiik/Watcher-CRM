<?php
declare(strict_types=1);

use Migrations\AbstractMigration;

class AddAssignIpAddressesFromBehindToServiceTypes extends AbstractMigration
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
        $table = $this->table('service_types');
        $table->addColumn('assign_ip_addresses_from_behind', 'boolean', [
            'default' => false,
            'null' => false,
        ]);
        $table->update();
    }
}
