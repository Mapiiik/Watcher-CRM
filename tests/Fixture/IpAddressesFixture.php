<?php
declare(strict_types=1);

namespace App\Test\Fixture;

use App\Model\Enum\IpAddressTypeOfUse;
use Cake\TestSuite\Fixture\TestFixture;

/**
 * IpAddressesFixture
 */
class IpAddressesFixture extends TestFixture
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
                'ip_address' => '192.168.11.11',
                'type_of_use' => IpAddressTypeOfUse::CustomerManually,
                'customer_id' => '403bab0e-52cd-4a8e-83f8-43c2457d0481',
                'note' => 'Lorem ipsum dolor sit amet, aliquet feugiat. Convallis morbi fringilla gravida, phasellus feugiat dapibus velit nunc, pulvinar eget sollicitudin venenatis cum nullam, vivamus ut a sed, mollitia lectus. Nulla vestibulum massa neque ut et, id hendrerit sit, feugiat in taciti enim proin nibh, tempor dignissim, rhoncus duis vestibulum nunc mattis convallis.',
                'contract_id' => '7f76dc3f-a11b-4109-958b-4b0382545a66',
                'created' => 1636113487,
                'created_by' => '11edb519-be76-4d66-aea0-34188d31eae1',
                'modified' => 1636113487,
                'modified_by' => '11edb519-be76-4d66-aea0-34188d31eae1',
            ],
        ];
        parent::init();
    }
}
