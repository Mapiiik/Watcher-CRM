<?php
declare(strict_types=1);

namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * AddressesFixture
 */
class AddressesFixture extends TestFixture
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
                'type' => 1,
                'customer_id' => 1,
                'title' => 'Lorem ipsum dolor sit amet',
                'first_name' => 'Lorem ipsum dolor sit amet',
                'last_name' => 'Lorem ipsum dolor sit amet',
                'suffix' => 'Lorem ipsum dolor sit amet',
                'company' => 'Lorem ipsum dolor sit amet',
                'street' => 'Lorem ipsum dolor sit amet',
                'number' => 'Lorem ipsum dolor sit amet',
                'city' => 'Lorem ipsum dolor sit amet',
                'zip' => 'Lorem ipsum dolor sit amet',
                'country_id' => 1,
                'created_by' => 1,
                'created' => 1636113483,
                'modified_by' => 1,
                'modified' => 1636113483,
                'ruian_gid' => 1,
                'gps_x' => 1,
                'gps_y' => 1,
            ],
        ];
        parent::init();
    }
}
