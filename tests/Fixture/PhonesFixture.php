<?php
declare(strict_types=1);

namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * PhonesFixture
 */
class PhonesFixture extends TestFixture
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
                'customer_id' => 1,
                'phone' => 'Lorem ipsum dolor sit amet',
            ],
        ];
        parent::init();
    }
}
