<?php
declare(strict_types=1);

namespace WorkReports\Model\Entity;

use App\Model\Entity\AppEntity;

/**
 * WorkReportWorker Entity
 *
 * What the reports need to know about a user that the users themselves do not carry.
 *
 * @property string $user_id
 * @property \PhpCollective\DecimalObject\Decimal $workload
 * @property string|null $default_private_car_id
 * @property string|null $default_company_car_id
 * @property bool $active
 *
 * @property \App\Model\Entity\AppUser $user
 * @property \App\Model\Entity\AppUser[] $recipients
 * @property \WorkReports\Model\Entity\WorkReportWorkerRecipient[] $work_report_worker_recipients
 * @property \WorkReports\Model\Entity\WorkCar|null $default_private_car
 * @property \WorkReports\Model\Entity\WorkCar|null $default_company_car
 */
class WorkReportWorker extends AppEntity
{
    /**
     * Fields that can be mass assigned using newEntity() or patchEntity().
     *
     * @var array<string, bool>
     */
    protected array $_accessible = [
        'user_id' => true,
        'workload' => true,
        'default_private_car_id' => true,
        'default_company_car_id' => true,
        'active' => true,
    ];
}
