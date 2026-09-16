<?php
declare(strict_types=1);

use Migrations\BaseMigration;

class AddMinimumConnectionPriceToContracts extends BaseMigration
{
    /**
     * Change Method.
     *
     * The price below which the connection on this contract is not lowered. A customer has no
     * claim to a cheaper tariff under a contract already agreed, so the line is the office's own
     * and is not printed anywhere.
     *
     * More information on this method is available here:
     * https://book.cakephp.org/migrations/4/en/migrations.html#the-change-method
     *
     * @return void
     */
    public function change(): void
    {
        $this->table('contracts')
            ->addColumn('minimum_connection_price', 'decimal', [
                'default' => null,
                'null' => true,
                'precision' => 10,
                'scale' => 2,
                'comment' => 'The monthly price the connection may not be lowered below; empty for no limit',
            ])
            ->update();
    }
}
