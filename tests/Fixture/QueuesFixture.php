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
            ],
        ];
        parent::init();
    }
}
