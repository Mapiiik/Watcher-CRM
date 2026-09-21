<?php
declare(strict_types=1);

namespace WorkReports\Model\Entity;

use App\Model\Entity\AppEntity;

/**
 * WorkReportWorkerRecipient Entity
 *
 * Somebody who gets the worker's reports and sees them, and may also change their items and
 * return them when `may_edit` says so.
 *
 * @property string $work_report_worker_id
 * @property string $user_id
 * @property bool $may_edit
 *
 * @property \WorkReports\Model\Entity\WorkReportWorker $work_report_worker
 * @property \App\Model\Entity\AppUser $user
 */
class WorkReportWorkerRecipient extends AppEntity
{
    /**
     * Fields that can be mass assigned using newEntity() or patchEntity().
     *
     * @var array<string, bool>
     */
    protected array $_accessible = [
        'work_report_worker_id' => true,
        'user_id' => true,
        'may_edit' => true,
    ];
}
