<?php
declare(strict_types=1);

namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * ServiceTypesFixture
 */
class ServiceTypesFixture extends TestFixture
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
                'created' => 1636113918,
                'modified' => 1636113918,
                'name' => 'Lorem ipsum dolor sit amet',
                'contract_number_format' => 'Lorem ipsum dolor sit amet',
                'activation_fee' => 1,
                'activation_fee_with_obligation' => 1,
                'separate_invoice' => 1,
                'invoice_with_items' => 1,
                'invoice_text' => 'Lorem ipsum dolor sit amet',
                'installation_address_required' => 1,
                'normally_with_borrowed_equipment' => 1,
                'access_point_required' => 1,
                'have_contract_versions' => 1,
                'have_equipments' => 1,
                'have_ip_addresses' => 1,
                'have_radius_accounts' => 1,
            ],
        ];
        parent::init();
    }
}
