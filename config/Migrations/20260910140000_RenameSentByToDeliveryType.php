<?php
declare(strict_types=1);

use Migrations\BaseMigration;

class RenameSentByToDeliveryType extends BaseMigration
{
    /**
     * Change Method.
     *
     * Every other column ending in `_by` holds a user: who applied it, who revoked it, who wrote
     * it. This one held a number standing for the post or an e-mail, so it read as the one thing
     * it was not. What it says is how the papers went out, and that is what it is called now -
     * the same word the enum behind it uses.
     *
     * More information on this method is available here:
     * https://book.cakephp.org/migrations/4/en/migrations.html#the-change-method
     *
     * @return void
     */
    public function change(): void
    {
        $this->table('contract_proposals')
            ->renameColumn('sent_by', 'delivery_type')
            ->update();
    }
}
