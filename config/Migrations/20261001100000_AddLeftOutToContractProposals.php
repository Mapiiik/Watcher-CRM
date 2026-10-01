<?php
declare(strict_types=1);

use Migrations\BaseMigration;

class AddLeftOutToContractProposals extends BaseMigration
{
    /**
     * Change Method.
     *
     * What the proposal asked for and could not be written, so that applying the rest of it is a
     * way out rather than a quiet loss. It is kept apart from `changes` on purpose: that is what
     * the papers say and what the customer signed, and this is what became of it.
     *
     * @return void
     */
    public function change(): void
    {
        $table = $this->table('contract_proposals');

        $table->addColumn('left_out', 'jsonb', [
            'default' => '{}',
            'null' => false,
            'comment' => 'The lines applying the changes could not write, and why',
        ]);

        // What is left out is a job, not a state, so it has to be possible to say it is done -
        // otherwise the contract would carry the finding for the rest of its life.
        $table->addColumn('left_out_settled', 'timestamp', [
            'timezone' => true,
            'default' => null,
            'null' => true,
            'comment' => 'When somebody saw to what applying the changes left out',
        ]);

        $table->addColumn('left_out_settled_by', 'uuid', [
            'default' => null,
            'null' => true,
            'comment' => 'Who saw to it',
        ]);

        $table->update();
    }
}
