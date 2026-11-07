<?php
declare(strict_types=1);

namespace App\Model\Entity;

use App\Model\Entity\Trait\DashboardVisibilityTrait;
use Tasks\Model\Entity\Trait\ColoredStyleTrait;

/**
 * Label Entity
 *
 * @property string $id
 * @property int $nid
 * @property string|null $name
 * @property string|null $caption
 * @property string $color
 * @property int|null $validity
 * @property bool $dynamic
 * @property string|null $dynamic_sql
 * @property bool $show_on_dashboard
 * @property list<string>|null $dashboard_roles
 * @property list<string> $dashboard_role_names
 * @property string $style
 *
 * @property \App\Model\Entity\CustomerLabel[] $customer_labels
 */
class Label extends AppEntity
{
    use ColoredStyleTrait;
    use DashboardVisibilityTrait;

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
        'name' => true,
        'caption' => true,
        'color' => true,
        'validity' => true,
        'dynamic' => true,
        'dynamic_sql' => true,
        'show_on_dashboard' => true,
        'dashboard_roles' => true,
        'customer_labels' => true,
    ];

    /**
     * getter for style
     *
     * @return string
     */
    protected function _getStyle(): string
    {
        return $this->labelStyle($this->color);
    }
}
