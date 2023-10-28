<?php
declare(strict_types=1);

namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * CustomerLabelsFixture
 */
class CustomerLabelsFixture extends TestFixture
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
                'created' => 1698516987,
                'note' => 'Lorem ipsum dolor sit amet, aliquet feugiat. Convallis morbi fringilla gravida, phasellus feugiat dapibus velit nunc, pulvinar eget sollicitudin venenatis cum nullam, vivamus ut a sed, mollitia lectus. Nulla vestibulum massa neque ut et, id hendrerit sit, feugiat in taciti enim proin nibh, tempor dignissim, rhoncus duis vestibulum nunc mattis convallis.',
                'modified' => 1698516987,
                'id' => 'd6a30ce3-70e7-4d83-ac03-b0d847389ac2',
                'customer_id' => '403bab0e-52cd-4a8e-83f8-43c2457d0481',
                'label_id' => 'e9cbb697-be8b-4e05-8226-1b0aeb53130d',
                'created_by' => '11edb519-be76-4d66-aea0-34188d31eae1',
                'modified_by' => '11edb519-be76-4d66-aea0-34188d31eae1',
            ],
        ];
        parent::init();
    }
}
