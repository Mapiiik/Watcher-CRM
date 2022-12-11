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
            ],
        ];
        parent::init();
    }
}
