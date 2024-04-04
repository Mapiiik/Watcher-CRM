<?php
declare(strict_types=1);

use Migrations\AbstractMigration;

class AlterFixedDiscountOnBillings extends AbstractMigration
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
        $table = $this->table('billings');
        $table->changeColumn('fixed_discount', 'decimal', [
            'default' => null,
            'null' => true,
            'precision' => 10,
            'scale' => 2,
        ]);
        $table->update();
    }
}
