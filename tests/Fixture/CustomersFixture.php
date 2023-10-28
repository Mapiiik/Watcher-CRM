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
                'nid' => 1,
                'dealer' => 0,
                'title' => 'Lorem ipsum dolor sit amet',
                'first_name' => 'Lorem ipsum dolor sit amet',
                'last_name' => 'Lorem ipsum dolor sit amet',
                'suffix' => 'Lorem ipsum dolor sit amet',
                'company' => 'Lorem ipsum dolor sit amet',
                'bank_name' => 'Lorem ipsum dolor sit amet',
                'bank_account' => 'Lorem ipsum dolor sit amet',
                'bank_code' => 'Lo',
                'modified' => 1698515732,
                'created' => 1698515732,
                'ic' => 'Lorem ipsu',
                'dic' => 'Lorem ipsum d',
                'www' => 'Lorem ipsum dolor sit amet',
                'internal_note' => 'Lorem ipsum dolor sit amet, aliquet feugiat. Convallis morbi fringilla gravida, phasellus feugiat dapibus velit nunc, pulvinar eget sollicitudin venenatis cum nullam, vivamus ut a sed, mollitia lectus. Nulla vestibulum massa neque ut et, id hendrerit sit, feugiat in taciti enim proin nibh, tempor dignissim, rhoncus duis vestibulum nunc mattis convallis.',
                'invoice_delivery_type' => 1,
                'note' => 'Lorem ipsum dolor sit amet, aliquet feugiat. Convallis morbi fringilla gravida, phasellus feugiat dapibus velit nunc, pulvinar eget sollicitudin venenatis cum nullam, vivamus ut a sed, mollitia lectus. Nulla vestibulum massa neque ut et, id hendrerit sit, feugiat in taciti enim proin nibh, tempor dignissim, rhoncus duis vestibulum nunc mattis convallis.',
                'identity_card_number' => 'Lorem ipsu',
                'date_of_birth' => '2023-10-28',
                'agree_gdpr' => 1,
                'agree_mailing_outages' => 1,
                'agree_mailing_commercial' => 1,
                'agree_mailing_billing' => 1,
                'id' => '403bab0e-52cd-4a8e-83f8-43c2457d0481',
                'tax_rate_id' => 'ab05963c-1531-4677-a9ee-80cecde25124',
                'created_by' => '11edb519-be76-4d66-aea0-34188d31eae1',
                'modified_by' => '11edb519-be76-4d66-aea0-34188d31eae1',
            ],
            [
                'nid' => 2,
                'dealer' => 1,
                'title' => 'Lorem ipsum dolor sit amet',
                'first_name' => 'Lorem ipsum dolor sit amet',
                'last_name' => 'Lorem ipsum dolor sit amet',
                'suffix' => 'Lorem ipsum dolor sit amet',
                'company' => 'Lorem ipsum dolor sit amet',
                'bank_name' => 'Lorem ipsum dolor sit amet',
                'bank_account' => 'Lorem ipsum dolor sit amet',
                'bank_code' => 'Lo',
                'modified' => 1698517873,
                'created' => 1698517873,
                'ic' => 'Lorem ipsu',
                'dic' => 'Lorem ipsum d',
                'www' => 'Lorem ipsum dolor sit amet',
                'internal_note' => 'Lorem ipsum dolor sit amet, aliquet feugiat. Convallis morbi fringilla gravida, phasellus feugiat dapibus velit nunc, pulvinar eget sollicitudin venenatis cum nullam, vivamus ut a sed, mollitia lectus. Nulla vestibulum massa neque ut et, id hendrerit sit, feugiat in taciti enim proin nibh, tempor dignissim, rhoncus duis vestibulum nunc mattis convallis.',
                'invoice_delivery_type' => 1,
                'note' => 'Lorem ipsum dolor sit amet, aliquet feugiat. Convallis morbi fringilla gravida, phasellus feugiat dapibus velit nunc, pulvinar eget sollicitudin venenatis cum nullam, vivamus ut a sed, mollitia lectus. Nulla vestibulum massa neque ut et, id hendrerit sit, feugiat in taciti enim proin nibh, tempor dignissim, rhoncus duis vestibulum nunc mattis convallis.',
                'identity_card_number' => 'Lorem ipsu',
                'date_of_birth' => '2023-10-28',
                'agree_gdpr' => 1,
                'agree_mailing_outages' => 1,
                'agree_mailing_commercial' => 1,
                'agree_mailing_billing' => 1,
                'id' => 'ae128a49-82fd-4b80-921f-f11af75fd113',
                'tax_rate_id' => 'ab05963c-1531-4677-a9ee-80cecde25124',
                'created_by' => '11edb519-be76-4d66-aea0-34188d31eae1',
                'modified_by' => '11edb519-be76-4d66-aea0-34188d31eae1',
            ],
        ];
        parent::init();
    }
}
