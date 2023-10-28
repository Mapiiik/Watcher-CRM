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
                'created' => 1698516590,
                'modified' => 1698516590,
                'accounting_assignment_code' => 'Lorem ipsum dolor sit amet',
                'bank_account_code' => 'Lorem ipsum dolor sit amet',
                'activity_code' => 'Lorem ipsum dolor sit amet',
                'id' => 'ab05963c-1531-4677-a9ee-80cecde25124',
                'created_by' => '11edb519-be76-4d66-aea0-34188d31eae1',
                'modified_by' => '11edb519-be76-4d66-aea0-34188d31eae1',
            ],
        ];
        parent::init();
    }
}
