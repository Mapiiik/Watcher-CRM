<?php
declare(strict_types=1);

use Migrations\BaseMigration;

/**
 * Whether a customer is ours to push into the accounting system.
 *
 * The accounting system keeps a partner card for the operator itself, built from the company's own
 * data, and an import that names the same company finds two matching partners and refuses to touch
 * either. There is nothing to fix in the customer record - the card was never ours to write - so the
 * only answer is to leave that one out and let whoever keeps the company's own data keep it.
 *
 * Everyone is sent as before; only a customer somebody unticks stays behind, and keeping their
 * partner card right in the accounting system is then a manual job. Invoices are a separate
 * question and are unaffected.
 */
class AddSyncToAccountingToCustomers extends BaseMigration
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
        $table->addColumn('sync_to_accounting', 'boolean', [
            'default' => true,
            'null' => false,
            'after' => 'accounting_profile_id',
        ]);
        $table->update();
    }
}
