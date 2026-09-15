<?php
declare(strict_types=1);

use Migrations\BaseMigration;

class AlterCustomerProposals extends BaseMigration
{
    /**
     * Up Method.
     *
     * Once a round put to the customer is also the envelope their contracts' papers go out in, it
     * may be for nothing of the customer's own - a new contract needs an envelope whether or not
     * anything is being asked of the customer beside it.
     *
     * An empty purpose says exactly that: the round carries no paper of the customer's own. Every
     * round on file has one and goes on behaving as it did.
     *
     * @return void
     */
    public function up(): void
    {
        $this->table('customer_proposals')
            ->changeColumn('purpose', 'string', [
                'limit' => 20,
                'default' => null,
                'null' => true,
                'comment' => 'What the round is for, as App\Model\Enum\CustomerProposalPurpose,'
                    . ' empty when it only holds its contracts papers',
            ])
            ->save();
    }

    /**
     * Down Method.
     *
     * @return void
     */
    public function down(): void
    {
        $this->execute('DELETE FROM customer_proposals WHERE purpose IS NULL');

        $this->table('customer_proposals')
            ->changeColumn('purpose', 'string', [
                'limit' => 20,
                'null' => false,
                'comment' => 'What the round is for, as App\Model\Enum\CustomerProposalPurpose',
            ])
            ->save();
    }
}
