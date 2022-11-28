<?php
declare(strict_types=1);

namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * ContractsFixture
 */
class ContractsFixture extends TestFixture
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
                'installation_address_id' => 1,
                'number' => 'Lorem ipsum dolor sit amet',
                'service_type_id' => 1,
                'created' => 1669642595,
                'created_by' => 1,
                'modified' => 1669642595,
                'modified_by' => 1,
                'note' => 'Lorem ipsum dolor sit amet, aliquet feugiat. Convallis morbi fringilla gravida, phasellus feugiat dapibus velit nunc, pulvinar eget sollicitudin venenatis cum nullam, vivamus ut a sed, mollitia lectus. Nulla vestibulum massa neque ut et, id hendrerit sit, feugiat in taciti enim proin nibh, tempor dignissim, rhoncus duis vestibulum nunc mattis convallis.',
                'obligation_until' => '2022-11-28',
                'vip' => 1,
                'installation_technician_id' => 1,
                'commission_id' => 1,
                'installation_date' => '2022-11-28',
                'access_description' => 'Lorem ipsum dolor sit amet, aliquet feugiat. Convallis morbi fringilla gravida, phasellus feugiat dapibus velit nunc, pulvinar eget sollicitudin venenatis cum nullam, vivamus ut a sed, mollitia lectus. Nulla vestibulum massa neque ut et, id hendrerit sit, feugiat in taciti enim proin nibh, tempor dignissim, rhoncus duis vestibulum nunc mattis convallis.',
                'valid_from' => '2022-11-28',
                'valid_until' => '2022-11-28',
                'conclusion_date' => '2022-11-28',
                'number_of_amendments' => 1,
                'activation_fee' => 1,
                'activation_fee_with_obligation' => 1,
                'access_point_id' => 'feedb343-cea8-423f-a409-de4331354217',
                'contract_state_id' => 1,
            ],
        ];
        parent::init();
    }
}
