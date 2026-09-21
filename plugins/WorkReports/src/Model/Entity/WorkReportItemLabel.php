<?php
declare(strict_types=1);

namespace WorkReports\Model\Entity;

use App\Model\Entity\AppEntity;

/**
 * WorkReportItemLabel Entity
 *
 * @property string $work_report_item_id
 * @property string $work_label_id
 */
class WorkReportItemLabel extends AppEntity
{
    /**
     * Fields that can be mass assigned using newEntity() or patchEntity().
     *
     * @var array<string, bool>
     */
    protected array $_accessible = [
        'work_report_item_id' => true,
        'work_label_id' => true,
    ];
}
