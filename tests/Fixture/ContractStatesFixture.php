<?php
declare(strict_types=1);

namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;
use Override;

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
    #[Override]
    public function init(): void
    {
        $this->records = [
            [
                'id' => '3fc51c92-5dbb-4bd4-9a47-237169c2755c',
                'name' => 'Lorem ipsum dolor sit amet',
                'color' => '#c8e6c9',

                'active_services' => 1,
                'billed' => 1,
                'blocked' => 1,

                // New contract availability
                'usable_for_new_contract' => 1,

                // Tasks
                'requires_open_task_type_id' => null,
                'requires_no_open_tasks' => 0,

                // Billings
                'requires_no_active_billings' => 0,
                'requires_no_future_billings' => 0,

                // Network
                'requires_no_assigned_ip_addresses_or_networks' => 0,
                'requires_no_active_radius_accounts' => 0,

                // Hardware
                'requires_no_borrowed_equipments' => 0,

                // Dates
                'requires_installation_date' => 0,
                'requires_uninstallation_date' => 0,
                'requires_termination_date' => 0,

                // Contract versions
                'requires_contract_version' => 0,
                'requires_active_contract_version' => 0,
                'requires_active_or_future_contract_version' => 0,
                'requires_no_active_or_future_contract_versions' => 0,
                'requires_no_active_obligations' => 0,

                // Consistent end dates
                'requires_versions_matching_termination' => 0,
                'requires_billings_matching_termination' => 0,
                'requires_equipments_matching_uninstallation' => 0,

                // Meta
                'created' => 1669643075,
                'created_by' => '11edb519-be76-4d66-aea0-34188d31eae1',
                'modified' => 1669643075,
                'modified_by' => '11edb519-be76-4d66-aea0-34188d31eae1',
            ],
        ];
        parent::init();
    }
}
