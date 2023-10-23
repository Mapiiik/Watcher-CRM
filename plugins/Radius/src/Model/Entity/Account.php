<?php
declare(strict_types=1);

namespace Radius\Model\Entity;

use Cake\ORM\Entity;

/**
 * Account Entity
 *
 * @property \Cake\I18n\DateTime|null $created
 * @property string|null $created_by
 * @property \App\Model\Entity\AppUser|null $creator
 * @property \Cake\I18n\DateTime|null $modified
 * @property string|null $modified_by
 * @property \App\Model\Entity\AppUser|null $modifier
 * @property int $id
 * @property string $username
 * @property string $password
 * @property int $type
 * @property bool $active
 * @property string $customer_id
 * @property string $contract_id
 * @property string $style
 *
 * @property \App\Model\Entity\Customer $customer
 * @property \App\Model\Entity\Contract $contract
 * @property \Radius\Model\Entity\Radcheck[] $radcheck
 * @property \Radius\Model\Entity\Radreply[] $radreply
 * @property \Radius\Model\Entity\Radusergroup[] $radusergroup
 * @property \Radius\Model\Entity\Radpostauth[] $radpostauth
 * @property \Radius\Model\Entity\Radacct[] $radacct
 */
class Account extends Entity
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
        'created' => true,
        'created_by' => true,
        'modified' => true,
        'modified_by' => true,
        'username' => true,
        'password' => true,
        'type' => true,
        'active' => true,
        'customer_id' => true,
        'contract_id' => true,
        'customer' => true,
        'contract' => true,
        'radcheck' => true,
        'radreply' => true,
        'radusergroup' => true,
        'radpostauth' => true,
        'radacct' => true,
    ];

    /**
     * Fields that are excluded from JSON versions of the entity.
     *
     * @var array<string>
     */
    protected array $_hidden = [
        'password',
    ];

    /**
     * getter for style
     *
     * @return string
     */
    protected function _getStyle(): string
    {
        $style = '';

        if (!$this->active) {
            $style = 'color: darkgray; text-decoration: line-through;';
        }

        if (isset($this->contract->style)) {
            $style .= ' ' . $this->contract->style;
        }

        return $style;
    }

    /**
     * Get account type options method
     *
     * @return array<int, string>
     */
    public function getTypeOptions(): array
    {
        return [
            0 => 'PPPoE',
        ];
    }

    /**
     * Get account type name method
     *
     * @return string
     */
    public function getTypeName(): string
    {
        return $this->getTypeOptions()[$this->type] ?? (string)$this->type;
    }
}
