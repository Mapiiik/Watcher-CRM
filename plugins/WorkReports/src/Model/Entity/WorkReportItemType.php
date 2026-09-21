<?php
declare(strict_types=1);

namespace WorkReports\Model\Entity;

use App\Model\Entity\AppEntity;

/**
 * WorkReportItemType Entity
 *
 * @property string $name
 * @property bool $description_required
 * @property \WorkReports\Model\Enum\TimeMode $time_mode
 * @property bool $counts_as_worked
 * @property bool $reduces_fund
 * @property bool $active
 * @property int $sort
 *
 * @property \WorkReports\Model\Entity\WorkReportItem[] $work_report_items
 */
class WorkReportItemType extends AppEntity
{
    /**
     * Fields that can be mass assigned using newEntity() or patchEntity().
     *
     * @var array<string, bool>
     */
    protected array $_accessible = [
        'name' => true,
        'description_required' => true,
        'time_mode' => true,
        'counts_as_worked' => true,
        'reduces_fund' => true,
        'active' => true,
        'sort' => true,
    ];
}
