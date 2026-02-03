<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * ContractState Entity
 *
 * @property \Cake\I18n\DateTime|null $created
 * @property string|null $created_by
 * @property \App\Model\Entity\AppUser|null $creator
 * @property \Cake\I18n\DateTime|null $modified
 * @property string|null $modified_by
 * @property \App\Model\Entity\AppUser|null $modifier
 * @property string $id
 * @property string $name
 * @property string $color
 * @property bool $active_services
 * @property bool $billed
 * @property bool $blocked
 * @property string|null $note
 *
 * @property \App\Model\Entity\Contract[] $contracts
 * @property \App\Model\Entity\TaskType|null $requires_open_task_type
 *
 * @property string $name_for_lists
 */
class ContractState extends Entity
{
    /**
     * Fields that can be mass assigned using newEntity() or patchEntity().
     *
     * Note that when '*' is set to true, this allows all unspecified fields to
     * be mass assigned. For security purposes, it is advised to set '*' to false
     * (or remove it), and explicitly make individual fields accessible as needed.
     *
     * @var array<string, bool>
     */
    protected array $_accessible = [
        'name' => true,
        'color' => true,
        'active_services' => true,
        'billed' => true,
        'blocked' => true,
        'note' => true,

        // New contract availability
        'usable_for_new_contract' => true,

        // Tasks
        'requires_open_task_type_id' => true,
        'requires_no_open_tasks' => true,

        // Billings
        'requires_no_active_billings' => true,
        'requires_no_future_billings' => true,

        // Network
        'requires_no_assigned_ip_addresses_or_networks' => true,
        'requires_no_active_radius_accounts' => true,

        // Hardware
        'requires_no_borrowed_equipments' => true,

        // Dates
        'requires_installation_date' => true,
        'requires_uninstallation_date' => true,
        'requires_termination_date' => true,

        // Contract versions
        'requires_contract_version' => true,
        'requires_active_contract_version' => true,
        'requires_active_or_future_contract_version' => true,
        'requires_no_active_or_future_contract_versions' => true,
        'requires_no_active_obligations' => true,

        // Meta
        'created' => true,
        'created_by' => true,
        'modified' => true,
        'modified_by' => true,

        // Associations
        'creator' => true,
        'modifier' => true,
        'contracts' => true,
        'requires_open_task_type' => true,
    ];

    /**
     * getter for name with notes for lists
     *
     * @return string
     */
    protected function _getNameForLists(): string
    {
        $name = $this->name;

        if (isset($this->note)) {
            $name .= ' (' . $this->note . ')';
        }

        return $name;
    }
}
