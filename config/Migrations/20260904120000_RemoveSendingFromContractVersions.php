<?php
declare(strict_types=1);

use Migrations\BaseMigration;

class RemoveSendingFromContractVersions extends BaseMigration
{
    /**
     * Up Method.
     *
     * When the papers went out and how is a fact about the papers, not about the agreement they
     * carry, so it belongs on the proposal they were drawn from. A version may have several of
     * those behind it over the years; the version now reads the latest of them.
     *
     * The columns were added a few days ago and two versions on file have them filled in. They are
     * not carried over, because there is nothing to carry them to - a proposal is drawn up around a
     * snapshot of the contract, and nobody took one at the time. What they said is in the audit log
     * if it turns out to matter.
     *
     * @return void
     */
    public function up(): void
    {
        $table = $this->table('contract_versions');
        $table->removeColumn('sent_date');
        $table->removeColumn('sent_by');
        $table->update();
    }

    /**
     * Down Method.
     *
     * @return void
     */
    public function down(): void
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
