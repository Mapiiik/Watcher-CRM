<?php
declare(strict_types=1);

use Migrations\BaseMigration;

class AddCustomerProposalIdToContractProposals extends BaseMigration
{
    /**
     * Up Method.
     *
     * The papers a customer gets go out in one envelope and come back signed in one go, but each
     * side of them was its own round: a consent asked for here, a contract's papers drawn up there,
     * and everything from printing to filing the scans done twice.
     *
     * A round put to the customer already holds exactly what an envelope needs - whose it is, the
     * day it speaks about, when it went out and how, when it came back - so it becomes the envelope
     * and a contract's papers may say they are in it.
     *
     * Nothing is migrated: the column is empty on every proposal there is, which reads as what they
     * were, papers that went out on their own.
     *
     * @return void
     */
    public function up(): void
    {
        $this->table('contract_proposals')
            ->addColumn('customer_proposal_id', 'uuid', [
                'default' => null,
                'null' => true,
                'after' => 'contract_id',
                'comment' => 'The round these papers went out in, empty when they went on their own',
            ])
            ->addIndex(['customer_proposal_id'])
            ->addForeignKey('customer_proposal_id', 'customer_proposals', 'id', [
                'delete' => 'SET_NULL',
                'update' => 'NO_ACTION',
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
        $this->table('contract_proposals')
            ->dropForeignKey('customer_proposal_id')
            ->removeIndex(['customer_proposal_id'])
            ->removeColumn('customer_proposal_id')
            ->save();
    }
}
