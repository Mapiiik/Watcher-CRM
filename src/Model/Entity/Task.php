<?php
declare(strict_types=1);

namespace App\Model\Entity;

use App\ApiClient;
use Cake\ORM\Entity;

/**
 * Task Entity
 *
 * @property \Cake\I18n\FrozenTime|null $created
 * @property int|null $created_by
 * @property \Cake\I18n\FrozenTime|null $modified
 * @property int|null $modified_by
 * @property int $id
 * @property int $task_type_id
 * @property string|null $subject
 * @property string|null $text
 * @property int $priority
 * @property int|null $customer_id
 * @property int|null $dealer_id
 * @property string|null $email
 * @property string|null $phone
 * @property int $task_state_id
 * @property \Cake\I18n\FrozenTime|null $finish_date
 * @property \Cake\I18n\FrozenTime|null $start_date
 * @property \Cake\I18n\FrozenTime|null $estimated_date
 * @property \Cake\I18n\FrozenTime|null $critical_date
 * @property string|null $access_point_id
 * @property string $style
 *
 * @property \App\Model\Entity\TaskType $task_type
 * @property \App\Model\Entity\Customer $customer
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
     * @var array<bool>
     */
    protected $_accessible = [
        'created' => true,
        'created_by' => true,
        'modified' => true,
        'modified_by' => true,
        'task_type_id' => true,
        'subject' => true,
        'text' => true,
        'priority' => true,
        'customer_id' => true,
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
        'dealer' => true,
        'task_state' => true,
        'access_point_id' => true,
        'access_point' => true,
    ];

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
}
