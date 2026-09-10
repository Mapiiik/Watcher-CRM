<?php
declare(strict_types=1);

use Migrations\BaseMigration;
use Migrations\Db\Literal;

class CreateCustomerProposals extends BaseMigration
{
    /**
     * Up Method.
     *
     * One round of a paper that concerns the customer rather than any one contract: a consent
     * today, a summary of what they are provided with or a final settlement later. Until now the
     * consent was printed and nothing was left behind except four booleans, so nobody could say
     * when it went out, whether it came back, or which printing a signed scan answered.
     *
     * No snapshot, unlike the contract's proposal. These papers list what we hold about the
     * customer as it stands, and once drawn the document itself is what keeps saying what it said
     * - it is frozen in the store like every other paper. A second copy of the same JSON beside
     * it would only be somewhere else for the truth to be.
     *
     * What it does carry is the round: the day it speaks about, when the paper was drawn, when it
     * went out, when it came back signed. That is what makes a second round tellable from the first, which is the whole
     * reason the scans hang here rather than on the customer.
     *
     * Written out both ways rather than as change(): the partial index is raw SQL, which the
     * automatic reversal replays forwards instead of undoing.
     *
     * @return void
     */
    public function up(): void
    {
        $table = $this->table('customer_proposals', [
            'id' => false,
            'primary_key' => ['id'],
        ]);

        $table->addColumn('id', 'uuid', [
            'default' => Literal::from('uuid_generate_v4()'),
            'null' => false,
        ]);

        $table->addColumn('customer_id', 'uuid', [
            'null' => false,
        ]);

        // Why the papers are going out, not which of them is printed. Which document that turns
        // into is settled at the printing and kept by the store on the file itself, exactly as it
        // is for a contract's proposal.
        $table->addColumn('purpose', 'string', [
            'limit' => 20,
            'null' => false,
            'comment' => 'What the round is for, as App\Model\Enum\CustomerProposalPurpose',
        ]);

        // Not the day it was drawn, which is the line below, but the day it speaks about: the same
        // day for a consent asked out of the blue, the day a contract starts for one that goes
        // out with it. It is always known, so it is always written down.
        $table->addColumn('effective_from', 'date', [
            'null' => false,
            'comment' => 'The day the paper relates to',
        ]);

        $table->addColumn('sent_date', 'date', [
            'default' => null,
            'null' => true,
            'comment' => 'When the paper was last sent to the customer',
        ]);

        $table->addColumn('delivery_type', 'integer', [
            'default' => null,
            'null' => true,
            'comment' => 'How it was sent, as App\Model\Enum\DocumentsDeliveryType',
        ]);

        $table->addColumn('conclusion_date', 'date', [
            'default' => null,
            'null' => true,
            'comment' => 'The day the customer signed or otherwise agreed to it',
        ]);

        $table->addColumn('revoked', 'timestamp', [
            'timezone' => true,
            'default' => null,
            'null' => true,
        ]);

        $table->addColumn('revoked_by', 'uuid', [
            'default' => null,
            'null' => true,
        ]);

        $table->addColumn('note', 'text', [
            'default' => null,
            'null' => true,
        ]);

        $table->addColumn('created', 'timestamp', [
            'timezone' => true,
            'null' => true,
        ]);

        $table->addColumn('created_by', 'uuid', [
            'null' => true,
        ]);

        $table->addColumn('modified', 'timestamp', [
            'timezone' => true,
            'null' => true,
        ]);

        $table->addColumn('modified_by', 'uuid', [
            'null' => true,
        ]);

        $table->addIndex(['customer_id']);

        // What the customer's own page asks for: their rounds, newest first.
        $table->addIndex(['customer_id', 'created']);

        $table->addForeignKey('customer_id', 'customers', 'id');
        $table->addForeignKey('revoked_by', 'users', 'id');
        $table->addForeignKey('created_by', 'users', 'id');
        $table->addForeignKey('modified_by', 'users', 'id');

        $table->create();

        // Rounds still waiting - neither signed nor given up on. This is what the checks and the
        // customer's page ask for, and it is a small part of a table that only grows.
        $this->execute(
            'CREATE INDEX customer_proposals_open ON customer_proposals (customer_id)'
            . ' WHERE conclusion_date IS NULL AND revoked IS NULL',
        );
    }

    /**
     * Down Method.
     *
     * @return void
     */
    public function down(): void
    {
        $this->execute('DROP INDEX IF EXISTS customer_proposals_open');

        $this->table('customer_proposals')->drop()->save();
    }
}
