<?php
declare(strict_types=1);

use Migrations\BaseMigration;

class RenameLabelCustomersToCustomerLabels extends BaseMigration
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
        $table = $this->table('label_customers');
        $table->rename('customer_labels');
        $table->save();
    }
}
