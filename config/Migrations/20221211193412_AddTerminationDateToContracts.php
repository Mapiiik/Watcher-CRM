<?php
declare(strict_types=1);

use Migrations\AbstractMigration;

class AddTerminationDateToContracts extends AbstractMigration
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
        $table = $this->table('contracts');
        $table->addColumn('termination_date', 'date', [
            'default' => null,
            'null' => true,
        ]);
        $table->update();

        // set termination date where previously set on customer
        if ($this->isMigratingUp()) {
            /** @var \Cake\Database\Query\SelectQuery $selectBuilder */
            $selectBuilder = $this->getQueryBuilder('select');
            $customers = $selectBuilder
                ->select(['id', 'termination_date'])
                ->from('customers')
                ->where(function ($exp) {
                    return $exp
                        ->isNotNull('termination_date');
                })
                ->execute()
                ->fetchAll('assoc');

            foreach ($customers as $customer) {
                /** @var \Cake\Database\Query\UpdateQuery $updateBuilder */
                $updateBuilder = $this->getQueryBuilder('update');
                $updateBuilder
                    ->update('contracts')
                    ->set('termination_date', $customer['termination_date'])
                    ->where(['customer_id' => $customer['id']])
                    ->execute();
            }
        }
    }
}
