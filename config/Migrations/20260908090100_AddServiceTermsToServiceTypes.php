<?php
declare(strict_types=1);

use Migrations\BaseMigration;

class AddServiceTermsToServiceTypes extends BaseMigration
{
    /**
     * Change Method.
     *
     * What holds for every contract of one kind of service. It stands beside the contract's own
     * individual terms rather than being copied into them, so that the two can be read apart on
     * the paper - one is what this service is, the other is what this customer was given.
     *
     * More information on this method is available here:
     * https://book.cakephp.org/migrations/4/en/migrations.html#the-change-method
     *
     * @return void
     */
    public function change(): void
    {
        $this->table('service_types')
            ->addColumn('service_terms', 'text', [
                'default' => null,
                'null' => true,
                'comment' => 'What holds for every contract of this kind of service; printed on the contract',
            ])
            ->update();
    }
}
