<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * ContractState Entity
 *
 * @property \Cake\I18n\DateTime|null $created
 * @property int|null $created_by
 * @property \CakeDC\Users\Model\Entity\User|null $creator
 * @property \Cake\I18n\DateTime|null $modified
 * @property int|null $modified_by
 * @property \CakeDC\Users\Model\Entity\User|null $modifier
 * @property string $id
 * @property string $name
 * @property string $color
 * @property bool $active_services
 * @property bool $billed
 * @property bool $blocked
 * @property string|null $note
 *
 * @property \App\Model\Entity\Contract[] $contracts
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
        'created' => true,
        'created_by' => true,
        'modified' => true,
        'modified_by' => true,
        'creator' => true,
        'modifier' => true,
        'contracts' => true,
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
