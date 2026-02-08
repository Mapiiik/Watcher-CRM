<?php
declare(strict_types=1);

namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * ServiceOverridesFixture
 */
class ServiceOverridesFixture extends TestFixture
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
                'id' => 'f356b450-8e28-4922-b454-7776edeb92e8',
                'contract_id' => '7f76dc3f-a11b-4109-958b-4b0382545a66',
                'service_id' => 'eaacfeb3-1430-43ce-842e-497c5c95d953',
                'valid_from' => '2026-02-08',
                'valid_until' => '2026-02-08',
                'reason' => 'Lorem ipsum dolor sit amet, aliquet feugiat. Convallis morbi fringilla gravida, phasellus feugiat dapibus velit nunc, pulvinar eget sollicitudin venenatis cum nullam, vivamus ut a sed, mollitia lectus. Nulla vestibulum massa neque ut et, id hendrerit sit, feugiat in taciti enim proin nibh, tempor dignissim, rhoncus duis vestibulum nunc mattis convallis.',
                'created' => 1770555886,
                'created_by' => '11edb519-be76-4d66-aea0-34188d31eae1',
                'modified' => 1770555886,
                'modified_by' => '11edb519-be76-4d66-aea0-34188d31eae1',
                'revoked' => 1770555886,
                'revoked_by' => '11edb519-be76-4d66-aea0-34188d31eae1',
            ],
        ];
        parent::init();
    }
}
