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
                'task_type_id' => 'dbf92ff5-8d55-449e-8295-952bf52d6ef5',
                'subject' => 'Lorem ipsum dolor sit amet',
                'text' => 'Lorem ipsum dolor sit amet, aliquet feugiat. Convallis morbi fringilla gravida, phasellus feugiat dapibus velit nunc, pulvinar eget sollicitudin venenatis cum nullam, vivamus ut a sed, mollitia lectus. Nulla vestibulum massa neque ut et, id hendrerit sit, feugiat in taciti enim proin nibh, tempor dignissim, rhoncus duis vestibulum nunc mattis convallis.',
                'priority' => 1,
                'customer_id' => '403bab0e-52cd-4a8e-83f8-43c2457d0481',
                'dealer_id' => 'ae128a49-82fd-4b80-921f-f11af75fd113',
                'modified_by' => '11edb519-be76-4d66-aea0-34188d31eae1',
                'modified' => 1636114490,
                'created_by' => '11edb519-be76-4d66-aea0-34188d31eae1',
                'created' => 1636114490,
                'email' => 'Lorem ipsum dolor sit amet',
                'phone' => 'Lorem ipsum dolor sit amet',
                'task_state_id' => '9b9be681-37b3-4ec1-9868-827bed69a87c',
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
