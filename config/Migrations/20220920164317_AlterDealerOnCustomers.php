<?php
declare(strict_types=1);

use Migrations\AbstractMigration;
use Phinx\Util\Literal;

class AlterDealerOnCustomers extends AbstractMigration
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
        $table = $this->table('customers');
        $table->changeColumn('dealer', 'boolean', [
            'default' => null,
            'limit' => null,
            'null' => false,
        ]);
        $table->changeColumn('dealer', Literal::from('SMALLINT USING (CASE WHEN dealer THEN 1 ELSE 0 END)'), [
            'default' => 0,
            'limit' => 6,
            'null' => false,
        ]);
        $table->update();
    }
}
