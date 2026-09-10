<?php
declare(strict_types=1);

use Migrations\BaseMigration;

class RenameContractVersionProposalsToContractProposals extends BaseMigration
{
    /**
     * Up Method.
     *
     * They are proposals of contracts, so that is what they are called. The old name said which
     * table the foreign key pointed at, which is a fact about the schema rather than about the
     * papers, and it read as a mouthful everywhere it appeared. The customer's own proposals
     * arriving beside them is what made the difference plain.
     *
     * The column that points at the version stays as it is: a proposal still belongs to one, and
     * only the name of the agenda has changed.
     *
     * The documents already filed carry the old name in `model`, so they are moved with it.
     * Without that every paper drawn so far would come loose from the proposal it was drawn from.
     * Asked for rather than assumed: the store is a plugin and brings its own migrations, which on
     * a database being built from nothing have not necessarily run yet.
     *
     * @return void
     */
    public function up(): void
    {
        $this->table('contract_version_proposals')
            ->rename('contract_proposals')
            ->update();

        // A rename leaves the indexes named after what the table used to be.
        $this->execute('ALTER INDEX contract_version_proposals_open RENAME TO contract_proposals_open');

        if ($this->hasTable('file_links')) {
            $this->execute(
                "UPDATE file_links SET model = 'ContractProposals' WHERE model = 'ContractVersionProposals'",
            );
        }
    }

    /**
     * Down Method.
     *
     * @return void
     */
    public function down(): void
    {
        if ($this->hasTable('file_links')) {
            $this->execute(
                "UPDATE file_links SET model = 'ContractVersionProposals' WHERE model = 'ContractProposals'",
            );
        }

        $this->execute('ALTER INDEX contract_proposals_open RENAME TO contract_version_proposals_open');

        $this->table('contract_proposals')
            ->rename('contract_version_proposals')
            ->update();
    }
}
