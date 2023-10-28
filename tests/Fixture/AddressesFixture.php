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
                'id' => 'ab4bab00-9fe8-48b1-beef-3832a4f933a8',
                'type' => 1,
                'customer_id' => '403bab0e-52cd-4a8e-83f8-43c2457d0481',
                'title' => 'Lorem ipsum dolor sit amet',
                'first_name' => 'Lorem ipsum dolor sit amet',
                'last_name' => 'Lorem ipsum dolor sit amet',
                'suffix' => 'Lorem ipsum dolor sit amet',
                'company' => 'Lorem ipsum dolor sit amet',
                'street' => 'Lorem ipsum dolor sit amet',
                'number' => 'Lorem ipsum dolor sit amet',
                'city' => 'Lorem ipsum dolor sit amet',
                'zip' => 'Lorem ipsum dolor sit amet',
                'country_id' => 'b490f1c9-ff7e-430a-bfb0-f400878e1617',
                'created_by' => '11edb519-be76-4d66-aea0-34188d31eae1',
                'created' => 1636113483,
                'modified_by' => '11edb519-be76-4d66-aea0-34188d31eae1',
                'modified' => 1636113483,
                'ruian_gid' => 1,
                'gps_x' => 1,
                'gps_y' => 1,
            ],
        ];
        parent::init();
    }
}
