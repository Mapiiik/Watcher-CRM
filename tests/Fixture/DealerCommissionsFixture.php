<?php
declare(strict_types=1);

namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * DealerCommissionsFixture
 */
class DealerCommissionsFixture extends TestFixture
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
                'dealer_id' => 'ae128a49-82fd-4b80-921f-f11af75fd113',
                'commission_id' => 'f43a4e56-a052-4859-8c6c-caa29bc3144d',
                'fixed' => 1,
                'percentage' => 1,
            ],
        ];
        parent::init();
    }
}
