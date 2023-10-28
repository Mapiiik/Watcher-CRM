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
                'id' => '7f76dc3f-a11b-4109-958b-4b0382545a66',
                'customer_id' => '403bab0e-52cd-4a8e-83f8-43c2457d0481',
                'installation_address_id' => 'ab4bab00-9fe8-48b1-beef-3832a4f933a8',
                'number' => 'Lorem ipsum dolor sit amet',
                'subscriber_verification_code' => 'Lorem ipsum dolor sit amet',
                'service_type_id' => '907cbc5c-af88-43b6-b535-959b4fa2ce3d',
                'created' => 1669642595,
                'created_by' => '11edb519-be76-4d66-aea0-34188d31eae1',
                'modified' => 1669642595,
                'modified_by' => '11edb519-be76-4d66-aea0-34188d31eae1',
                'note' => 'Lorem ipsum dolor sit amet, aliquet feugiat. Convallis morbi fringilla gravida, phasellus feugiat dapibus velit nunc, pulvinar eget sollicitudin venenatis cum nullam, vivamus ut a sed, mollitia lectus. Nulla vestibulum massa neque ut et, id hendrerit sit, feugiat in taciti enim proin nibh, tempor dignissim, rhoncus duis vestibulum nunc mattis convallis.',
                'vip' => 1,
                'installation_technician_id' => 'ae128a49-82fd-4b80-921f-f11af75fd113',
                'commission_id' => 'f43a4e56-a052-4859-8c6c-caa29bc3144d',
                'installation_date' => '2022-11-28',
                'access_description' => 'Lorem ipsum dolor sit amet, aliquet feugiat. Convallis morbi fringilla gravida, phasellus feugiat dapibus velit nunc, pulvinar eget sollicitudin venenatis cum nullam, vivamus ut a sed, mollitia lectus. Nulla vestibulum massa neque ut et, id hendrerit sit, feugiat in taciti enim proin nibh, tempor dignissim, rhoncus duis vestibulum nunc mattis convallis.',
                'activation_fee' => 1,
                'activation_fee_with_obligation' => 1,
                'access_point_id' => 'feedb343-cea8-423f-a409-de4331354217',
                'contract_state_id' => '3fc51c92-5dbb-4bd4-9a47-237169c2755c',
                'uninstallation_date' => '2022-12-11',
                'uninstallation_technician_id' => null,
                'termination_date' => '2022-12-11',
            ],
        ];
        parent::init();
    }
}
