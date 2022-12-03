<?php
declare(strict_types=1);

namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * ContractVersionsFixture
 */
class ContractVersionsFixture extends TestFixture
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
                'id' => '74824fba-20b2-46fc-806c-df795aa9e429',
                'contract_id' => 1,
                'valid_from' => '2022-11-30',
                'valid_until' => '2022-11-30',
                'obligation_until' => '2022-11-30',
                'conclusion_date' => '2022-11-30',
                'number_of_amendments' => 1,
                'note' => 'Lorem ipsum dolor sit amet, aliquet feugiat. Convallis morbi fringilla gravida, phasellus feugiat dapibus velit nunc, pulvinar eget sollicitudin venenatis cum nullam, vivamus ut a sed, mollitia lectus. Nulla vestibulum massa neque ut et, id hendrerit sit, feugiat in taciti enim proin nibh, tempor dignissim, rhoncus duis vestibulum nunc mattis convallis.',
                'created' => 1669802822,
                'created_by' => 1,
                'modified' => 1669802822,
                'modified_by' => 1,
            ],
        ];
        parent::init();
    }
}
