<?php
declare(strict_types=1);

namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;
use Override;

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
    #[Override]
    public function init(): void
    {
        $this->records = [
            [
                'id' => 'b1000000-0000-4000-8000-000000000001',
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
                'separate_invoice' => 1,
                'service_id' => 'eaacfeb3-1430-43ce-842e-497c5c95d953',
                'quantity' => 1,
                'contract_id' => '7f76dc3f-a11b-4109-958b-4b0382545a66',
                'fixed_discount' => 1,
                'percentage_discount' => 1,
            ],
            // still open (billing_until IS NULL) — the "active service" counterpart
            // of the historical billing above, on the same contract
            [
                'id' => 'b2000000-0000-4000-8000-000000000002',
                'customer_id' => '403bab0e-52cd-4a8e-83f8-43c2457d0481',
                'text' => 'Sed do eiusmod tempor',
                'price' => 2,
                'billing_from' => '2022-01-01',
                'note' => null,
                'modified_by' => '11edb519-be76-4d66-aea0-34188d31eae1',
                'modified' => 1636113486,
                'created_by' => '11edb519-be76-4d66-aea0-34188d31eae1',
                'created' => 1636113486,
                'billing_until' => null,
                'separate_invoice' => 0,
                'service_id' => '5f6a2f47-0a4d-4c05-9bcb-2f0dc0a3f0d2',
                'quantity' => 1,
                'contract_id' => '7f76dc3f-a11b-4109-958b-4b0382545a66',
                'fixed_discount' => 0,
                'percentage_discount' => 0,
            ],
            // open, and on the other contract - a page showing one contract's billings has no
            // reason to read this one, and reading it is what a lost filter looks like
            [
                'id' => 'b3000000-0000-4000-8000-000000000003',
                'customer_id' => '403bab0e-52cd-4a8e-83f8-43c2457d0481',
                'text' => 'On the other contract',
                'price' => 3,
                'billing_from' => '2022-01-01',
                'note' => null,
                'modified_by' => '11edb519-be76-4d66-aea0-34188d31eae1',
                'modified' => 1636113486,
                'created_by' => '11edb519-be76-4d66-aea0-34188d31eae1',
                'created' => 1636113486,
                'billing_until' => null,
                'separate_invoice' => 0,
                'service_id' => '5f6a2f47-0a4d-4c05-9bcb-2f0dc0a3f0d2',
                'quantity' => 1,
                'contract_id' => '9c0d5e5c-2a6b-4f8e-9a3d-1b7c4e2f6a90',
                'fixed_discount' => 0,
                'percentage_discount' => 0,
            ],
        ];
        parent::init();
    }
}
