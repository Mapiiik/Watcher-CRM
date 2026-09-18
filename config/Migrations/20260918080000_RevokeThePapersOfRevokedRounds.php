<?php
declare(strict_types=1);

use Migrations\BaseMigration;

class RevokeThePapersOfRevokedRounds extends BaseMigration
{
    /**
     * Up Method.
     *
     * Giving up on a round now says so of the papers it holds. Rounds given up on before that left
     * their papers standing open, so they went on being listed as waiting and could still be
     * edited - which is what this writes straight.
     *
     * Papers already carried over are left as they are: what reached the live records happened,
     * whatever became of the envelope afterwards.
     *
     * @return void
     */
    public function up(): void
    {
        $this->execute(
            'UPDATE contract_proposals p'
            . ' SET revoked = r.revoked, revoked_by = r.revoked_by'
            . ' FROM customer_proposals r'
            . ' WHERE r.id = p.customer_proposal_id'
            . ' AND r.revoked IS NOT NULL'
            . ' AND p.revoked IS NULL'
            . ' AND p.applied IS NULL',
        );
    }

    /**
     * Down Method.
     *
     * Only what was written here goes back: papers given up on the same moment as the round they
     * are in. One given up on by itself has its own day against it and keeps it.
     *
     * @return void
     */
    public function down(): void
    {
        $this->execute(
            'UPDATE contract_proposals p'
            . ' SET revoked = NULL, revoked_by = NULL'
            . ' FROM customer_proposals r'
            . ' WHERE r.id = p.customer_proposal_id'
            . ' AND p.revoked = r.revoked'
            . ' AND p.applied IS NULL',
        );
    }
}
