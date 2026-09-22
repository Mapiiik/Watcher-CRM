<?php
declare(strict_types=1);

use Migrations\BaseMigration;
use Migrations\Db\Literal;

class CreateAvailableConnections extends BaseMigration
{
    /**
     * Change Method.
     *
     * The address points a customer could be connected at, whether or not anybody is. The
     * regulators want them reported beside the active ones, and they outlive the contracts that
     * put a connection there, so the address is kept here rather than reached through a contract.
     *
     * @return void
     */
    public function change(): void
    {
        $table = $this->table('available_connections', [
            'id' => false,
            'primary_key' => ['id'],
        ]);

        $table->addColumn('id', 'uuid', [
            'default' => Literal::from('uuid_generate_v4()'),
            'null' => false,
        ]);

        $table->addColumn('address_registry_source', 'string', [
            'limit' => 2,
            'null' => false,
            'comment' => 'Which national address registry, as on addresses',
        ]);

        $table->addColumn('address_registry_reference', 'string', [
            'null' => false,
            'comment' => 'The address point in that registry',
        ]);

        $table->addColumn('address_label', 'string', [
            'default' => null,
            'null' => true,
            'comment' => 'The address as the registry wrote it when the point was recorded',
        ]);

        $table->addColumn('gps_x', 'float', [
            'default' => null,
            'null' => true,
        ]);

        $table->addColumn('gps_y', 'float', [
            'default' => null,
            'null' => true,
        ]);

        $table->addColumn('access_technology', 'string', [
            'limit' => 32,
            'null' => false,
            'comment' => 'As App\Model\Enum\AccessTechnology',
        ]);

        $table->addColumn('speed_down_max', 'biginteger', [
            'null' => false,
            'comment' => 'The fastest download the connection can carry, in kbps',
        ]);

        $table->addColumn('speed_up_max', 'biginteger', [
            'null' => false,
            'comment' => 'The fastest upload the connection can carry, in kbps',
        ]);

        $table->addColumn('access_point_id', 'uuid', [
            'default' => null,
            'null' => true,
            'comment' => 'The access point in NMS it is served from',
        ]);

        $table->addColumn('origin', 'string', [
            'limit' => 16,
            'default' => 'manual',
            'null' => false,
            'comment' => 'As App\Model\Enum\AvailableConnectionOrigin',
        ]);

        $table->addColumn('contract_id', 'uuid', [
            'default' => null,
            'null' => true,
            'comment' => 'The contract that put the connection there, while it exists',
        ]);

        $table->addColumn('retired', 'date', [
            'default' => null,
            'null' => true,
            'comment' => 'Since when the connection is no longer there to be had',
        ]);

        $table->addColumn('note', 'text', [
            'default' => null,
            'null' => true,
        ]);

        $table->addColumn('created', 'timestamp', [
            'timezone' => true,
            'null' => true,
        ]);

        $table->addColumn('created_by', 'uuid', [
            'null' => true,
        ]);

        $table->addColumn('modified', 'timestamp', [
            'timezone' => true,
            'null' => true,
        ]);

        $table->addColumn('modified_by', 'uuid', [
            'null' => true,
        ]);

        // One record of a technology at an address point. The synchronisation relies on it.
        $table->addIndex(['address_registry_source', 'address_registry_reference', 'access_technology'], [
            'unique' => true,
            'name' => 'available_connections_point_technology',
        ]);
        $table->addIndex(['contract_id']);

        // A contract erased for privacy leaves the connection behind, only without it.
        $table->addForeignKey('contract_id', 'contracts', 'id', ['delete' => 'SET_NULL']);
        $table->addForeignKey('created_by', 'users', 'id');
        $table->addForeignKey('modified_by', 'users', 'id');

        $table->create();
    }
}
