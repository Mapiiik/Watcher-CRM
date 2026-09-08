<?php
declare(strict_types=1);

use Migrations\BaseMigration;

class AddIndividualTermsToContracts extends BaseMigration
{
    /**
     * Change Method.
     *
     * What was agreed with this customer beyond the standard terms. Until now it had nowhere to
     * live: `note` is the office's own note and never reaches the paper, and everything that does
     * reach it is either worked out from the records or written in the settings and the same for
     * everybody.
     *
     * Free text on purpose. What is agreed individually is by definition what the fields did not
     * foresee, and the documents print it as it was typed.
     *
     * More information on this method is available here:
     * https://book.cakephp.org/migrations/4/en/migrations.html#the-change-method
     *
     * @return void
     */
    public function change(): void
    {
        $this->table('contracts')
            ->addColumn('individual_terms', 'text', [
                'default' => null,
                'null' => true,
                'comment' => 'What was agreed with this customer beyond the standard terms; printed on the contract',
            ])
            ->update();
    }
}
