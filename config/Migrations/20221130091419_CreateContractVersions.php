<?php
declare(strict_types=1);

use Migrations\AbstractMigration;
use Phinx\Util\Literal;

class CreateContractVersions extends AbstractMigration
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
        // create extension for full UUID support
        $this->execute('CREATE EXTENSION IF NOT EXISTS "uuid-ossp";');

        $table = $this->table('contract_versions', ['id' => false, 'primary_key' => ['id']]);
        $table->addColumn('id', 'uuid', [
            'default' => Literal::from('uuid_generate_v4()'),
            'null' => false,
        ]);
        $table->addColumn('contract_id', 'integer', [
            'default' => null,
            'limit' => 10,
            'null' => false,
        ]);
        $table->addColumn('valid_from', 'date', [
            'default' => null,
            'null' => false,
        ]);
        $table->addColumn('valid_until', 'date', [
            'default' => null,
            'null' => true,
        ]);
        $table->addColumn('obligation_until', 'date', [
            'default' => null,
            'null' => true,
        ]);
        $table->addColumn('conclusion_date', 'date', [
            'default' => null,
            'null' => true,
        ]);
        $table->addColumn('number_of_amendments', 'integer', [
            'default' => 0,
            'limit' => 10,
            'null' => false,
        ]);
        $table->addColumn('note', 'text', [
            'default' => null,
            'null' => true,
        ]);
        $table->addColumn('created', 'datetime', [
            'default' => null,
            'null' => true,
        ]);
        $table->addColumn('created_by', 'integer', [
            'default' => null,
            'limit' => 10,
            'null' => true,
        ]);
        $table->addColumn('modified', 'datetime', [
            'default' => null,
            'null' => true,
        ]);
        $table->addColumn('modified_by', 'integer', [
            'default' => null,
            'limit' => 10,
            'null' => true,
        ]);
        $table->create();

        // create default contract versions for contracts with filled data
        if ($this->isMigratingUp()) {
            // fetch an array of messages
            $contracts = $this->fetchAll('SELECT * FROM contracts');

            $contract_versions = [];
            foreach ($contracts as $contract) {
                //skip contracts without valid_from date
                if (empty($contract['valid_from'])) {
                    continue 1;
                }

                $contract_versions[] = [
                    'contract_id' => $contract['id'],
                    'valid_from' => $contract['valid_from'],
                    'valid_until' => $contract['valid_until'],
                    'obligation_until' => $contract['obligation_until'],
                    'conclusion_date' => $contract['conclusion_date'],
                    'number_of_amendments' => $contract['number_of_amendments'] ?? 0,
                    'note' => 'automatically migrated',
                ];
            }

            $table->insert($contract_versions)->save();
        }
    }
}
