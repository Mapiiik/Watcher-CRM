<?php
declare(strict_types=1);

namespace App\Test\Fixture;

use App\Contracts\Proposal\ProposalAcknowledgements;
use Cake\TestSuite\Fixture\TestFixture;
use Override;

/**
 * ContractVersionProposalsFixture
 */
class ContractVersionProposalsFixture extends TestFixture
{
    /**
     * Init method
     *
     * @return void
     */
    #[Override]
    public function init(): void
    {
        $snapshot = [
            'contract' => [
                'number' => '2022/0001',
                'subscriber_verification_code' => null,
                'termination_date' => null,
                'activation_fee_sum' => '0.00',
                'activation_fee_with_obligation_sum' => '0.00',
            ],
            'customer' => [
                'number' => '1',
                'date_of_birth' => null,
                'identity_card_number' => null,
                'identity_number' => '12345678',
                'vat_number' => null,
                'email' => 'lorem@example.com',
                'phone' => '+420123456789',
                'vat_rate' => 0.21,
                'reverse_charge' => false,
            ],
            'addresses' => [
                'billing' => [
                    'company' => null,
                    'full_name' => 'Lorem Ipsum',
                    'street_and_number_extra' => 'Lorem 1',
                    'zip_and_city' => '100 00 Praha',
                ],
                'delivery' => 'Lorem 1, 100 00 Praha',
                'permanent' => 'Lorem 1, 100 00 Praha',
                'installation' => 'Lorem 1, 100 00 Praha',
            ],
            'version' => [
                'id' => '74824fba-20b2-46fc-806c-df795aa9e429',
                'valid_from' => '2022-11-30',
                'valid_until' => '2022-11-30',
                'obligation_until' => '2022-11-30',
                'minimum_duration' => 1,
            ],
            'billings' => [
                [
                    'id' => 'b2000000-0000-4000-8000-000000000002',
                    'billing_from' => '2022-01-01',
                    'billing_until' => null,
                    'text' => 'Sed do eiusmod tempor',
                    'quantity' => 1,
                    'price' => '2.00',
                    'fixed_discount' => null,
                    'percentage_discount' => null,
                    'separate_invoice' => false,
                    'service' => [
                        'id' => 'eaacfeb3-1430-43ce-842e-497c5c95d953',
                        'name' => 'Lorem ipsum dolor sit amet',
                        'price' => '1.00',
                        'queue' => null,
                    ],
                ],
            ],
            'borrowed_equipments' => [],
            'sold_equipments' => [],
            'ip_addresses' => [],
            'ip_networks' => [],
        ];

        $this->records = [
            [
                'id' => 'c9a1f2b3-4d5e-4f60-8a71-9b2c3d4e5f60',
                'contract_id' => '7f76dc3f-a11b-4109-958b-4b0382545a66',
                'contract_version_id' => '74824fba-20b2-46fc-806c-df795aa9e429',
                'terminates_contract_version_id' => null,
                'terminated_contract_number' => null,
                'effective_from' => '2026-09-01',
                'snapshot' => $snapshot,
                'snapshot_taken' => 1772582400,
                'changes' => [],
                'acknowledgements' => [ProposalAcknowledgements::FIXED_TERM => true],
                'sent_date' => null,
                'sent_by' => null,
                'conclusion_date' => null,
                'applied' => null,
                'applied_by' => null,
                'revoked' => null,
                'revoked_by' => null,
                'note' => null,
                'created' => 1772582400,
                'created_by' => '11edb519-be76-4d66-aea0-34188d31eae1',
                'modified' => 1772582400,
                'modified_by' => '11edb519-be76-4d66-aea0-34188d31eae1',
            ],
        ];
        parent::init();
    }
}
