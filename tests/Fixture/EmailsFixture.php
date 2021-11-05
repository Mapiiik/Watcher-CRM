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
                'id' => 1,
                'customer_id' => 1,
                'email' => 'Lorem ipsum dolor sit amet',
                'use_for_billing' => 1,
                'use_for_outages' => 1,
                'use_for_commercial' => 1,
            ],
        ];
        parent::init();
    }
}
