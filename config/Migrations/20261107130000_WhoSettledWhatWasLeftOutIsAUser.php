<?php
declare(strict_types=1);

use Migrations\BaseMigration;

/**
 * Who saw to what applying a proposal left out names an account, so the database says so.
 *
 * Nothing kept it in step until now, so a value naming an account that is gone is taken away first,
 * or the key could not be added. An account going later clears the name and leaves the record of
 * the settling, which says when.
 */
class WhoSettledWhatWasLeftOutIsAUser extends BaseMigration
{
    /**
     * Up Method.
     *
     * @return void
     */
    public function up(): void
    {
        $this->execute(
            'UPDATE contract_proposals SET left_out_settled_by = NULL
            WHERE left_out_settled_by IS NOT NULL
              AND NOT EXISTS (SELECT 1 FROM users WHERE users.id = contract_proposals.left_out_settled_by)',
        );

        $this->table('contract_proposals')
            ->addForeignKey('left_out_settled_by', 'users', 'id', [
                'constraint' => 'contract_proposals_left_out_settled_by_fkey',
                'update' => 'NO_ACTION',
                'delete' => 'SET_NULL',
            ])
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
            ->dropForeignKey('left_out_settled_by')
            ->update();
    }
}
