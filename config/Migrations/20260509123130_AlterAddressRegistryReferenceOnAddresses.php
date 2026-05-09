<?php
declare(strict_types=1);

use Migrations\BaseMigration;

class AlterAddressRegistryReferenceOnAddresses extends BaseMigration
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
        $table->changeColumn('address_registry_reference', 'string', [
            'default' => null,
            'limit' => null,
            'null' => true,
        ]);
        $table->update();
    }
}
