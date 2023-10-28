<?php
declare(strict_types=1);

namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * BorrowedEquipmentsFixture
 */
class BorrowedEquipmentsFixture extends TestFixture
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
                'contract_id' => '7f76dc3f-a11b-4109-958b-4b0382545a66',
                'equipment_type_id' => '582ff5cb-62c6-42bd-a1bb-7f3d167124a4',
                'serial_number' => 'Lorem ipsum dolor sit amet',
                'created' => 1636113486,
                'created_by' => '11edb519-be76-4d66-aea0-34188d31eae1',
                'modified' => 1636113486,
                'modified_by' => '11edb519-be76-4d66-aea0-34188d31eae1',
                'borrowed_from' => '2021-11-05',
                'borrowed_until' => '2021-11-05',
            ],
        ];
        parent::init();
    }
}
