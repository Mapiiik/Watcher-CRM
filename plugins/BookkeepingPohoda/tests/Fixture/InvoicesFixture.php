<?php
declare(strict_types=1);

namespace BookkeepingPohoda\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * InvoicesFixture
 */
class InvoicesFixture extends TestFixture
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
                'number' => 1,
                'variable_symbol' => 1,
                'creation_date' => '2021-12-09',
                'due_date' => '2021-12-09',
                'text' => 'Lorem ipsum dolor sit amet, aliquet feugiat. Convallis morbi fringilla gravida, phasellus feugiat dapibus velit nunc, pulvinar eget sollicitudin venenatis cum nullam, vivamus ut a sed, mollitia lectus. Nulla vestibulum massa neque ut et, id hendrerit sit, feugiat in taciti enim proin nibh, tempor dignissim, rhoncus duis vestibulum nunc mattis convallis.',
                'total' => 1.5,
                'debt' => 1.5,
                'payment_date' => '2021-12-09',
                'send_by_email' => 1,
                'email_sent' => 1639064191,
                'created' => 1639064191,
                'created_by' => 1,
                'modified' => 1639064191,
                'modified_by' => 1,
            ],
        ];
        parent::init();
    }
}
