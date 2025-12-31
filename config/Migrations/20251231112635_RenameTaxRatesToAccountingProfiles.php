<?php
declare(strict_types=1);

use Migrations\BaseMigration;

class RenameTaxRatesToAccountingProfiles extends BaseMigration
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
        $this->table('tax_rates')
            ->rename('accounting_profiles')
            ->save();

        $this->table('customers')
            ->renameColumn('tax_rate_id', 'accounting_profile_id')
            ->save();
    }
}
