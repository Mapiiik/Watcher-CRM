<?php
declare(strict_types=1);

namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * ContractStatesFixture
 */
class ContractStatesFixture extends TestFixture
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
                'id' => '3fc51c92-5dbb-4bd4-9a47-237169c2755c',
                'name' => 'Lorem ipsum dolor sit amet',
                'color' => 'Lorem',
                'active_services' => 1,
                'billed' => 1,
                'blocked' => 1,
                'created' => 1669643075,
                'created_by' => '11edb519-be76-4d66-aea0-34188d31eae1',
                'modified' => 1669643075,
                'modified_by' => '11edb519-be76-4d66-aea0-34188d31eae1',
            ],
        ];
        parent::init();
    }
}
