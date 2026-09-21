<?php
declare(strict_types=1);

namespace WorkReports\Model\Entity;

use App\Model\Entity\AppEntity;
use WorkReports\Model\Enum\WorkReportState;

/**
 * WorkReport Entity
 *
 * A month of one worker.
 *
 * @property string $user_id
 * @property \Cake\I18n\Date $month
 * @property \PhpCollective\DecimalObject\Decimal $workload
 * @property \WorkReports\Model\Enum\WorkReportState $state
 * @property \Cake\I18n\DateTime|null $submitted
 * @property string|null $submitted_by
 * @property string|null $note
 *
 * @property \App\Model\Entity\AppUser $user
 * @property \App\Model\Entity\AppUser|null $submitter
 * @property \WorkReports\Model\Entity\WorkReportItem[] $work_report_items
 * @property \WorkReports\Model\Entity\WorkReportOnCall[] $work_report_on_calls
 */
class WorkReport extends AppEntity
{
    /**
     * Fields that can be mass assigned using newEntity() or patchEntity().
     *
     * @var array<string, bool>
     */
    protected array $_accessible = [
        'user_id' => true,
        'month' => true,
        'workload' => true,
        'note' => true,
    ];

    /**
     * Once submitted, the items are no longer the worker's to change.
     *
     * @return bool
     */
    public function isLocked(): bool
    {
        return $this->state === WorkReportState::Submitted;
    }
}
