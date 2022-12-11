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
                'customer_id' => 1,
                'text' => 'Lorem ipsum dolor sit amet',
                'price' => 1,
                'billing_from' => '2021-11-05',
                'note' => 'Lorem ipsum dolor sit amet, aliquet feugiat. Convallis morbi fringilla gravida, phasellus feugiat dapibus velit nunc, pulvinar eget sollicitudin venenatis cum nullam, vivamus ut a sed, mollitia lectus. Nulla vestibulum massa neque ut et, id hendrerit sit, feugiat in taciti enim proin nibh, tempor dignissim, rhoncus duis vestibulum nunc mattis convallis.',
                'modified_by' => 1,
                'modified' => 1636113486,
                'created_by' => 1,
                'created' => 1636113486,
                'billing_until' => '2021-11-05',
                'separate' => 1,
                'service_id' => 1,
                'quantity' => 1,
                'contract_id' => 1,
                'fixed_discount' => 1,
                'percentage_discount' => 1,
            ],
        ];
        parent::init();
    }
}
