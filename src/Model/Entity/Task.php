<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * Task Entity
 *
 * @property int $id
 * @property int $task_type_id
 * @property string|null $subject
 * @property string|null $text
 * @property int $priority
 * @property int|null $customer_id
 * @property int|null $dealer_id
 * @property int $modified_by
 * @property \Cake\I18n\FrozenTime|null $modified
 * @property int $created_by
 * @property \Cake\I18n\FrozenTime|null $created
 * @property string|null $email
 * @property string|null $phone
 * @property int $task_state_id
 * @property \Cake\I18n\FrozenTime|null $finish_date
 * @property \Cake\I18n\FrozenTime|null $start_date
 * @property \Cake\I18n\FrozenTime|null $estimated_date
 * @property \Cake\I18n\FrozenTime|null $critical_date
 * @property int|null $router_id
 *
 * @property \App\Model\Entity\TaskType $task_type
 * @property \App\Model\Entity\Customer $customer
 * @property \App\Model\Entity\Dealer $dealer
 * @property \App\Model\Entity\TaskState $task_state
 * @property \App\Model\Entity\Router $router
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
     * @var array
     */
    protected $_accessible = [
        'task_type_id' => true,
        'subject' => true,
        'text' => true,
        'priority' => true,
        'customer_id' => true,
        'dealer_id' => true,
        'modified_by' => true,
        'modified' => true,
        'created_by' => true,
        'created' => true,
        'email' => true,
        'phone' => true,
        'task_state_id' => true,
        'finish_date' => true,
        'start_date' => true,
        'estimated_date' => true,
        'critical_date' => true,
        'router_id' => true,
        'task_type' => true,
        'customer' => true,
        'dealer' => true,
        'task_state' => true,
        'router' => true,
    ];
}
