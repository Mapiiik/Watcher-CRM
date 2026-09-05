<?php
declare(strict_types=1);

use Migrations\BaseMigration;

class AddPurposeToContractVersionProposals extends BaseMigration
{
    /**
     * What the proposal is being drawn up for.
     *
     * Nothing has held this until now, and what a proposal holds does not say it: an end date on
     * a version reads the same whether the contract runs for a fixed term or is being brought to
     * an end. The form asked for both shapes at once and the rules had to guess between them.
     *
     * The column is filled in for what is already on file before it is made required. A proposal
     * that terminates an earlier version is a new contract; one that ends the contract is an
     * ending; one on a version already concluded is a change; anything else is a new contract.
     *
     * @return void
     */
    public function up(): void
    {
        $table = $this->table('contract_version_proposals');
        $table->addColumn('purpose', 'string', [
            'default' => null,
            'limit' => 20,
            'null' => true,
            'comment' => 'What the proposal is for, as App\Model\Enum\ProposalPurpose',
        ]);
        $table->update();

        // jsonb_exists() rather than the ? operator, which PDO would read as a placeholder.
        $this->execute(<<<'SQL'
            UPDATE contract_version_proposals p SET purpose = CASE
                WHEN p.terminates_contract_version_id IS NOT NULL THEN 'new-contract'
                WHEN jsonb_exists(p.changes -> 'contract', 'termination_date') THEN 'termination'
                WHEN EXISTS (
                    SELECT 1 FROM contract_versions v
                    WHERE v.id = p.contract_version_id AND v.conclusion_date IS NOT NULL
                ) THEN 'service-change'
                ELSE 'new-contract'
            END
            SQL);

        $table->changeColumn('purpose', 'string', [
            'limit' => 20,
            'null' => false,
            'comment' => 'What the proposal is for, as App\Model\Enum\ProposalPurpose',
        ]);
        $table->update();
    }

    /**
     * Down Method.
     *
     * @return void
     */
    public function down(): void
    {
        $table = $this->table('contract_version_proposals');
        $table->removeColumn('purpose');
        $table->update();
    }
}
