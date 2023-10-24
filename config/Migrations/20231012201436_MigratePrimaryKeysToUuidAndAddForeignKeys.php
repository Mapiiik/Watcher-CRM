<?php
declare(strict_types=1);

use Migrations\AbstractMigration;
use Phinx\Util\Literal;

class MigratePrimaryKeysToUuidAndAddForeignKeys extends AbstractMigration
{
    /**
     * Change Method.
     *
     * More information on this method is available here:
     * https://book.cakephp.org/phinx/0/en/migrations.html#the-change-method
     *
     * @return void
     */
    public function change(): void
    {
        $main_tables = [
            'addresses' => [
                'original' => 'id',
                'backup' => 'nid',
                'related' => [
                    [
                        'table' => 'contracts',
                        'original' => 'installation_address_id',
                        'backup' => 'installation_address_nid',
                        'nullable' => true,
                        'add_fk' => true,
                    ],
                ],
            ],
            'billings' => [
                'original' => 'id',
                'backup' => 'nid',
                'related' => [
                ],
            ],
            'borrowed_equipments' => [
                'original' => 'id',
                'backup' => 'nid',
                'related' => [
                ],
            ],
            'commissions' => [
                'original' => 'id',
                'backup' => 'nid',
                'related' => [
                    [
                        'table' => 'contracts',
                        'original' => 'commission_id',
                        'backup' => 'commission_nid',
                        'nullable' => true,
                        'add_fk' => true,
                    ],
                    [
                        'table' => 'dealer_commissions',
                        'original' => 'commission_id',
                        'backup' => 'commission_nid',
                        'nullable' => false,
                        'add_fk' => true,
                    ],
                ],
            ],
            'contracts' => [
                'original' => 'id',
                'backup' => 'nid',
                'keep_backup' => true,
                'related' => [
                    [
                        'table' => 'billings',
                        'original' => 'contract_id',
                        'backup' => 'contract_nid',
                        'nullable' => false,
                        'add_fk' => true,
                    ],
                    [
                        'table' => 'borrowed_equipments',
                        'original' => 'contract_id',
                        'backup' => 'contract_nid',
                        'nullable' => false,
                        'add_fk' => true,
                    ],
                    [
                        'table' => 'contract_versions',
                        'original' => 'contract_id',
                        'backup' => 'contract_nid',
                        'nullable' => false,
                        'add_fk' => true,
                    ],
                    [
                        'table' => 'ip_networks',
                        'original' => 'contract_id',
                        'backup' => 'contract_nid',
                        'nullable' => false,
                        'add_fk' => true,
                    ],
                    [
                        'table' => 'ips',
                        'original' => 'contract_id',
                        'backup' => 'contract_nid',
                        'nullable' => false,
                        'add_fk' => true,
                    ],
                    [
                        'table' => 'removed_ip_networks',
                        'original' => 'contract_id',
                        'backup' => 'contract_nid',
                        'nullable' => false,
                        'add_fk' => true,
                    ],
                    [
                        'table' => 'removed_ips',
                        'original' => 'contract_id',
                        'backup' => 'contract_nid',
                        'nullable' => false,
                        'add_fk' => true,
                    ],
                    [
                        'table' => 'sold_equipments',
                        'original' => 'contract_id',
                        'backup' => 'contract_nid',
                        'nullable' => false,
                        'add_fk' => true,
                    ],
                    [
                        'table' => 'tasks',
                        'original' => 'contract_id',
                        'backup' => 'contract_nid',
                        'nullable' => true,
                        'add_fk' => true,
                    ],
                ],
            ],
            'countries' => [
                'original' => 'id',
                'backup' => 'nid',
                'related' => [
                    [
                        'table' => 'addresses',
                        'original' => 'country_id',
                        'backup' => 'country_nid',
                        'nullable' => false,
                        'add_fk' => true,
                    ],
                ],
            ],
            'customer_labels' => [
                'original' => 'id',
                'backup' => 'nid',
                'related' => [
                ],
            ],
            'customers' => [
                'original' => 'id',
                'backup' => 'nid',
                'keep_backup' => true,
                'related' => [
                    [
                        'table' => 'addresses',
                        'original' => 'customer_id',
                        'backup' => 'customer_nid',
                        'nullable' => false,
                        'add_fk' => true,
                    ],
                    [
                        'table' => 'billings',
                        'original' => 'customer_id',
                        'backup' => 'customer_nid',
                        'nullable' => false,
                        'add_fk' => true,
                    ],
                    [
                        'table' => 'borrowed_equipments',
                        'original' => 'customer_id',
                        'backup' => 'customer_nid',
                        'nullable' => false,
                        'add_fk' => true,
                    ],
                    [
                        'table' => 'contracts',
                        'original' => 'customer_id',
                        'backup' => 'customer_nid',
                        'nullable' => false,
                        'add_fk' => true,
                    ],
                    [
                        'table' => 'contracts',
                        'original' => 'installation_technician_id',
                        'backup' => 'installation_technician_nid',
                        'nullable' => true,
                        'add_fk' => true,
                    ],
                    [
                        'table' => 'contracts',
                        'original' => 'uninstallation_technician_id',
                        'backup' => 'uninstallation_technician_nid',
                        'nullable' => true,
                        'add_fk' => true,
                    ],
                    [
                        'table' => 'customer_labels',
                        'original' => 'customer_id',
                        'backup' => 'customer_nid',
                        'nullable' => false,
                        'add_fk' => true,
                    ],
                    [
                        'table' => 'dealer_commissions',
                        'original' => 'dealer_id',
                        'backup' => 'dealer_nid',
                        'nullable' => false,
                        'add_fk' => true,
                    ],
                    [
                        'table' => 'emails',
                        'original' => 'customer_id',
                        'backup' => 'customer_nid',
                        'nullable' => false,
                        'add_fk' => true,
                    ],
                    [
                        'table' => 'ip_networks',
                        'original' => 'customer_id',
                        'backup' => 'customer_nid',
                        'nullable' => false,
                        'add_fk' => true,
                    ],
                    [
                        'table' => 'ips',
                        'original' => 'customer_id',
                        'backup' => 'customer_nid',
                        'nullable' => false,
                        'add_fk' => true,
                    ],
                    [
                        'table' => 'logins',
                        'original' => 'customer_id',
                        'backup' => 'customer_nid',
                        'nullable' => false,
                        'add_fk' => true,
                    ],
                    [
                        'table' => 'phones',
                        'original' => 'customer_id',
                        'backup' => 'customer_nid',
                        'nullable' => false,
                        'add_fk' => true,
                    ],
                    [
                        'table' => 'removed_ip_networks',
                        'original' => 'customer_id',
                        'backup' => 'customer_nid',
                        'nullable' => false,
                        'add_fk' => true,
                    ],
                    [
                        'table' => 'removed_ips',
                        'original' => 'customer_id',
                        'backup' => 'customer_nid',
                        'nullable' => false,
                        'add_fk' => true,
                    ],
                    [
                        'table' => 'sold_equipments',
                        'original' => 'customer_id',
                        'backup' => 'customer_nid',
                        'nullable' => false,
                        'add_fk' => true,
                    ],
                    [
                        'table' => 'tasks',
                        'original' => 'customer_id',
                        'backup' => 'customer_nid',
                        'nullable' => true,
                        'add_fk' => true,
                    ],
                    [
                        'table' => 'tasks',
                        'original' => 'dealer_id',
                        'backup' => 'dealer_nid',
                        'nullable' => true,
                        'add_fk' => true,
                    ],
                    [ # Migration should be in CakeDC/Users plugin, but for simplicity it's now here
                        'table' => 'users',
                        'original' => 'customer_id',
                        'backup' => 'customer_nid',
                        'nullable' => true,
                        'add_fk' => true,
                    ],
                ],
            ],
            'dealer_commissions' => [
                'original' => 'id',
                'backup' => 'nid',
                'related' => [
                ],
            ],
            'emails' => [
                'original' => 'id',
                'backup' => 'nid',
                'related' => [
                ],
            ],
            'equipment_types' => [
                'original' => 'id',
                'backup' => 'nid',
                'related' => [
                    [
                        'table' => 'borrowed_equipments',
                        'original' => 'equipment_type_id',
                        'backup' => 'equipment_type_nid',
                        'nullable' => false,
                        'add_fk' => true,
                    ],
                    [
                        'table' => 'sold_equipments',
                        'original' => 'equipment_type_id',
                        'backup' => 'equipment_type_nid',
                        'nullable' => false,
                        'add_fk' => true,
                    ],
                ],
            ],
            'ip_networks' => [
                'original' => 'id',
                'backup' => 'nid',
                'related' => [
                ],
            ],
            'ips' => [
                'original' => 'id',
                'backup' => 'nid',
                'related' => [
                ],
            ],
            'labels' => [
                'original' => 'id',
                'backup' => 'nid',
                'related' => [
                    [
                        'table' => 'customer_labels',
                        'original' => 'label_id',
                        'backup' => 'label_nid',
                        'nullable' => false,
                        'add_fk' => true,
                    ],
                ],
            ],
            'logins' => [
                'original' => 'id',
                'backup' => 'nid',
                'related' => [
                ],
            ],
            'phones' => [
                'original' => 'id',
                'backup' => 'nid',
                'related' => [
                ],
            ],
            'queues' => [
                'original' => 'id',
                'backup' => 'nid',
                'related' => [
                    [
                        'table' => 'services',
                        'original' => 'queue_id',
                        'backup' => 'queue_nid',
                        'nullable' => true,
                        'add_fk' => true,
                    ],
                ],
            ],
            'removed_ip_networks' => [
                'original' => 'id',
                'backup' => 'nid',
                'related' => [
                ],
            ],
            'removed_ips' => [
                'original' => 'id',
                'backup' => 'nid',
                'related' => [
                ],
            ],
            'service_types' => [
                'original' => 'id',
                'backup' => 'nid',
                'related' => [
                    [
                        'table' => 'contracts',
                        'original' => 'service_type_id',
                        'backup' => 'service_type_nid',
                        'nullable' => false,
                        'add_fk' => true,
                    ],
                    [
                        'table' => 'services',
                        'original' => 'service_type_id',
                        'backup' => 'service_type_nid',
                        'nullable' => true,
                        'add_fk' => true,
                    ],
                ],
            ],
            'services' => [
                'original' => 'id',
                'backup' => 'nid',
                'related' => [
                    [
                        'table' => 'billings',
                        'original' => 'service_id',
                        'backup' => 'service_nid',
                        'nullable' => true,
                        'add_fk' => true,
                    ],
                ],
            ],
            'sold_equipments' => [
                'original' => 'id',
                'backup' => 'nid',
                'related' => [
                ],
            ],
            'task_states' => [
                'original' => 'id',
                'backup' => 'nid',
                'related' => [
                    [
                        'table' => 'tasks',
                        'original' => 'task_state_id',
                        'backup' => 'task_state_nid',
                        'nullable' => false,
                        'add_fk' => true,
                    ],
                ],
            ],
            'task_types' => [
                'original' => 'id',
                'backup' => 'nid',
                'related' => [
                    [
                        'table' => 'tasks',
                        'original' => 'task_type_id',
                        'backup' => 'task_type_nid',
                        'nullable' => false,
                        'add_fk' => true,
                    ],
                ],
            ],
            'tasks' => [
                'original' => 'id',
                'backup' => 'nid',
                'keep_backup' => true,
                'related' => [
                ],
            ],
            'tax_rates' => [
                'original' => 'id',
                'backup' => 'nid',
                'related' => [
                    [
                        'table' => 'customers',
                        'original' => 'tax_rate_id',
                        'backup' => 'tax_rate_nid',
                        'nullable' => false,
                        'add_fk' => true,
                    ],
                ],
            ],
            'social_accounts' => [ # Migration should be in CakeDC/Users plugin, but for simplicity it's now here
                'original' => 'id',
                'backup' => 'nid',
                'related' => [
                ],
            ],
            'users' => [ # Migration should be in CakeDC/Users plugin, but for simplicity it's now here
                'original' => 'id',
                'backup' => 'nid',
                'keep_backup' => true,
                'related' => [
                    [
                        'table' => 'social_accounts',
                        'original' => 'user_id',
                        'backup' => 'user_nid',
                        'nullable' => false,
                        'add_fk' => true,
                    ],
                    [
                        'table' => 'addresses',
                        'original' => 'created_by',
                        'backup' => 'created_nid',
                        'nullable' => true,
                        'add_fk' => true,
                    ],
                    [
                        'table' => 'addresses',
                        'original' => 'modified_by',
                        'backup' => 'modified_nid',
                        'nullable' => true,
                        'add_fk' => true,
                    ],
                    [
                        'table' => 'billings',
                        'original' => 'created_by',
                        'backup' => 'created_nid',
                        'nullable' => true,
                        'add_fk' => true,
                    ],
                    [
                        'table' => 'billings',
                        'original' => 'modified_by',
                        'backup' => 'modified_nid',
                        'nullable' => true,
                        'add_fk' => true,
                    ],
                    [
                        'table' => 'borrowed_equipments',
                        'original' => 'created_by',
                        'backup' => 'created_nid',
                        'nullable' => true,
                        'add_fk' => true,
                    ],
                    [
                        'table' => 'borrowed_equipments',
                        'original' => 'modified_by',
                        'backup' => 'modified_nid',
                        'nullable' => true,
                        'add_fk' => true,
                    ],
                    [
                        'table' => 'commissions',
                        'original' => 'created_by',
                        'backup' => 'created_nid',
                        'nullable' => true,
                        'add_fk' => true,
                    ],
                    [
                        'table' => 'commissions',
                        'original' => 'modified_by',
                        'backup' => 'modified_nid',
                        'nullable' => true,
                        'add_fk' => true,
                    ],
                    [
                        'table' => 'contract_states',
                        'original' => 'created_by',
                        'backup' => 'created_nid',
                        'nullable' => true,
                        'add_fk' => true,
                    ],
                    [
                        'table' => 'contract_states',
                        'original' => 'modified_by',
                        'backup' => 'modified_nid',
                        'nullable' => true,
                        'add_fk' => true,
                    ],
                    [
                        'table' => 'contract_versions',
                        'original' => 'created_by',
                        'backup' => 'created_nid',
                        'nullable' => true,
                        'add_fk' => true,
                    ],
                    [
                        'table' => 'contract_versions',
                        'original' => 'modified_by',
                        'backup' => 'modified_nid',
                        'nullable' => true,
                        'add_fk' => true,
                    ],
                    [
                        'table' => 'contracts',
                        'original' => 'created_by',
                        'backup' => 'created_nid',
                        'nullable' => true,
                        'add_fk' => true,
                    ],
                    [
                        'table' => 'contracts',
                        'original' => 'modified_by',
                        'backup' => 'modified_nid',
                        'nullable' => true,
                        'add_fk' => true,
                    ],
                    [
                        'table' => 'countries',
                        'original' => 'created_by',
                        'backup' => 'created_nid',
                        'nullable' => true,
                        'add_fk' => true,
                    ],
                    [
                        'table' => 'countries',
                        'original' => 'modified_by',
                        'backup' => 'modified_nid',
                        'nullable' => true,
                        'add_fk' => true,
                    ],
                    [
                        'table' => 'customer_labels',
                        'original' => 'created_by',
                        'backup' => 'created_nid',
                        'nullable' => true,
                        'add_fk' => true,
                    ],
                    [
                        'table' => 'customer_labels',
                        'original' => 'modified_by',
                        'backup' => 'modified_nid',
                        'nullable' => true,
                        'add_fk' => true,
                    ],
                    [
                        'table' => 'customers',
                        'original' => 'created_by',
                        'backup' => 'created_nid',
                        'nullable' => true,
                        'add_fk' => true,
                    ],
                    [
                        'table' => 'customers',
                        'original' => 'modified_by',
                        'backup' => 'modified_nid',
                        'nullable' => true,
                        'add_fk' => true,
                    ],
                    [
                        'table' => 'dealer_commissions',
                        'original' => 'created_by',
                        'backup' => 'created_nid',
                        'nullable' => true,
                        'add_fk' => true,
                    ],
                    [
                        'table' => 'dealer_commissions',
                        'original' => 'modified_by',
                        'backup' => 'modified_nid',
                        'nullable' => true,
                        'add_fk' => true,
                    ],
                    [
                        'table' => 'emails',
                        'original' => 'created_by',
                        'backup' => 'created_nid',
                        'nullable' => true,
                        'add_fk' => true,
                    ],
                    [
                        'table' => 'emails',
                        'original' => 'modified_by',
                        'backup' => 'modified_nid',
                        'nullable' => true,
                        'add_fk' => true,
                    ],
                    [
                        'table' => 'equipment_types',
                        'original' => 'created_by',
                        'backup' => 'created_nid',
                        'nullable' => true,
                        'add_fk' => true,
                    ],
                    [
                        'table' => 'equipment_types',
                        'original' => 'modified_by',
                        'backup' => 'modified_nid',
                        'nullable' => true,
                        'add_fk' => true,
                    ],
                    [
                        'table' => 'ip_networks',
                        'original' => 'created_by',
                        'backup' => 'created_nid',
                        'nullable' => true,
                        'add_fk' => true,
                    ],
                    [
                        'table' => 'ip_networks',
                        'original' => 'modified_by',
                        'backup' => 'modified_nid',
                        'nullable' => true,
                        'add_fk' => true,
                    ],
                    [
                        'table' => 'ips',
                        'original' => 'created_by',
                        'backup' => 'created_nid',
                        'nullable' => true,
                        'add_fk' => true,
                    ],
                    [
                        'table' => 'ips',
                        'original' => 'modified_by',
                        'backup' => 'modified_nid',
                        'nullable' => true,
                        'add_fk' => true,
                    ],
                    [
                        'table' => 'labels',
                        'original' => 'created_by',
                        'backup' => 'created_nid',
                        'nullable' => true,
                        'add_fk' => true,
                    ],
                    [
                        'table' => 'labels',
                        'original' => 'modified_by',
                        'backup' => 'modified_nid',
                        'nullable' => true,
                        'add_fk' => true,
                    ],
                    [
                        'table' => 'logins',
                        'original' => 'created_by',
                        'backup' => 'created_nid',
                        'nullable' => true,
                        'add_fk' => true,
                    ],
                    [
                        'table' => 'logins',
                        'original' => 'modified_by',
                        'backup' => 'modified_nid',
                        'nullable' => true,
                        'add_fk' => true,
                    ],
                    [
                        'table' => 'phones',
                        'original' => 'created_by',
                        'backup' => 'created_nid',
                        'nullable' => true,
                        'add_fk' => true,
                    ],
                    [
                        'table' => 'phones',
                        'original' => 'modified_by',
                        'backup' => 'modified_nid',
                        'nullable' => true,
                        'add_fk' => true,
                    ],
                    [
                        'table' => 'queues',
                        'original' => 'created_by',
                        'backup' => 'created_nid',
                        'nullable' => true,
                        'add_fk' => true,
                    ],
                    [
                        'table' => 'queues',
                        'original' => 'modified_by',
                        'backup' => 'modified_nid',
                        'nullable' => true,
                        'add_fk' => true,
                    ],
                    [
                        'table' => 'removed_ip_networks',
                        'original' => 'created_by',
                        'backup' => 'created_nid',
                        'nullable' => true,
                        'add_fk' => true,
                    ],
                    [
                        'table' => 'removed_ip_networks',
                        'original' => 'modified_by',
                        'backup' => 'modified_nid',
                        'nullable' => true,
                        'add_fk' => true,
                    ],
                    [
                        'table' => 'removed_ip_networks',
                        'original' => 'removed_by',
                        'backup' => 'removed_nid',
                        'nullable' => true,
                        'add_fk' => true,
                    ],
                    [
                        'table' => 'removed_ips',
                        'original' => 'created_by',
                        'backup' => 'created_nid',
                        'nullable' => true,
                        'add_fk' => true,
                    ],
                    [
                        'table' => 'removed_ips',
                        'original' => 'modified_by',
                        'backup' => 'modified_nid',
                        'nullable' => true,
                        'add_fk' => true,
                    ],
                    [
                        'table' => 'removed_ips',
                        'original' => 'removed_by',
                        'backup' => 'removed_nid',
                        'nullable' => true,
                        'add_fk' => true,
                    ],
                    [
                        'table' => 'service_types',
                        'original' => 'created_by',
                        'backup' => 'created_nid',
                        'nullable' => true,
                        'add_fk' => true,
                    ],
                    [
                        'table' => 'service_types',
                        'original' => 'modified_by',
                        'backup' => 'modified_nid',
                        'nullable' => true,
                        'add_fk' => true,
                    ],
                    [
                        'table' => 'services',
                        'original' => 'created_by',
                        'backup' => 'created_nid',
                        'nullable' => true,
                        'add_fk' => true,
                    ],
                    [
                        'table' => 'services',
                        'original' => 'modified_by',
                        'backup' => 'modified_nid',
                        'nullable' => true,
                        'add_fk' => true,
                    ],
                    [
                        'table' => 'sold_equipments',
                        'original' => 'created_by',
                        'backup' => 'created_nid',
                        'nullable' => true,
                        'add_fk' => true,
                    ],
                    [
                        'table' => 'sold_equipments',
                        'original' => 'modified_by',
                        'backup' => 'modified_nid',
                        'nullable' => true,
                        'add_fk' => true,
                    ],
                    [
                        'table' => 'task_states',
                        'original' => 'created_by',
                        'backup' => 'created_nid',
                        'nullable' => true,
                        'add_fk' => true,
                    ],
                    [
                        'table' => 'task_states',
                        'original' => 'modified_by',
                        'backup' => 'modified_nid',
                        'nullable' => true,
                        'add_fk' => true,
                    ],
                    [
                        'table' => 'task_types',
                        'original' => 'created_by',
                        'backup' => 'created_nid',
                        'nullable' => true,
                        'add_fk' => true,
                    ],
                    [
                        'table' => 'task_types',
                        'original' => 'modified_by',
                        'backup' => 'modified_nid',
                        'nullable' => true,
                        'add_fk' => true,
                    ],
                    [
                        'table' => 'tasks',
                        'original' => 'created_by',
                        'backup' => 'created_nid',
                        'nullable' => true,
                        'add_fk' => true,
                    ],
                    [
                        'table' => 'tasks',
                        'original' => 'modified_by',
                        'backup' => 'modified_nid',
                        'nullable' => true,
                        'add_fk' => true,
                    ],
                    [
                        'table' => 'tax_rates',
                        'original' => 'created_by',
                        'backup' => 'created_nid',
                        'nullable' => true,
                        'add_fk' => true,
                    ],
                    [
                        'table' => 'tax_rates',
                        'original' => 'modified_by',
                        'backup' => 'modified_nid',
                        'nullable' => true,
                        'add_fk' => true,
                    ],
                ],
            ],
        ];

        foreach ($main_tables as $main_table => $main_table_data) {
            // removal of foreign keys for related tables in case of up migration
            if ($this->isMigratingUp()) {
                foreach ($main_table_data['related'] as $related_table) {
                    $ftable = $this->table($related_table['table']);

                    if ($ftable->hasForeignKey($related_table['original'])) {
                        $ftable->dropForeignKey($related_table['original']);
                        $ftable->update();
                    }

                    unset($ftable);
                }
            }

            // UUID for main table
            $table = $this->table($main_table);

            $table->renameColumn($main_table_data['original'], $main_table_data['backup']);
            $table->update();

            $table->addColumn($main_table_data['original'], 'uuid', [
                'default' => Literal::from('uuid_generate_v4()'),
                'null' => false,
            ]);
            $table->update();

            // reset primary key in case of up migration
            if ($this->isMigratingUp()) {
                $table->changePrimaryKey(['id']);
                $table->update();
            }

            // add a unique index for the old numeric primary key
            $table->addIndex($main_table_data['backup'], [
                'unique' => true,
            ]);
            $table->update();

            // main table done
            unset($table);

            foreach ($main_table_data['related'] as $related_table) {
                // nullable UUID for related table
                $rtable = $this->table($related_table['table']);

                $rtable->renameColumn($related_table['original'], $related_table['backup']);
                $rtable->update();

                $rtable->addColumn($related_table['original'], 'uuid', [
                    'default' => null,
                    'null' => true,
                ]);
                $rtable->update();

                // set new UUID keys for the related table
                if ($this->isMigratingUp()) {
                    /** @var \Cake\Database\Query\SelectQuery $selectBuilder */
                    $selectBuilder = $this->getQueryBuilder('select');
                    $main_rows = $selectBuilder
                        ->select([$main_table_data['original'], $main_table_data['backup']])
                        ->from($main_table)
                        ->execute()
                        ->fetchAll('assoc');

                    foreach ($main_rows as $main_row) {
                        /** @var \Cake\Database\Query\UpdateQuery $updateBuilder */
                        $updateBuilder = $this->getQueryBuilder('update');
                        $updateBuilder
                            ->update($related_table['table'])
                            ->set($related_table['original'], $main_row[$main_table_data['original']])
                            ->where([$related_table['backup'] => $main_row[$main_table_data['backup']]])
                            ->execute();
                        unset($updateBuilder);
                    }
                    unset($main_rows);
                    unset($selectBuilder);

                    // set to not nullable if required
                    if ($related_table['nullable'] !== true) {
                        $rtable->changeColumn($related_table['original'], 'uuid', [
                            'default' => null,
                            'null' => false,
                        ]);
                    }

                    // add foreign key if required
                    if ($related_table['add_fk'] === true) {
                        $rtable->addForeignKey(
                            $related_table['original'],
                            $main_table,
                            $main_table_data['original'],
                            [
                                'delete' => 'NO_ACTION',
                                'update' => 'NO_ACTION',
                            ]
                        );
                    }

                    $rtable->update();
                }

                // related table done
                unset($rtable);
            }
        }
    }
}
