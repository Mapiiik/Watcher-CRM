<?php
declare(strict_types=1);

namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * BillingsFixture
 */
class BillingsFixture extends TestFixture
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
                'customer_id' => '403bab0e-52cd-4a8e-83f8-43c2457d0481',
                'text' => 'Lorem ipsum dolor sit amet',
                'price' => 1,
                'billing_from' => '2021-11-05',
                'note' => 'Lorem ipsum dolor sit amet, aliquet feugiat. Convallis morbi fringilla gravida, phasellus feugiat dapibus velit nunc, pulvinar eget sollicitudin venenatis cum nullam, vivamus ut a sed, mollitia lectus. Nulla vestibulum massa neque ut et, id hendrerit sit, feugiat in taciti enim proin nibh, tempor dignissim, rhoncus duis vestibulum nunc mattis convallis.',
                'modified_by' => '11edb519-be76-4d66-aea0-34188d31eae1',
                'modified' => 1636113486,
                'created_by' => '11edb519-be76-4d66-aea0-34188d31eae1',
                'created' => 1636113486,
                'billing_until' => '2021-11-05',
                'separate' => 1,
                'service_id' => 'eaacfeb3-1430-43ce-842e-497c5c95d953',
                'quantity' => 1,
                'contract_id' => '7f76dc3f-a11b-4109-958b-4b0382545a66',
                'fixed_discount' => 1,
                'percentage_discount' => 1,
            ],
        ];
        parent::init();
    }
}
