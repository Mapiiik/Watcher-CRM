<?php
declare(strict_types=1);

use Cake\Datasource\ConnectionManager;
use Migrations\AbstractMigration;

class MigrateRelatedKeysToUuidOnAccounts extends AbstractMigration
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
        /*
         * Use manual migration 2001_PreMigrateRelatedKeysToUuidOnAccounts.sql
         * (disabled because of problems with synchronization of slave databases)
         *
        // nullable UUID for related tables
        $table = $this->table('accounts');

        $table->renameColumn('customer_id', 'customer_nid');
        $table->renameColumn('contract_id', 'contract_nid');
        $table->renameColumn('created_by', 'created_nid');
        $table->renameColumn('modified_by', 'modified_nid');
        $table->update();

        $table->addColumn('customer_id', 'uuid', [
            'default' => null,
            'null' => true,
        ]);
        $table->addColumn('contract_id', 'uuid', [
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
        */

        // set new UUID keys for the customers/contracts/users
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
                    ->update('accounts')
                    ->set('customer_id', $customer['id'])
                    ->where(['customer_nid' => $customer['nid']])
                    ->execute();
                unset($updateBuilder);
            }
            unset($customers);
            unset($selectBuilder);

            $selectBuilder = $this->getSelectBuilder()->setConnection($connection);
            $contracts = $selectBuilder
                ->select(['id', 'nid'])
                ->from('contracts')
                ->execute()
                ->fetchAll('assoc');

            foreach ($contracts as $contract) {
                $updateBuilder = $this->getUpdateBuilder();
                $updateBuilder
                    ->update('accounts')
                    ->set('contract_id', $contract['id'])
                    ->where(['contract_nid' => $contract['nid']])
                    ->execute();
                unset($updateBuilder);
            }
            unset($contracts);
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
                    ->update('accounts')
                    ->set('created_by', $user['id'])
                    ->where(['created_nid' => $user['nid']])
                    ->execute();
                $updateBuilder
                    ->update('accounts')
                    ->set('modified_by', $user['id'])
                    ->where(['modified_nid' => $user['nid']])
                    ->execute();
                unset($updateBuilder);
            }
            unset($users);
            unset($selectBuilder);

            /*
             * Use manual migration 2002_PostMigrateRelatedKeysToUuidOnAccounts.sql
             * (disabled because of problems with synchronization of slave databases)
             *
            // set to not nullable
            $table->changeColumn('customer_id', 'uuid', [
                'default' => null,
                'null' => false,
            ]);
            $table->update();
            $table->changeColumn('contract_id', 'uuid', [
                'default' => null,
                'null' => false,
            ]);

            $table->removeColumn('customer_nid');
            $table->removeColumn('contract_nid');
            $table->removeColumn('created_nid');
            $table->removeColumn('modified_nid');

            $table->update();
            */
        }
    }
}
