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
                'created' => 1698516626,
                'modified' => 1698516626,
                'name' => 'Lorem ipsum dolor sit amet',
                'price' => 1,
                'not_for_new_customers' => 1,
                'queue_id' => '9a2952ed-9947-4c0e-bda8-97f00614eab4',
                'service_type_id' => '907cbc5c-af88-43b6-b535-959b4fa2ce3d',
                'id' => 'eaacfeb3-1430-43ce-842e-497c5c95d953',
                'created_by' => '11edb519-be76-4d66-aea0-34188d31eae1',
                'modified_by' => '11edb519-be76-4d66-aea0-34188d31eae1',
            ],
        ];
        parent::init();
    }
}
