<?php
declare(strict_types=1);

use Migrations\BaseMigration;

/**
 * A new contract replacing an earlier version says whether the connection is set up again, which
 * is what decides whether its papers charge the activation fee.
 */
class ContractProposalsMayReactivate extends BaseMigration
{
    /**
     * Change Method.
     *
     * @return void
     */
    public function change(): void
    {
        $this->table('contract_proposals')
            ->addColumn('reactivates', 'boolean', ['default' => false, 'null' => false])
            ->update();
    }
}
