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
                'customer_id' => 1,
                'contract_id' => 1,
                'equipment_type_id' => 1,
                'serial_number' => 'Lorem ipsum dolor sit amet',
                'created' => 1636113486,
                'created_by' => 1,
                'modified' => 1636113486,
                'modified_by' => 1,
                'borrowed_from' => '2021-11-05',
                'borrowed_until' => '2021-11-05',
            ],
        ];
        parent::init();
    }
}
