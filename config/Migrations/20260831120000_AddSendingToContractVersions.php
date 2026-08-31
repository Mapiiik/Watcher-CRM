<?php
declare(strict_types=1);

use Migrations\BaseMigration;

class AddSendingToContractVersions extends BaseMigration
{
    /**
     * Change Method.
     *
     * When the papers for a version went out to the customer, and how they went. Nothing has
     * held that until now: a version says when it was concluded, but not whether anybody
     * ever put it in front of the customer to conclude.
     *
     * Both columns are nullable, and both mean the same thing when empty - nobody has
     * written it down. That is not the same as "it was never sent", so nothing reads an
     * empty one as an answer.
     *
     * More information on this method is available here:
     * https://book.cakephp.org/migrations/4/en/migrations.html#the-change-method
     *
     * @return void
     */
    public function change(): void
    {
        $table = $this->table('contract_versions');
        $table->addColumn('sent_date', 'date', [
            'default' => null,
            'null' => true,
            'comment' => 'When the papers for this version were sent to the customer',
        ]);
        $table->addColumn('sent_by', 'integer', [
            'default' => null,
            'null' => true,
            'comment' => 'How they were sent, as App\Model\Enum\ContractDeliveryMethod',
        ]);
        $table->update();
    }
}
