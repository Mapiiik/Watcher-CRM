<?php
declare(strict_types=1);

namespace App\Model\Entity;

use App\ApiClient;
use Cake\ORM\Entity;

/**
 * Task Entity
 *
 * @property \Cake\I18n\DateTime|null $created
 * @property string|null $created_by
 * @property \App\Model\Entity\AppUser|null $creator
 * @property \Cake\I18n\DateTime|null $modified
 * @property string|null $modified_by
 * @property \App\Model\Entity\AppUser|null $modifier
 * @property string $id
 * @property int $nid
 * @property int $task_type_id
 * @property string|null $subject
 * @property string|null $text
 * @property int $priority
 * @property string|null $customer_id
 * @property string|null $contract_id
 * @property int|null $dealer_id
 * @property string|null $email
 * @property string|null $phone
 * @property int $task_state_id
 * @property \Cake\I18n\DateTime|null $finish_date
 * @property \Cake\I18n\DateTime|null $start_date
 * @property \Cake\I18n\DateTime|null $estimated_date
 * @property \Cake\I18n\DateTime|null $critical_date
 * @property string|null $access_point_id
 * @property string $number
 * @property string $summary_text
 * @property string $style
 *
 * @property \App\Model\Entity\TaskType $task_type
 * @property \App\Model\Entity\Customer $customer
 * @property \App\Model\Entity\Contract $contract
 * @property \App\Model\Entity\Customer $dealer
 * @property \App\Model\Entity\TaskState $task_state
 * @property \Cake\ORM\Entity|null $access_point
 */
class Task extends Entity
{
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
        'task_type_id' => true,
        'subject' => true,
        'text' => true,
        'priority' => true,
        'customer_id' => true,
        'contract_id' => true,
        'dealer_id' => true,
        'email' => true,
        'phone' => true,
        'task_state_id' => true,
        'finish_date' => true,
        'start_date' => true,
        'estimated_date' => true,
        'critical_date' => true,
        'task_type' => true,
        'customer' => true,
        'contract' => true,
        'dealer' => true,
        'task_state' => true,
        'access_point_id' => true,
        'access_point' => true,
    ];

    /**
     * getter for task number
     *
     * @return string
     */
    protected function _getNumber(): string
    {
        $number = strval($this->nid);

        return $number;
    }

    /**
     * getter for acess point (try to load via ApiClient)
     *
     * @return \Cake\ORM\Entity|null
     */
    protected function _getAccessPoint(): ?Entity
    {
        if ($this->access_point_id) {
            return ApiClient::getAccessPoint($this->access_point_id);
        }

        return null;
    }

    /**
     * getter for summary text
     *
     * @return string
     */
    protected function _getSummaryText(): string
    {
        $summary_text = '';

        $summary_text .= $this->contract->number ?? $this->customer->number ?? '';
        $summary_text .= (!empty($summary_text) ? ' - ' : '') . $this->task_type->name;

        if (isset($this->customer)) {
            $summary_text .= ' - ' . ($this->customer->company ?? $this->customer->last_name ?? '');
        }

        if (isset($this->contract->installation_address)) {
            $summary_text .=
                ', '
                . $this->contract->installation_address->street_and_number
                . ', '
                . $this->contract->installation_address->city;
        } elseif (isset($this->customer->installation_address)) {
            $summary_text .=
                ', '
                . $this->customer->installation_address->street_and_number
                . ', '
                . $this->customer->installation_address->city;
        } else {
            // nothing
        }

        if (isset($this->phone)) {
            $summary_text .= ', ' . $this->phone;
        }

        $summary_text .= ' - ' . $this->subject;

        return $summary_text;
    }

    /**
     * getter for style
     *
     * @return string
     */
    protected function _getStyle(): string
    {
        $style = '';

        if (isset($this->task_state->color)) {
            $style = 'background-color: ' . $this->task_state->color . ';';
        }

        return $style;
    }

    /**
     * Get task priority options method
     *
     * @return array<int, string>
     */
    public function getPriorityOptions(): array
    {
        return [
            -10 => __('Low'),
            0 => __('Normal'),
            10 => __('High'),
            50 => __('Urgent'),
        ];
    }

    /**
     * Get task priority name method
     *
     * @return string
     */
    public function getPriorityName(): string
    {
        return $this->getPriorityOptions()[$this->priority] ?? (string)$this->priority;
    }
}
