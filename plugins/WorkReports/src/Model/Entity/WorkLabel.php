<?php
declare(strict_types=1);

namespace WorkReports\Model\Entity;

use App\Model\Entity\AppEntity;
use Tasks\Model\Entity\Trait\ColoredStyleTrait;

/**
 * WorkLabel Entity
 *
 * @property string $name
 * @property string|null $caption
 * @property string $color
 * @property bool $active
 * @property string $style
 */
class WorkLabel extends AppEntity
{
    use ColoredStyleTrait;

    /**
     * Fields that can be mass assigned using newEntity() or patchEntity().
     *
     * @var array<string, bool>
     */
    protected array $_accessible = [
        'name' => true,
        'caption' => true,
        'color' => true,
        'active' => true,
    ];

    /**
     * Drawn the way the labels of the customers are.
     *
     * @return string
     */
    protected function _getStyle(): string
    {
        return $this->labelStyle($this->color);
    }
}
