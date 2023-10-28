<?php
declare(strict_types=1);

namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * QueuesFixture
 */
class QueuesFixture extends TestFixture
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
                'name' => 'Lorem ipsum dolor sit amet',
                'caption' => 'Lorem ipsum dolor sit amet',
                'fup' => 1,
                'speed_up' => 1,
                'speed_down' => 1,
                'cto_category' => 'Lorem ipsum dolor sit amet',
                'created' => 1698519305,
                'modified' => 1698519305,
                'id' => '9a2952ed-9947-4c0e-bda8-97f00614eab4',
            ],
        ];
        parent::init();
    }
}
