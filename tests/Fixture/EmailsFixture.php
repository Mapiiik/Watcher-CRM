<?php
declare(strict_types=1);

namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * EmailsFixture
 */
class EmailsFixture extends TestFixture
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
                'email' => 'Lorem ipsum dolor sit amet',
                'use_for_billing' => 1,
                'use_for_outages' => 1,
                'use_for_commercial' => 1,
            ],
        ];
        parent::init();
    }
}
