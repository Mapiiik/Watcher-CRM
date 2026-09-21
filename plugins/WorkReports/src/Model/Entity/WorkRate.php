<?php
declare(strict_types=1);

namespace WorkReports\Model\Entity;

use App\Model\Entity\AppEntity;

/**
 * WorkRate Entity
 *
 * @property string $code
 * @property string|null $name
 * @property \PhpCollective\DecimalObject\Decimal|null $price
 * @property string|null $accounting_product_code
 * @property bool $active
 * @property string $name_for_lists
 */
class WorkRate extends AppEntity
{
    /**
     * Fields that can be mass assigned using newEntity() or patchEntity().
     *
     * @var array<string, bool>
     */
    protected array $_accessible = [
        'code' => true,
        'name' => true,
        'price' => true,
        'accounting_product_code' => true,
        'active' => true,
    ];

    /**
     * The code, which is what the rate is known by, and the name after it.
     *
     * @return string
     */
    protected function _getNameForLists(): string
    {
        return $this->name === null || $this->name === '' ? $this->code : $this->code . ' - ' . $this->name;
    }
}
