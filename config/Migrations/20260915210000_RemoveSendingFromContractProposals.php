<?php
declare(strict_types=1);

use Migrations\BaseMigration;

class RemoveSendingFromContractProposals extends BaseMigration
{
    /**
     * Up Method.
     *
     * Papers go out in one envelope and come back in one, and the round put to the customer is
     * that envelope. The day they went, the way they went and the day they came back signed are
     * facts about the envelope, so they are recorded on it - and until now they were also copied
     * onto every set of papers inside it, which is a second copy of one truth.
     *
     * A second copy behaves the way second copies do. A signature recorded on the papers and not
     * on the envelope leaves the two disagreeing, and what reads the papers then answers something
     * the envelope would deny.
     *
     * What the papers keep is the day they take effect, which is their own - it says from when the
     * change happens rather than anything about signing - along with the carrying over and the
     * giving up, both of which are done one contract at a time.
     *
     * The envelope takes anything its papers know and it does not, so nothing is lost on the way
     * out. Where several sets disagree it takes the latest, which is what a round of papers that
     * went out more than once amounts to.
     *
     * @return void
     */
    public function up(): void
    {
        $this->execute(
            'UPDATE customer_proposals r'
            . ' SET sent_date = COALESCE(r.sent_date, p.sent_date),'
            . ' delivery_type = COALESCE(r.delivery_type, p.delivery_type),'
            . ' conclusion_date = COALESCE(r.conclusion_date, p.conclusion_date)'
            . ' FROM ('
            . ' SELECT customer_proposal_id,'
            . ' MAX(sent_date) AS sent_date,'
            . ' MAX(delivery_type) AS delivery_type,'
            . ' MAX(conclusion_date) AS conclusion_date'
            . ' FROM contract_proposals'
            . ' WHERE customer_proposal_id IS NOT NULL'
            . ' GROUP BY customer_proposal_id'
            . ' ) p'
            . ' WHERE p.customer_proposal_id = r.id',
        );

        // A paper filed against a proposal that has since been deleted cannot be reached from
        // anywhere, and the link keeps its file alive, so neither the page nor the housekeeping
        // will ever meet it again. Nothing stops this happening yet - a rule does from here on.
        //
        // Asked for rather than assumed: the papers belong to a plugin, whose own migrations run
        // after these on a database being built from nothing.
        if ($this->hasTable('file_links')) {
            $this->execute(
                'DELETE FROM file_links l'
                . " WHERE l.model = 'ContractProposals'"
                . ' AND NOT EXISTS (SELECT 1 FROM contract_proposals p WHERE p.id = l.foreign_key)',
            );
            $this->execute(
                'DELETE FROM file_links l'
                . " WHERE l.model = 'CustomerProposals'"
                . ' AND NOT EXISTS (SELECT 1 FROM customer_proposals r WHERE r.id = l.foreign_key)',
            );
        }

        // Every set of papers lies in an envelope, which is what makes the envelope the one place
        // the sending is written. Without one there would be nobody to ask.
        $this->table('contract_proposals')
            ->changeColumn('customer_proposal_id', 'uuid', [
                'default' => null,
                'null' => false,
                'comment' => 'The round these papers go out in',
            ])
            ->save();

        // Letting go of an envelope used to leave its papers standing on their own, which is no
        // longer a place they can stand. A rule says so where the operator can read it; this is
        // the same answer underneath, so no other way in can get around it either.
        $this->table('contract_proposals')
            ->dropForeignKey('customer_proposal_id')
            ->addForeignKey('customer_proposal_id', 'customer_proposals', 'id', [
                'delete' => 'RESTRICT',
                'update' => 'NO_ACTION',
            ])
            ->update();

        // What asked for this index was the latest sending of a version, which is now read through
        // the envelope - so the way in is the same and the second column is the one that leads
        // there.
        $this->table('contract_proposals')
            ->removeIndexByName('contract_version_proposals_contract_version_id_sent_date')
            ->addIndex(['contract_version_id', 'customer_proposal_id'])
            ->removeColumn('sent_date')
            ->removeColumn('delivery_type')
            ->removeColumn('conclusion_date')
            ->update();
    }

    /**
     * Down Method.
     *
     * @return void
     */
    public function down(): void
    {
        $this->table('contract_proposals')
            ->addColumn('sent_date', 'date', [
                'default' => null,
                'null' => true,
                'after' => 'confirmations',
                'comment' => 'When the papers were last sent to the customer',
            ])
            ->addColumn('delivery_type', 'integer', [
                'default' => null,
                'null' => true,
                'after' => 'sent_date',
                'comment' => 'How they were sent, as App\Model\Enum\DocumentsDeliveryType',
            ])
            ->addColumn('conclusion_date', 'date', [
                'default' => null,
                'null' => true,
                'after' => 'delivery_type',
                'comment' => 'The day the customer signed or otherwise agreed to this proposal',
            ])
            ->removeIndexByName('contract_proposals_contract_version_id_customer_proposal_id')
            ->addIndex(['contract_version_id', 'sent_date'])
            ->update();

        // Each set of papers takes back what its envelope says of it, which is where the days went.
        $this->execute(
            'UPDATE contract_proposals p'
            . ' SET sent_date = r.sent_date,'
            . ' delivery_type = r.delivery_type,'
            . ' conclusion_date = r.conclusion_date'
            . ' FROM customer_proposals r'
            . ' WHERE r.id = p.customer_proposal_id',
        );

        $this->table('contract_proposals')
            ->changeColumn('customer_proposal_id', 'uuid', [
                'default' => null,
                'null' => true,
                'comment' => 'The round these papers went out in, empty when they went on their own',
            ])
            ->save();

        $this->table('contract_proposals')
            ->dropForeignKey('customer_proposal_id')
            ->addForeignKey('customer_proposal_id', 'customer_proposals', 'id', [
                'delete' => 'SET_NULL',
                'update' => 'NO_ACTION',
            ])
            ->update();
    }
}
