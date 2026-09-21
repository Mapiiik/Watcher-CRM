<?php
declare(strict_types=1);

namespace WorkReports\Model\Entity;

use App\Model\Entity\AppEntity;

/**
 * WorkReportOnCall Entity
 *
 * A day of being on call, with the hours it was worth when it was entered.
 *
 * @property string $work_report_id
 * @property \Cake\I18n\Date $date
 * @property string $hours
 *
 * @property \WorkReports\Model\Entity\WorkReport $work_report
 */
class WorkReportOnCall extends AppEntity
{
    /**
     * Fields that can be mass assigned using newEntity() or patchEntity().
     *
     * @var array<string, bool>
     */
    protected array $_accessible = [
        'work_report_id' => true,
        'date' => true,
        'hours' => true,
    ];
}
