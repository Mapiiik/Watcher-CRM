<?php
declare(strict_types=1);

namespace App\Model\Entity;

use App\Colors\ColorThemeSelector;
use App\NMS\ApiClient as NMSApiClient;
use ArrayObject;
use Cake\Core\Configure;

/**
 * Task Entity
 *
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
 * @property \ArrayObject<string, mixed>|null $access_point
 * @property string|null $access_point_name
 */
class Task extends AppEntity
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
        return strval($this->nid);
    }

    /**
     * getter for acess point (try to load via ApiClient)
     *
     * @return \ArrayObject<string, mixed>|null
     */
    protected function _getAccessPoint(): ?ArrayObject
    {
        if ($this->access_point_id) {
            return NMSApiClient::getAccessPoint($this->access_point_id);
        }

        return null;
    }

    /**
     * getter for acess point name (try to load via ApiClient)
     *
     * @return string|null
     */
    protected function _getAccessPointName(): ?string
    {
        // load access points from NMS if possible
        $accessPoints = NMSApiClient::getAccessPointsList();

        if ($accessPoints && $this->access_point_id) {
            return $accessPoints[$this->access_point_id] ?? null;
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
        $phoneNumber = $this->phone;
        if (isset($phoneNumber) && Configure::read('Phones.stripPrefixForSummary') === true) {
            // Replace "+" and following numbers with an empty string
            $phoneNumber = preg_replace('/\+\d+/', '', $phoneNumber) ?? $phoneNumber;
            // Remove all spaces and then add spaces after the commas
            $phoneNumber = str_replace([' ', ','], ['', ', '], $phoneNumber);
        }

        // The contract's address wins over the customer's one.
        $address = $this->contract->installation_address
            ?? $this->customer->installation_address
            ?? null;

        // The subject and the customer head the line, the rest follows behind commas.
        $summary_text = implode(', ', array_filter([
            implode(' - ', array_filter([
                $this->subject ?? $this->task_type->name ?? null,
                $this->customer->company ?? $this->customer->last_name ?? null,
            ])),
            $address?->street_and_number,
            $address?->city,
            $phoneNumber,
        ]));

        $number = $this->contract->number ?? $this->customer->number ?? null;

        return $number !== null ? $summary_text . ' (' . $number . ')' : $summary_text;
    }

    /**
     * getter for style
     *
     * @return string
     */
    protected function _getStyle(): string
    {
        if (!isset($this->task_state->color)) {
            // no dynamic style
            return '';
        }

        $theme = Configure::read('UI.theme');
        $theme = is_string($theme) ? $theme : null;

        $backgroundColor = ColorThemeSelector::forTheme(
            $this->task_state->color,
            $theme,
        );

        return 'background-color: ' . $backgroundColor . ';';
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
