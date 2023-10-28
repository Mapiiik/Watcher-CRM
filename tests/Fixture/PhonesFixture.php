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
                'customer_id' => '403bab0e-52cd-4a8e-83f8-43c2457d0481',
                'phone' => 'Lorem ipsum dolor sit amet',
            ],
        ];
        parent::init();
    }
}
