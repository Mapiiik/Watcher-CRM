<?php
declare(strict_types=1);

use Migrations\BaseMigration;
use Migrations\Db\Literal;

class CreateContractVersionProposals extends BaseMigration
{
    /**
     * Change Method.
     *
     * What a version of a contract is being asked to become, and the record of the paper that
     * asked. Until now a change had to be written into the live records before it could be
     * printed, and taken back by hand when the customer did not sign.
     *
     * A proposal holds three things. The snapshot is how everything stood when it was drawn up,
     * and the documents are printed from it, so the same paper printed twice is the same paper.
     * The changes are what is to happen once it is signed, and they reach the live records only
     * when somebody carries them over. The acknowledgements are what the operator confirmed
     * against the readiness checks - asked here once instead of at every printing.
     *
     * More information on this method is available here:
     * https://book.cakephp.org/migrations/4/en/migrations.html#the-change-method
     *
     * @return void
     */
    public function change(): void
    {
        $table = $this->table('contract_version_proposals', [
            'id' => false,
            'primary_key' => ['id'],
        ]);

        $table->addColumn('id', 'uuid', [
            'default' => Literal::from('uuid_generate_v4()'),
            'null' => false,
        ]);

        // The contract is carried alongside the version it belongs to. Nested routes, the filters
        // in AdditionalParametersTrait and the permission callbacks are all built on customer_id
        // and contract_id, and billings hold both for the same reason.
        $table->addColumn('contract_id', 'uuid', [
            'null' => false,
        ]);

        $table->addColumn('contract_version_id', 'uuid', [
            'null' => false,
        ]);

        // A shorthand, not a general mechanism: a contract replacing another could be two
        // proposals, but ContractNewX settles both with a single paper.
        $table->addColumn('terminates_contract_version_id', 'uuid', [
            'default' => null,
            'null' => true,
            'comment' => 'An earlier version of the same contract that this proposal terminates',
        ]);

        $table->addColumn('terminated_contract_number', 'string', [
            'default' => null,
            'null' => true,
            'comment' => 'The number of the terminated contract, as it stood on the paper',
        ]);

        $table->addColumn('effective_from', 'date', [
            'null' => false,
            'comment' => 'The day the proposal takes effect; on an amendment, its effective date',
        ]);

        $table->addColumn('snapshot', 'jsonb', [
            'null' => false,
            'comment' => 'How everything stood when the proposal was drawn up; documents print from this',
        ]);

        $table->addColumn('snapshot_taken', 'timestamp', [
            'timezone' => true,
            'null' => false,
            'comment' => 'When the snapshot was last taken',
        ]);

        $table->addColumn('changes', 'jsonb', [
            'null' => false,
            'comment' => 'What is to happen once the proposal is signed',
        ]);

        $table->addColumn('acknowledgements', 'jsonb', [
            'default' => '{}',
            'null' => false,
            'comment' => 'What the operator confirmed against the readiness checks',
        ]);

        $table->addColumn('sent_date', 'date', [
            'default' => null,
            'null' => true,
            'comment' => 'When the papers were last sent to the customer',
        ]);

        $table->addColumn('sent_by', 'integer', [
            'default' => null,
            'null' => true,
            'comment' => 'How they were sent, as App\Model\Enum\ContractDeliveryMethod',
        ]);

        $table->addColumn('conclusion_date', 'date', [
            'default' => null,
            'null' => true,
            'comment' => 'The day the customer signed or otherwise agreed to this proposal',
        ]);

        $table->addColumn('applied', 'timestamp', [
            'timezone' => true,
            'default' => null,
            'null' => true,
            'comment' => 'When the changes were carried over into the live records',
        ]);

        $table->addColumn('applied_by', 'uuid', [
            'default' => null,
            'null' => true,
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

        $table->addIndex(['contract_id']);
        $table->addIndex(['contract_version_id']);

        // What the version getter and the unsigned deadlines ask for: the latest sending.
        $table->addIndex(['contract_version_id', 'sent_date']);

        $table->addForeignKey('contract_id', 'contracts', 'id');
        $table->addForeignKey('contract_version_id', 'contract_versions', 'id');
        $table->addForeignKey('terminates_contract_version_id', 'contract_versions', 'id');
        $table->addForeignKey('applied_by', 'users', 'id');
        $table->addForeignKey('revoked_by', 'users', 'id');
        $table->addForeignKey('created_by', 'users', 'id');
        $table->addForeignKey('modified_by', 'users', 'id');

        $table->create();

        // Proposals still open - neither carried over nor revoked. The contract checks, the
        // dashboard and the printing offer all ask for these and for nothing else.
        $this->execute(
            'CREATE INDEX contract_version_proposals_open ON contract_version_proposals (contract_id)'
            . ' WHERE applied IS NULL AND revoked IS NULL',
        );
    }
}
