<?php
declare(strict_types=1);

use Migrations\BaseMigration;
use Migrations\Db\Literal;

class CreateConnectionHistory extends BaseMigration
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
        // create extension for full UUID support
        $this->execute('CREATE EXTENSION IF NOT EXISTS "uuid-ossp";');

        $table = $this->table('connection_history', [
            'id' => false,
            'primary_key' => ['id'],
        ]);

        $table->addColumn('id', 'uuid', [
            'default' => Literal::from('uuid_generate_v4()'),
            'null' => false,
        ]);

        // where the interval was derived from
        $table->addColumn('source', 'string', [
            'limit' => 20,
            'null' => false,
        ]);

        // identity of the account within the source, kept as text so the history
        // survives the account being renamed or deleted in the source system
        $table->addColumn('source_reference', 'string', [
            'limit' => 255,
            'null' => false,
        ]);

        // no foreign key on purpose, RADIUS accounts live in another database
        $table->addColumn('account_id', 'uuid', [
            'null' => true,
        ]);

        $table->addColumn('customer_id', 'uuid', [
            'null' => true,
        ]);

        $table->addColumn('contract_id', 'uuid', [
            'null' => true,
        ]);

        // the tuple that identifies one connection point
        $table->addColumn('station_id', 'string', [
            'limit' => 255,
            'null' => true,
        ]);

        $table->addColumn('called_station_id', 'string', [
            'limit' => 255,
            'null' => true,
        ]);

        $table->addColumn('nas_ip_address', 'string', [
            'limit' => 39,
            'null' => true,
        ]);

        $table->addColumn('nas_port_id', 'string', [
            'limit' => 255,
            'null' => true,
        ]);

        $table->addColumn('ip_address', 'string', [
            'limit' => 39,
            'null' => true,
        ]);

        $table->addColumn('ipv6_prefix', 'string', [
            'limit' => 43,
            'null' => true,
        ]);

        // resolved from the NMS, frozen at the moment the interval was opened
        $table->addColumn('access_point_id', 'uuid', [
            'null' => true,
        ]);

        $table->addColumn('access_point_name', 'string', [
            'limit' => 255,
            'null' => true,
        ]);

        $table->addColumn('routeros_device_id', 'uuid', [
            'null' => true,
        ]);

        $table->addColumn('routeros_device_name', 'string', [
            'limit' => 255,
            'null' => true,
        ]);

        $table->addColumn('first_seen', 'timestamp', [
            'timezone' => true,
            'null' => false,
        ]);

        // how accurate first_seen is, see \App\Model\Enum\FirstSeenSource
        $table->addColumn('first_seen_source', 'string', [
            'limit' => 20,
            'null' => false,
        ]);

        $table->addColumn('last_seen', 'timestamp', [
            'timezone' => true,
            'null' => false,
        ]);

        $table->addColumn('enriched', 'timestamp', [
            'timezone' => true,
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

        // one interval per account may start at a given moment only once
        $table->addIndex(['source', 'source_reference', 'first_seen'], [
            'unique' => true,
            'name' => 'connection_history_interval',
        ]);

        // reading paths: customer card, contract card, RADIUS account monitoring
        $table->addIndex(['customer_id', 'first_seen']);
        $table->addIndex(['contract_id', 'first_seen']);
        $table->addIndex(['account_id', 'first_seen']);
        $table->addIndex(['station_id']);

        $table->addForeignKey('customer_id', 'customers', 'id');
        $table->addForeignKey('contract_id', 'contracts', 'id');
        $table->addForeignKey('created_by', 'users', 'id');
        $table->addForeignKey('modified_by', 'users', 'id');

        $table->create();
    }
}
