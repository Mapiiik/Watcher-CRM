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
                'id' => 1,
                'name' => 'Lorem ipsum dolor sit amet',
                'caption' => 'Lorem ipsum dolor sit amet',
                'fup' => 1,
                'limit' => 1,
                'overlimit_fragment' => 1,
                'overlimit_cost' => 1,
                'service_type_id' => 1,
                'speed_up' => 1,
                'speed_down' => 1,
            ],
        ];
        parent::init();
    }
}
