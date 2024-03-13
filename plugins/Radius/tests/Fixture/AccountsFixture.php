<?php
declare(strict_types=1);

namespace Radius\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;
use Radius\Model\Enum\AccountType;

/**
 * AccountsFixture
 */
class AccountsFixture extends TestFixture
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
                'username' => 'Lorem ipsum dolor sit amet, aliquet feugiat. Convallis morbi fringilla gravida, phasellus feugiat dapibus velit nunc, pulvinar eget sollicitudin venenatis cum nullam, vivamus ut a sed, mollitia lectus. Nulla vestibulum massa neque ut et, id hendrerit sit, feugiat in taciti enim proin nibh, tempor dignissim, rhoncus duis vestibulum nunc mattis convallis.',
                'password' => 'Lorem ipsum dolor sit amet, aliquet feugiat. Convallis morbi fringilla gravida, phasellus feugiat dapibus velit nunc, pulvinar eget sollicitudin venenatis cum nullam, vivamus ut a sed, mollitia lectus. Nulla vestibulum massa neque ut et, id hendrerit sit, feugiat in taciti enim proin nibh, tempor dignissim, rhoncus duis vestibulum nunc mattis convallis.',
                'type' => AccountType::PPPoE,
                'active' => 1,
                'customer_id' => '403bab0e-52cd-4a8e-83f8-43c2457d0481',
                'contract_id' => '7f76dc3f-a11b-4109-958b-4b0382545a66',
                'created' => 1622028698,
                'created_by' => '11edb519-be76-4d66-aea0-34188d31eae1',
                'modified' => 1622028698,
                'modified_by' => '11edb519-be76-4d66-aea0-34188d31eae1',
            ],
        ];
        parent::init();
    }
}
