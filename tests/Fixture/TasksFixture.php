<?php
declare(strict_types=1);

namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * TasksFixture
 */
class TasksFixture extends TestFixture
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
                'task_type_id' => 1,
                'subject' => 'Lorem ipsum dolor sit amet',
                'text' => 'Lorem ipsum dolor sit amet, aliquet feugiat. Convallis morbi fringilla gravida, phasellus feugiat dapibus velit nunc, pulvinar eget sollicitudin venenatis cum nullam, vivamus ut a sed, mollitia lectus. Nulla vestibulum massa neque ut et, id hendrerit sit, feugiat in taciti enim proin nibh, tempor dignissim, rhoncus duis vestibulum nunc mattis convallis.',
                'priority' => 1,
                'customer_id' => 1,
                'dealer_id' => 1,
                'modified_by' => 1,
                'modified' => 1636114490,
                'created_by' => 1,
                'created' => 1636114490,
                'email' => 'Lorem ipsum dolor sit amet',
                'phone' => 'Lorem ipsum dolor sit amet',
                'task_state_id' => 1,
                'finish_date' => '2021-11-05',
                'start_date' => '2021-11-05',
                'estimated_date' => '2021-11-05',
                'critical_date' => '2021-11-05',
                'router_id' => 1,
            ],
        ];
        parent::init();
    }
}
