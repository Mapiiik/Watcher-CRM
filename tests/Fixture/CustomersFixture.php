<?php
declare(strict_types=1);

namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * CustomersFixture
 */
class CustomersFixture extends TestFixture
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
                'dealer' => 1,
                'title' => 'Lorem ipsum dolor sit amet',
                'first_name' => 'Lorem ipsum dolor sit amet',
                'last_name' => 'Lorem ipsum dolor sit amet',
                'suffix' => 'Lorem ipsum dolor sit amet',
                'company' => 'Lorem ipsum dolor sit amet',
                'tax_rate_id' => 1,
                'bank_name' => 'Lorem ipsum dolor sit amet',
                'bank_account' => 'Lorem ipsum dolor sit amet',
                'bank_code' => 'Lo',
                'modified_by' => 1,
                'modified' => 1636113486,
                'created_by' => 1,
                'created' => 1636113486,
                'ic' => 'Lorem ipsu',
                'dic' => 'Lorem ipsum d',
                'www' => 'Lorem ipsum dolor sit amet',
                'internal_note' => 'Lorem ipsum dolor sit amet, aliquet feugiat. Convallis morbi fringilla gravida, phasellus feugiat dapibus velit nunc, pulvinar eget sollicitudin venenatis cum nullam, vivamus ut a sed, mollitia lectus. Nulla vestibulum massa neque ut et, id hendrerit sit, feugiat in taciti enim proin nibh, tempor dignissim, rhoncus duis vestibulum nunc mattis convallis.',
                'invoice_delivery_type' => 1,
                'note' => 'Lorem ipsum dolor sit amet, aliquet feugiat. Convallis morbi fringilla gravida, phasellus feugiat dapibus velit nunc, pulvinar eget sollicitudin venenatis cum nullam, vivamus ut a sed, mollitia lectus. Nulla vestibulum massa neque ut et, id hendrerit sit, feugiat in taciti enim proin nibh, tempor dignissim, rhoncus duis vestibulum nunc mattis convallis.',
                'identity_card_number' => 'Lorem ipsu',
                'date_of_birth' => '2021-11-05',
                'termination_date' => '2021-11-05',
                'agree_gdpr' => 1,
                'agree_mailing_outages' => 1,
                'agree_mailing_commercial' => 1,
                'agree_mailing_billing' => 1,
            ],
        ];
        parent::init();
    }
}
