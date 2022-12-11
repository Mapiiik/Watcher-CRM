<?php
declare(strict_types=1);

namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * ServicesFixture
 */
class ServicesFixture extends TestFixture
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
                'created' => 1636113926,
                'modified' => 1636113926,
                'name' => 'Lorem ipsum dolor sit amet',
                'price' => 1,
                'service_type_id' => 1,
                'queue_id' => 1,
            ],
        ];
        parent::init();
    }
}
