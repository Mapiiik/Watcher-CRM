<?php
declare(strict_types=1);

use Migrations\BaseMigration;

class RenameRuianGidToAddressRegistryReferenceOnAddresses extends BaseMigration
{
    /**
     * Change Method.
     *
     * More information on this method is available here:
     * https://book.cakephp.org/migrations/5/en/migrations.html#the-change-method
     *
     * @return void
     */
    public function change(): void
    {
        $table = $this->table('addresses');
        $table->renameColumn('ruian_gid', 'address_registry_reference');
        $table->save();
    }
}
