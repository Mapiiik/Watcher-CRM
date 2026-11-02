<?php
declare(strict_types=1);

use Migrations\BaseMigration;

/**
 * A customer says who they are in law, which is what decides the clauses their contract papers
 * print. Left empty until someone fills it in, so nobody is taken for a company by mistake.
 */
class CustomersHaveALegalStatus extends BaseMigration
{
    /**
     * Change Method.
     *
     * @return void
     */
    public function change(): void
    {
        $this->table('customers')
            ->addColumn('legal_status', 'string', [
                'default' => null,
                'null' => true,
                'comment' => 'As App\\Model\\Enum\\CustomerLegalStatus',
            ])
            ->update();
    }
}
