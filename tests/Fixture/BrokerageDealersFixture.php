<?php
declare(strict_types=1);

namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * BrokerageDealersFixture
 */
class BrokerageDealersFixture extends TestFixture
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
                'dealer_id' => 1,
                'brokerage_id' => 1,
                'fixed' => 1,
                'percentage' => 1,
                'id' => 1,
            ],
        ];
        parent::init();
    }
}
