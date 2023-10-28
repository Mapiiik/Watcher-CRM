<?php
declare(strict_types=1);

namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * RemovedIpsFixture
 */
class RemovedIpsFixture extends TestFixture
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
                'removed_by' => '11edb519-be76-4d66-aea0-34188d31eae1',
                'removed' => 1636113697,
                'ip' => '192.168.11.11',
                'customer_id' => '403bab0e-52cd-4a8e-83f8-43c2457d0481',
                'note' => 'Lorem ipsum dolor sit amet, aliquet feugiat. Convallis morbi fringilla gravida, phasellus feugiat dapibus velit nunc, pulvinar eget sollicitudin venenatis cum nullam, vivamus ut a sed, mollitia lectus. Nulla vestibulum massa neque ut et, id hendrerit sit, feugiat in taciti enim proin nibh, tempor dignissim, rhoncus duis vestibulum nunc mattis convallis.',
                'contract_id' => '7f76dc3f-a11b-4109-958b-4b0382545a66',
            ],
        ];
        parent::init();
    }
}
