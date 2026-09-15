<?php
declare(strict_types=1);

use Cake\Utility\Text;
use Migrations\BaseMigration;

class BackfillCustomerProposals extends BaseMigration
{
    /**
     * Up Method.
     *
     * From here on there is one proposal: it is put to the customer, and what it does for each of
     * their contracts is a part of it. Papers drawn up before that are parts of nothing, so every
     * one of them is given a proposal to be a part of.
     *
     * One apiece rather than one for everything that shares a day. They were drawn up separately
     * and travelled separately, and gathering them now would say they went out together - which
     * would then write one signature across all of them. The proposals made here ask nothing of
     * the customer themselves, which is what an empty purpose says.
     *
     * The days are copied so that a proposal reads as having gone the road its papers went, and so
     * is whoever drew them up: nobody is sitting down today and creating anything.
     *
     * Written a row at a time rather than as one statement, because the statement would have to
     * match each proposal to the one made for it - and two drawn up for the same customer on the
     * same day cannot be told apart that way.
     *
     * @return void
     */
    public function up(): void
    {
        $unlinked = $this->fetchAll(
            'SELECT p.id, c.customer_id, p.effective_from, p.sent_date, p.delivery_type,'
            . ' p.conclusion_date, p.revoked, p.revoked_by, p.created, p.created_by,'
            . ' p.modified, p.modified_by'
            . ' FROM contract_proposals p'
            . ' JOIN contracts c ON c.id = p.contract_id'
            . ' WHERE p.customer_proposal_id IS NULL',
        );

        foreach ($unlinked as $papers) {
            $round = Text::uuid();

            $this->getInsertBuilder()
                ->insert([
                    'id',
                    'customer_id',
                    'effective_from',
                    'sent_date',
                    'delivery_type',
                    'conclusion_date',
                    'revoked',
                    'revoked_by',
                    'created',
                    'created_by',
                    'modified',
                    'modified_by',
                ])
                ->into('customer_proposals')
                ->values([
                    'id' => $round,
                    'customer_id' => $papers['customer_id'],
                    'effective_from' => $papers['effective_from'],
                    'sent_date' => $papers['sent_date'],
                    'delivery_type' => $papers['delivery_type'],
                    'conclusion_date' => $papers['conclusion_date'],
                    'revoked' => $papers['revoked'],
                    'revoked_by' => $papers['revoked_by'],
                    'created' => $papers['created'],
                    'created_by' => $papers['created_by'],
                    'modified' => $papers['modified'],
                    'modified_by' => $papers['modified_by'],
                ])
                ->execute();

            $this->getUpdateBuilder()
                ->update('contract_proposals')
                ->set('customer_proposal_id', $round)
                ->where(['id' => $papers['id']])
                ->execute();
        }
    }

    /**
     * Down Method.
     *
     * @return void
     */
    public function down(): void
    {
        // Only the ones made here: a proposal made for papers that already existed was drawn up
        // at the very moment they were, which nothing made since can say of itself. The papers let
        // go of them on their own, because the key is set to drop to nothing.
        $this->execute(
            'DELETE FROM customer_proposals r WHERE r.purpose IS NULL AND EXISTS ('
            . ' SELECT 1 FROM contract_proposals p'
            . ' WHERE p.customer_proposal_id = r.id'
            . ' AND p.created IS NOT DISTINCT FROM r.created'
            . ')',
        );
    }
}
