<?php
declare(strict_types=1);

namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * SoldEquipmentsFixture
 */
class SoldEquipmentsFixture extends TestFixture
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
                'id' => 1,
                'customer_id' => 1,
                'contract_id' => 1,
                'equipment_type_id' => 1,
                'serial_number' => 'Lorem ipsum dolor sit amet',
                'created' => 1636113947,
                'created_by' => 1,
                'modified' => 1636113947,
                'modified_by' => 1,
            ],
        ];
        parent::init();
    }
}
