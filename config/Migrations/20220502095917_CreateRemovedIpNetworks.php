<?php
declare(strict_types=1);

use Migrations\AbstractMigration;

class CreateRemovedIpNetworks extends AbstractMigration
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
        $table = $this->table('removed_ip_networks');
        $table->addColumn('customer_id', 'integer', [
            'default' => null,
            'limit' => 11,
            'null' => false,
        ]);
        $table->addColumn('contract_id', 'integer', [
            'default' => null,
            'limit' => 11,
            'null' => false,
        ]);
        $table->addColumn('ip_network', 'cidr', [
            'default' => null,
            'null' => false,
        ]);
        $table->addColumn('type_of_use', 'integer', [
            'default' => 0,
            'limit' => 11,
            'null' => false,
        ]);
        $table->addColumn('note', 'text', [
            'default' => null,
            'limit' => null,
            'null' => true,
        ]);
        $table->addColumn('removed', 'timestamp', [
            'timezone' => true,
            'default' => null,
            'null' => false,
        ]);
        $table->addColumn('removed_by', 'integer', [
            'default' => null,
            'limit' => 11,
            'null' => false,
        ]);
        $table->create();
    }
}
