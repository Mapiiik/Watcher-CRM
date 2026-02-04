<?php
declare(strict_types=1);

use Migrations\BaseMigration;

class AddObligationsSettledToContractVersions extends BaseMigration
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
        $table = $this->table('contract_versions');
        $table->addColumn('obligations_settled', 'boolean', [
            'default' => false,
            'null' => false,
        ]);
        $table->update();
    }
}
