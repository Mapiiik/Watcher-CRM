<?php
declare(strict_types=1);

use Migrations\BaseMigration;

class AlterContractProposals extends BaseMigration
{
    /**
     * Up Method.
     *
     * A version says that a paper is valid from one day to another, was agreed to on some day and
     * carries an obligation until some other. It is the record of a paper's life - and until now it
     * had to exist before the paper did, because a proposal could not be drawn up without naming
     * one. So a version was declared valid before anybody had signed anything, and a proposal
     * nobody signed left one behind that was never anything.
     *
     * Leaving the column empty says the version is still to come: the proposal carries what it will
     * be, and carrying the proposal over brings it into being. Only a new contract may do that, and
     * that is a rule rather than a constraint - a change amends a version that was agreed to and an
     * ending ends one that is running, so neither has anything to start.
     *
     * Nothing is migrated. Every proposal on file names its version and goes on behaving as it did.
     *
     * @return void
     */
    public function up(): void
    {
        $this->table('contract_proposals')
            ->changeColumn('contract_version_id', 'uuid', [
                'default' => null,
                'null' => true,
                'comment' => 'The version the paper is about, empty while it is still to be created',
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
        $this->execute('DELETE FROM contract_proposals WHERE contract_version_id IS NULL');

        $this->table('contract_proposals')
            ->changeColumn('contract_version_id', 'uuid', [
                'null' => false,
                'comment' => '',
            ])
            ->save();
    }
}
