<?php
declare(strict_types=1);

namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * CustomerMessagesFixture
 */
class CustomerMessagesFixture extends TestFixture
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
                'id' => '119c1d5b-763e-4ae5-ab5e-578150236461',
                'customer_id' => '403bab0e-52cd-4a8e-83f8-43c2457d0481',
                'type' => 10,
                'direction' => 10,
                'recipients' => [],
                'subject' => 'Lorem ipsum dolor sit amet',
                'body' => 'Lorem ipsum dolor sit amet, aliquet feugiat. Convallis morbi fringilla gravida, phasellus feugiat dapibus velit nunc, pulvinar eget sollicitudin venenatis cum nullam, vivamus ut a sed, mollitia lectus. Nulla vestibulum massa neque ut et, id hendrerit sit, feugiat in taciti enim proin nibh, tempor dignissim, rhoncus duis vestibulum nunc mattis convallis.',
                'body_format' => 0,
                'delivery_status' => 0,
                'processed' => 1707837371,
                'identifier' => 'Lorem ipsum dolor sit amet',                
                'created' => 1707837371,
                'created_by' => '11edb519-be76-4d66-aea0-34188d31eae1',
                'modified' => 1707837371,
                'modified_by' => '11edb519-be76-4d66-aea0-34188d31eae1',
            ],
        ];
        parent::init();
    }
}
