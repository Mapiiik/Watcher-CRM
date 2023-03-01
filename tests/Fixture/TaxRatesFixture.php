<?php
declare(strict_types=1);

namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * TaxRatesFixture
 */
class TaxRatesFixture extends TestFixture
{
    /**
     * Init method
     *
     * @return void
     */
    public function init(): void
    {
        $this->records = [
            [
                'name' => 'Lorem ipsum dolor sit amet',
                'vat_rate' => 1,
                'reverse_charge' => 1,
                'created' => 1677660090,
                'created_by' => 1,
                'modified' => 1677660090,
                'modified_by' => 1,
                'accounting_assignment_code' => 'Lorem ipsum dolor sit amet',
                'bank_account_code' => 'Lorem ipsum dolor sit amet',
                'activity_code' => 'Lorem ipsum dolor sit amet',
            ],
        ];
        parent::init();
    }
}
