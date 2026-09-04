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
                'id' => '7f76dc3f-a11b-4109-958b-4b0382545a66',
                'number' => 'Lorem ipsum dolor sit amet',
                'activation_fee' => null,
                'service_type' => ['id' => '7be0a2b6-8d3d-4ff4-b8cb-4a2b8ac21b6a', 'name' => 'Internet'],
                'installation_address' => ['id' => 'ab4bab00-9fe8-48b1-beef-3832a4f933a8'],
            ],
            'customer' => [
                'id' => '403bab0e-52cd-4a8e-83f8-43c2457d0481',
                'nid' => 1,
                'accounting_profile' => ['vat_rate' => 0.21, 'reverse_charge' => false],
                'addresses' => [['id' => 'ab4bab00-9fe8-48b1-beef-3832a4f933a8']],
                'emails' => [],
                'phones' => [],
            ],
            'version' => [
                'id' => '74824fba-20b2-46fc-806c-df795aa9e429',
                'valid_from' => '2022-11-30',
                'valid_until' => '2022-11-30',
                'obligation_until' => '2022-11-30',
                'conclusion_date' => '2022-11-30',
            ],
            'terminated_version' => null,
            'billings' => [
                [
                    'id' => 'b2000000-0000-4000-8000-000000000002',
                    'billing_from' => '2022-01-01',
                    'billing_until' => null,
                    'text' => 'Sed do eiusmod tempor',
                    'quantity' => 1,
                    'price' => '2.00',
                    'service' => null,
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
