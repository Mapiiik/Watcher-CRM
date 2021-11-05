<?php
declare(strict_types=1);

namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * ServiceTypesFixture
 */
class ServiceTypesFixture extends TestFixture
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
                'created' => 1636113918,
                'modified' => 1636113918,
                'name' => 'Lorem ipsum dolor sit amet',
                'contract_number_format' => 'Lorem ipsum dolor sit amet',
                'activation_fee' => 1,
                'activation_fee_with_obligation' => 1,
            ],
        ];
        parent::init();
    }
}
