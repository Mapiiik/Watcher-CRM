<?php
declare(strict_types=1);

namespace WorkReports\Model\Entity;

use App\Model\Entity\AppEntity;

/**
 * WorkReportItem Entity
 *
 * A line of a work report, and at the same time the record of work done at a customer.
 *
 * @property string $work_report_id
 * @property string $work_report_item_type_id
 * @property \Cake\I18n\Date $date
 * @property bool $whole_day
 * @property \Cake\I18n\DateTime|null $work_from
 * @property \Cake\I18n\DateTime|null $work_until
 * @property string|null $description
 * @property string|null $customer_id
 * @property string|null $contract_id
 * @property string|null $task_id
 * @property string|null $access_point_id
 * @property string|null $private_car_id
 * @property int|null $private_car_distance
 * @property string|null $company_car_id
 * @property int|null $company_car_distance
 * @property \PhpCollective\DecimalObject\Decimal|null $cash_collected
 * @property bool $to_invoice
 * @property \PhpCollective\DecimalObject\Decimal|null $invoice_hours
 * @property string|null $work_rate_id
 * @property \PhpCollective\DecimalObject\Decimal $rate_multiplier
 * @property string|null $invoice_text
 * @property bool $invoiced
 * @property string|null $note
 * @property int $minutes
 * @property string|null $time_from
 * @property string|null $time_until
 *
 * @property \WorkReports\Model\Entity\WorkReport $work_report
 * @property \WorkReports\Model\Entity\WorkReportItemType $work_report_item_type
 * @property \App\Model\Entity\Customer|null $customer
 * @property \App\Model\Entity\Contract|null $contract
 * @property \App\Model\Entity\Task|null $task
 * @property \WorkReports\Model\Entity\WorkCar|null $private_car
 * @property \WorkReports\Model\Entity\WorkCar|null $company_car
 * @property \WorkReports\Model\Entity\WorkRate|null $work_rate
 * @property \WorkReports\Model\Entity\WorkLabel[] $work_labels
 * @property \App\Model\Entity\AppUser[] $collaborators
 */
class WorkReportItem extends AppEntity
{
    /**
     * Fields that can be mass assigned using newEntity() or patchEntity().
     *
     * @var array<string, bool>
     */
    protected array $_accessible = [
        'work_report_id' => true,
        'work_report_item_type_id' => true,
        'date' => true,
        'whole_day' => true,
        'work_from' => true,
        'work_until' => true,
        'description' => true,
        'customer_id' => true,
        'contract_id' => true,
        'task_id' => true,
        'access_point_id' => true,
        'private_car_id' => true,
        'private_car_distance' => true,
        'company_car_id' => true,
        'company_car_distance' => true,
        'cash_collected' => true,
        'to_invoice' => true,
        'invoice_hours' => true,
        'work_rate_id' => true,
        'rate_multiplier' => true,
        'invoice_text' => true,
        'invoiced' => true,
        'note' => true,
        'work_labels' => true,
        'collaborators' => true,
    ];

    /**
     * The from as a clock shows it, which is what the form offers.
     *
     * @return string|null
     */
    protected function _getTimeFrom(): ?string
    {
        return $this->work_from?->format('H:i');
    }

    /**
     * The until as a clock shows it, which is what the form offers.
     *
     * @return string|null
     */
    protected function _getTimeUntil(): ?string
    {
        return $this->work_until?->format('H:i');
    }

    /**
     * Minutes between from and until, nothing for a whole day.
     *
     * @return int
     */
    protected function _getMinutes(): int
    {
        if ($this->whole_day || $this->work_from === null || $this->work_until === null) {
            return 0;
        }

        return intdiv($this->work_until->getTimestamp() - $this->work_from->getTimestamp(), 60);
    }
}
