<?php
declare(strict_types=1);

use Cake\Datasource\ConnectionManager;
use Migrations\AbstractMigration;
use Phinx\Util\Literal;

class MigratePrimaryKeyToUuidAndAddForeignKeysOnInvoices extends AbstractMigration
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

        $table = $this->table('invoices');

        // UUID primary key for invoices table
        $table->renameColumn('id', 'nid');
        $table->update();

        $table->addColumn('id', 'uuid', [
            'default' => Literal::from('uuid_generate_v4()'),
            'null' => false,
        ]);
        $table->update();

        // reset primary key in case of up migration
        if ($this->isMigratingUp()) {
            $table->changePrimaryKey(['id']);
            $table->update();
        }

        // nullable UUID for related tables
        $table->renameColumn('customer_id', 'customer_nid');
        $table->renameColumn('created_by', 'created_nid');
        $table->renameColumn('modified_by', 'modified_nid');
        $table->update();

        $table->addColumn('customer_id', 'uuid', [
            'default' => null,
            'null' => true,
        ]);
        $table->addColumn('created_by', 'uuid', [
            'default' => null,
            'null' => true,
        ]);
        $table->addColumn('modified_by', 'uuid', [
            'default' => null,
            'null' => true,
        ]);
        $table->update();

        // set new UUID keys for the customers//users
        if ($this->isMigratingUp()) {
            /** @var \Cake\Database\Connection $connection */
            $connection = ConnectionManager::get('default', false);

            $selectBuilder = $this->getSelectBuilder()->setConnection($connection);
            $customers = $selectBuilder
                ->select(['id', 'nid'])
                ->from('customers')
                ->execute()
                ->fetchAll('assoc');

            foreach ($customers as $customer) {
                $updateBuilder = $this->getUpdateBuilder();
                $updateBuilder
                    ->update('invoices')
                    ->set('customer_id', $customer['id'])
                    ->where(['customer_nid' => $customer['nid']])
                    ->execute();
                unset($updateBuilder);
            }
            unset($customers);
            unset($selectBuilder);

            $selectBuilder = $this->getSelectBuilder()->setConnection($connection);
            $users = $selectBuilder
                ->select(['id', 'nid'])
                ->from('users')
                ->execute()
                ->fetchAll('assoc');

            foreach ($users as $user) {
                $updateBuilder = $this->getUpdateBuilder();
                $updateBuilder
                    ->update('invoices')
                    ->set('created_by', $user['id'])
                    ->where(['created_nid' => $user['nid']])
                    ->execute();
                $updateBuilder
                    ->update('invoices')
                    ->set('modified_by', $user['id'])
                    ->where(['modified_nid' => $user['nid']])
                    ->execute();
                unset($updateBuilder);
            }
            unset($users);
            unset($selectBuilder);

            // add foreign keys
            $table->addForeignKey('customer_id', 'customers', 'id', [
                'delete' => 'NO_ACTION',
                'update' => 'NO_ACTION',
            ]);
            $table->addForeignKey('created_by', 'users', 'id', [
                'delete' => 'NO_ACTION',
                'update' => 'NO_ACTION',
            ]);
            $table->addForeignKey('modified_by', 'users', 'id', [
                'delete' => 'NO_ACTION',
                'update' => 'NO_ACTION',
            ]);

            // remove old numeric keys
            $table->removeColumn('nid');
            $table->removeColumn('customer_nid');
            $table->removeColumn('created_nid');
            $table->removeColumn('modified_nid');
            $table->update();
        }
    }
}
