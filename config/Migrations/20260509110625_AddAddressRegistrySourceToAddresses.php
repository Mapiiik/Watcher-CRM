<?php
declare(strict_types=1);

use Migrations\BaseMigration;

class AddAddressRegistrySourceToAddresses extends BaseMigration
{
    /**
     * Up Method.
     *
     * More information on this method is available here:
     * https://book.cakephp.org/migrations/5/en/migrations.html#the-up-method
     *
     * @return void
     */
    public function up(): void
    {
        $table = $this->table('addresses');
        $table->addColumn('address_registry_source', 'string', [
            'default' => null,
            'limit' => 2,
            'null' => true,
        ]);
        $table->addIndex(['address_registry_source', 'address_registry_reference']);
        $table->update();

        $updateBuilder = $this->getUpdateBuilder();
        $updateBuilder
            ->update('addresses')
            ->set('address_registry_source', 'cz')
            ->where(['address_registry_reference IS NOT NULL'])
            ->execute();
    }

    /**
     * Down Method.
     *
     * More information on this method is available here:
     * https://book.cakephp.org/migrations/5/en/migrations.html#the-down-method
     *
     * @return void
     */
    public function down(): void
    {
        $table = $this->table('addresses');
        $table->removeIndex(['address_registry_source', 'address_registry_reference']);
        $table->removeColumn('address_registry_source');
        $table->update();
    }
}
